<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Collection;

use Iterator;

/**
 * Trait for creating type-safe collections of entities.
 *
 * Implementing classes must:
 * 1. Store entities in a private array property named $collection
 * 2. Implement a constructor that accepts variadic entities and assigns them to $this->collection
 *
 * Example implementation:
 * ```
 *  final class LinkCollection implements IteratorAggregate, Countable
 *  {
 *      use CollectionTrait;
 *
 *      public function __construct(
 *          Link ...$links
 *      ) {
 *          $this->collection = $links;
 *      }
 *  }
 * ```
 */
trait CollectionTrait
{
    private array $collection;

    public function getIterator(): Iterator
    {
        foreach ($this->collection as $item) {
            yield $item;
        }
    }

    public function count(): int
    {
        return count($this->collection);
    }

    public function isEmpty(): bool
    {
        return empty($this->collection);
    }

    public function toArray(): array
    {
        return $this->collection;
    }
}
