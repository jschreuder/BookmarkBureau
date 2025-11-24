<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use InvalidArgumentException;

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
 * @template T of object
 */
trait EntityMapperTrait
{
    abstract public function supports(object $entity): bool;

    abstract public function getFields(): array;

    /** @return T */
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
     * @param array<string, mixed> $data
     * @return T
     */
    abstract private function doMapToEntity(array $data): object;

    /** @param T $entity */
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
     * @param T $entity
     * @return array<string, mixed>
     */
    abstract private function doMapToRow(object $entity): array;
}
