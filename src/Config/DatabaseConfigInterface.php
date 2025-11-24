<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use PDO;

/**
 * Database configuration interface.
 * Implementations define database type, connection parameters, and operation pipelines.
 * Different implementations expose different configuration strategies (SQLite, MySQL, etc.).
 */
interface DatabaseConfigInterface
{
    /**
     * Get the database type identifier (e.g., "sqlite", "mysql")
     */
    public function getDatabaseType(): string;

    /**
     * Create and return a configured PDO connection
     * MUST be implemented to always return the same connection instance.
     */
    public function getConnection(): PDO;

    /**
     * Get the default operation pipeline for database operations (e.g., transaction handling)
     * MUST be implemented to always return the same connection instance.
     */
    public function getDefaultPipeline(): PipelineInterface;
}
