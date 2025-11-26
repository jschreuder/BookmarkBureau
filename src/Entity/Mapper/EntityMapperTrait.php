<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * This trait implements common validation logic for EntityMappers to prevent
 * repetitive error handling in each implementation.
 *
 * It provides:
 * - mapToEntity validation: Checks that all required fields are present
 * - mapToRow validation: Checks that the entity is supported via supports()
 *
 * Implementing classes must define abstract methods doMapToEntity() and
 * doMapToRow(), allowing the trait to handle validation while delegating
 * the actual transformation logic.
 *
 * @template TEntity of object
 * @template TIn of array<string, mixed>
 * @template TOut of array<string, mixed>
 */
trait EntityMapperTrait
{
    abstract public function supports(object $entity): bool;

    abstract public function getFields(): array;

    /**
     * @param TIn $data
     * @return TEntity
     */
    public function mapToEntity(array $data): object
    {
        $fields = $this->getFields();
        $missingFields = array_diff($fields, array_keys($data));

        if ($missingFields !== []) {
            throw new InvalidArgumentException(
                static::class .
                    " requires fields: " .
                    implode(", ", $missingFields),
            );
        }

        return $this->doMapToEntity($data);
    }

    /**
     * @param TIn $data
     * @return TEntity
     */
    abstract private function doMapToEntity(array $data): object;

    /**
     * @param TEntity $entity
     * @return TOut
     */
    public function mapToRow(object $entity): array
    {
        if (!$this->supports($entity)) {
            throw new InvalidArgumentException(
                static::class .
                    " does not support objects of type " .
                    \get_class($entity),
            );
        }

        return $this->doMapToRow($entity);
    }

    /**
     * @param TEntity $entity
     * @return TOut
     */
    abstract private function doMapToRow(object $entity): array;

    /**
     * Replace a field name in an array of fields
     *
     * @param array<int, string> $fields
     * @param string $currentField
     * @param string $newFieldName
     * @return array<int, string>
     * @throws OutOfBoundsException if the current field is not found
     */
    private function replaceField(
        array $fields,
        string $currentField,
        string $newFieldName,
    ): array {
        $key = array_search($currentField, $fields, true);
        if ($key === false) {
            throw new OutOfBoundsException(
                "Field '{$currentField}' not found in fields array",
            );
        }
        $fields[$key] = $newFieldName;
        return $fields;
    }
}
