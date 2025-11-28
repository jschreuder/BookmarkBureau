<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

use jschreuder\BookmarkBureau\OperationPipeline\Pipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use jschreuder\BookmarkBureau\OperationMiddleware\PdoTransactionMiddleware;
use PDO;

/**
 * Not readonly: PDO connection is created lazily on first access and cached
 * for the lifetime of this instance.
 */
final class MysqlDatabaseConfig implements DatabaseConfigInterface
{
    private PDO $dbInstance;
    private PipelineInterface $defaultPipeline;

    public function __construct(
        public string $host,
        public string $dbname,
        public string $user,
        public string $pass,
        public int $port = 3306,
        public string $charset = "utf8mb4",
    ) {}

    #[\Override]
    public function getDatabaseType(): string
    {
        return "mysql";
    }

    #[\Override]
    public function getConnection(): PDO
    {
        if (!isset($this->dbInstance)) {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
                // CRITICAL: Use FOUND_ROWS instead of AFFECTED_ROWS for rowCount()
                // This makes rowCount() return the number of rows that MATCHED the WHERE clause
                // rather than rows that were CHANGED. This is required for repository update()
                // methods to properly detect non-existent entities (UPDATE returns 0 for both
                // "no match" and "match but no change"), while still allowing updates with
                // identical values to succeed without throwing exceptions.
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
            ];

            $this->dbInstance = new PDO(
                $dsn,
                $this->user,
                $this->pass,
                $options,
            );
        }
        return $this->dbInstance;
    }

    #[\Override]
    public function getDefaultPipeline(): PipelineInterface
    {
        if (!isset($this->defaultPipeline)) {
            $this->defaultPipeline = new Pipeline(
                new PdoTransactionMiddleware($this->getConnection()),
            );
        }
        return $this->defaultPipeline;
    }
}
