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
final class PostgresDatabaseConfig implements DatabaseConfigInterface
{
    private PDO $dbInstance;
    private PipelineInterface $defaultPipeline;

    public function __construct(
        public string $host,
        public string $dbname,
        public string $user,
        public string $pass,
        public int $port = 5432,
        public string $charset = "utf8",
    ) {}

    #[\Override]
    public function getDatabaseType(): string
    {
        return "pgsql";
    }

    #[\Override]
    public function getConnection(): PDO
    {
        if (!isset($this->dbInstance)) {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // NOTE: PostgreSQL's rowCount() behavior for UPDATE statements
                // PostgreSQL always returns the number of rows that MATCHED the WHERE clause,
                // not the number of rows that were CHANGED. This is PostgreSQL's built-in
                // behavior and cannot be configured (similar to SQLite). This is the desired
                // behavior for repository update() methods to properly detect non-existent
                // entities while allowing updates with identical values to succeed.
                // This matches the behavior we configure for MySQL with MYSQL_ATTR_FOUND_ROWS.
            ];

            $this->dbInstance = new PDO(
                $dsn,
                $this->user,
                $this->pass,
                $options,
            );

            // Set client encoding
            $this->dbInstance->exec("SET NAMES '{$this->charset}'");
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
