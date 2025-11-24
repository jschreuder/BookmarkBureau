<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\ServiceContainer;

use jschreuder\BookmarkBureau\Config\DatabaseConfigInterface;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use PDO;

trait DatabaseTrait
{
    // Abstract for method from config/ServiceContainer
    abstract public function getDatabaseConfig(): DatabaseConfigInterface;
    abstract public function getRateLimitDatabaseConfig(): DatabaseConfigInterface;

    public function getDb(): PDO
    {
        return $this->getDatabaseConfig()->getConnection();
    }

    public function getRateLimitDb(): PDO
    {
        return $this->getRateLimitDatabaseConfig()->getConnection();
    }

    public function getDefaultDbPipeline(): PipelineInterface
    {
        return $this->getDatabaseConfig()->getDefaultPipeline();
    }
}
