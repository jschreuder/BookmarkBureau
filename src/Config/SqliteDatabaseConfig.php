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
    private ?PDO $dbInstance = null;
    private ?PipelineInterface $defaultPipeline = null;

    public function __construct(private readonly string $dsn) {}

    #[\Override]
    public function getDatabaseType(): string
    {
        return "sqlite";
    }

    #[\Override]
    public function getConnection(): PDO
    {
        if ($this->dbInstance === null) {
            $this->dbInstance = new PDO($this->dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }
        return $this->dbInstance;
    }

    #[\Override]
    public function getDefaultPipeline(): PipelineInterface
    {
        if ($this->defaultPipeline === null) {
            $this->defaultPipeline = new Pipeline(
                new PdoTransactionMiddleware($this->getConnection()),
            );
        }
        return $this->defaultPipeline;
    }
}
