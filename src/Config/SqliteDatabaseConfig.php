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
final class SqliteDatabaseConfig implements DatabaseConfigInterface
{
    private PDO $dbInstance;
    private PipelineInterface $defaultPipeline;

    public function __construct(private readonly string $dsn) {}

    #[\Override]
    public function getDatabaseType(): string
    {
        return "sqlite";
    }

    #[\Override]
    public function getConnection(): PDO
    {
        if (!isset($this->dbInstance)) {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // NOTE: SQLite's rowCount() behavior for UPDATE statements
                // SQLite always returns the number of rows that MATCHED the WHERE clause,
                // not the number of rows that were CHANGED. This is SQLite's built-in
                // behavior and cannot be configured. This is the desired behavior for
                // repository update() methods to properly detect non-existent entities
                // while allowing updates with identical values to succeed.
            ];

            $this->dbInstance = new PDO($this->dsn, null, null, $options);
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
