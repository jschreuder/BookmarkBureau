<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use DomainException;
use InvalidArgumentException;

/**
 * Specification for bidirectional transformation between domain entities and data rows
 *
 * EntityMappers provide symmetric transformation capabilities:
 * - mapToEntity: row array (from database/HTTP) → domain entity instance
 * - mapToRow: domain entity instance → row array (for database/HTTP)
 *
 * Functionally, EntityMappers are hydrators that bridge the gap between
 * persistent storage formats (database rows, API payloads) and domain objects
 * (entities with business logic). They encapsulate the mapping logic for a
 * specific entity type, providing a single point of responsibility for
 * transformation in both directions.
 *
 * @template T of object
 */
interface EntityMapperInterface
{
    /**
     * Get all field names that this mapper handles
     *
     * Returns the complete list of field names that can be mapped between
     * row arrays and entities. This is useful for validation, filtering, and
     * query construction.
     *
     * @return array<string> List of field names supported by this mapper
     */
    public function getFields(): array;

    /**
     * Determines if this mapper supports transforming the given data
     *
     * This method checks whether the provided entity is of a type that this
     * mapper can transform. Used for routing transformation requests to the
     * appropriate mapper implementation.
     */
    public function supports(object $entity): bool;

    /**
     * Transform a row array into a domain entity instance
     *
     * This method takes a flat array representation (typically from a database
     * query or HTTP request) and hydrates it into a fully-constructed domain
     * entity instance. The array should contain all fields necessary to
     * construct a valid entity.
     *
     * @param array<string, mixed> $data
     * @return T
     * @throws InvalidArgumentException When required fields are missing or invalid
     * @throws DomainException When entity construction fails due to business rule violations
     */
    public function mapToEntity(array $data): object;

    /**
     * Transform a domain entity instance into a row array
     *
     * This method takes a domain entity instance and flattens it into an array
     * suitable for persistence (database insert/update) or serialization
     * (API response). The resulting array uses field names that correspond to
     * database columns or API payload keys.
     *
     * @param T $entity
     * @return array<string, mixed>
     * @throws \InvalidArgumentException When entity is of unsupported type
     */
    public function mapToRow(object $entity): array;
}
