<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Util;

use PDOException;

/**
 * Helper for detecting specific error conditions from PDOExceptions.
 *
 * Provides consistent detection of common SQL errors across different database systems:
 * - Foreign key constraint violations (SQLite: "FOREIGN KEY constraint failed", MySQL: "foreign key constraint fails")
 * - Duplicate entry violations (MySQL: "Duplicate entry", SQLite: "UNIQUE constraint failed")
 *
 * This eliminates duplication of error detection logic across repositories.
 */
final readonly class SqlExceptionHandler
{
    /**
     * Check if exception is a foreign key constraint violation.
     *
     * Detects FK violations across different database systems:
     * - SQLite: "FOREIGN KEY constraint failed"
     * - MySQL: "foreign key constraint fails"
     *
     * @param PDOException $e The exception to check
     * @return bool True if this is a foreign key violation
     */
    public static function isForeignKeyViolation(PDOException $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, "foreign key constraint fail");
    }

    /**
     * Check if exception is a duplicate entry/unique constraint violation.
     *
     * Detects duplicate/unique violations across different database systems:
     * - MySQL: "Duplicate entry 'value' for key 'index_name'"
     * - SQLite: "UNIQUE constraint failed: table.column"
     *
     * @param PDOException $e The exception to check
     * @return bool True if this is a duplicate entry violation
     */
    public static function isDuplicateEntry(PDOException $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, "duplicate entry") ||
            str_contains($message, "unique constraint failed");
    }

    /**
     * Extract the field name from a foreign key constraint error message.
     *
     * Attempts to identify which field caused the FK violation by looking for:
     * - MySQL: Field names in backticks like `dashboard_id` in constraint definition
     * - SQLite: Less specific, so checks for known field names in message
     *
     * Example messages:
     * - MySQL: "foreign key constraint fails (`db`.`table`, CONSTRAINT `fk` FOREIGN KEY (`dashboard_id`)...)"
     * - SQLite: "FOREIGN KEY constraint failed" (field not specified in message)
     *
     * @param PDOException $e The exception to analyze
     * @return string|null The field name if found, null otherwise
     */
    public static function getForeignKeyField(PDOException $e): ?string
    {
        $message = strtolower($e->getMessage());

        // Try to extract field name from MySQL FOREIGN KEY clause pattern
        // Matches: "FOREIGN KEY (`field_id`)" not "CONSTRAINT `fk_name`"
        if (
            preg_match("/foreign key \(`([a-z_]+_id)`\)/", $message, $matches)
        ) {
            return $matches[1];
        }

        // Check if message contains known field names (case-insensitive)
        $commonFields = [
            "dashboard_id",
            "link_id",
            "tag_id",
            "category_id",
            "user_id",
        ];

        foreach ($commonFields as $field) {
            if (str_contains($message, $field)) {
                return $field;
            }
        }

        return null;
    }
}
