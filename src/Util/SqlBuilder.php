<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Util;

use jschreuder\BookmarkBureau\Entity\Mapper\EntityMapperInterface;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;

/**
 * Helper for building dynamic SQL statements from field lists.
 *
 * Encapsulates the logic for:
 * - Generating SELECT column lists
 * - Generating INSERT column lists and placeholders
 * - Building UPDATE SET clauses
 * - Preparing parameter arrays with proper placeholder keys
 *
 * This eliminates duplication when repositories need to dynamically construct
 * SQL based on entity mapper field definitions.
 */
final readonly class SqlBuilder
{
    /**
     * Generate a SELECT field list from an EntityMapper's fields.
     *
     * Useful for constructing SELECT clauses or for use within JOIN queries
     * where fields need to be table-qualified.
     *
     * @template TEntity of object
     * @template TIn of array<string, mixed>
     * @template TOut of array<string, mixed>
     * @param EntityMapperInterface<TEntity, TIn, TOut> $mapper The entity mapper to extract fields from
     * @param string|null $tableAlias Optional table alias (e.g., "l" for "links l")
     *                                 If provided, fields will be qualified (e.g., "l.field_name")
     * @param array<string, string> $fieldAliases Optional mapping of field names to SQL aliases
     *                               (e.g., ["created_at" => "createdAt"] produces "table.created_at AS createdAt")
     * @return string Comma-separated field list
     * @throws RepositoryStorageException If a field alias references a non-existent field
     */
    public static function selectFieldsFromMapper(
        EntityMapperInterface $mapper,
        ?string $tableAlias = null,
        array $fieldAliases = [],
    ): string {
        $fields = $mapper->getDbFields();

        // Validate that all field aliases reference existing fields
        $nonExistentFields = array_diff_key($fieldAliases, array_flip($fields));
        if (!empty($nonExistentFields)) {
            $fieldName = array_key_first($nonExistentFields);
            throw new RepositoryStorageException(
                "Field alias references non-existent field \"{$fieldName}\"",
            );
        }

        return implode(
            ", ",
            array_map(function (string $field) use (
                $tableAlias,
                $fieldAliases,
            ): string {
                $fieldExpr =
                    $tableAlias !== null && $tableAlias !== ""
                        ? "{$tableAlias}.{$field}"
                        : $field;

                return $fieldExpr .
                    (isset($fieldAliases[$field])
                        ? " AS {$fieldAliases[$field]}"
                        : "");
            }, $fields),
        );
    }

    /**
     * Build a SELECT statement with the given fields.
     *
     * @param string $table The table name
     * @param array<string> $fields The field names to select
     * @param string|null $where Optional WHERE clause (without WHERE keyword)
     * @param string|null $orderBy Optional ORDER BY clause (without ORDER BY keyword)
     * @param int|null $limit Optional LIMIT clause value
     * @param int|null $offset Optional OFFSET clause value (only applied if $limit is set)
     * @return string The complete SELECT statement
     */
    public static function buildSelect(
        string $table,
        array $fields,
        ?string $where = null,
        ?string $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): string {
        $sql = "SELECT " . implode(", ", $fields) . " FROM {$table}";

        if ($where !== null) {
            $sql .= " WHERE {$where}";
        }

        if ($orderBy !== null) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
            if ($offset !== null) {
                $sql .= " OFFSET {$offset}";
            }
        }

        return $sql;
    }
    /**
     * Build an INSERT statement and its parameters from a row array.
     *
     * @param string $table The table name
     * @param array<string, mixed> $row The row data with field names as keys
     * @return array{sql: string, params: array<string, mixed>}
     */
    public static function buildInsert(string $table, array $row): array
    {
        $fields = array_keys($row);
        $placeholders = array_map(fn($field) => ":{$field}", $fields);

        $sql =
            "INSERT INTO {$table} (" .
            implode(", ", $fields) .
            ")
                VALUES (" .
            implode(", ", $placeholders) .
            ")";

        $params = [];
        foreach ($fields as $field) {
            $params[":{$field}"] = $row[$field];
        }

        return [
            "sql" => $sql,
            "params" => $params,
        ];
    }

    /**
     * Build an UPDATE statement and its parameters from a row array.
     *
     * Excludes the specified primary key field from the SET clause.
     *
     * @param string $table The table name
     * @param array<string, mixed> $row The row data with field names as keys
     * @param string $primaryKeyField The primary key field name (excluded from SET)
     * @return array{sql: string, params: array<string, mixed>}
     */
    public static function buildUpdate(
        string $table,
        array $row,
        string $primaryKeyField,
    ): array {
        $fields = array_keys($row);
        $updateFields = array_filter(
            $fields,
            fn($field) => $field !== $primaryKeyField,
        );

        $setClauses = array_map(
            fn($field) => "{$field} = :{$field}",
            $updateFields,
        );

        $sql =
            "UPDATE {$table} SET " .
            implode(", ", $setClauses) .
            " WHERE {$primaryKeyField} = :{$primaryKeyField}";

        $params = [];
        foreach ($fields as $field) {
            $params[":{$field}"] = $row[$field];
        }

        return [
            "sql" => $sql,
            "params" => $params,
        ];
    }
}
