<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use Traversable;

/**
 * Trait for creating type-safe collections of entities.
 *
 * Implementing classes must:
 * 1. Store entities in a private array property named $collection
 * 2. Implement a constructor that accepts variadic entities and assigns them to $this->collection
 * 3. Add @implements IteratorAggregate<int, EntityType> annotation
 *
 * Example implementation:
 * ```
 *  /**
 *   * @implements IteratorAggregate<int, Link>
 *   *\/
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
 *
 * @template T
 */
trait CollectionTrait
{
    /** @var array<int, T> */
    private readonly array $collection;

    public function getIterator(): Traversable
    {
        foreach ($this->collection as $item) {
            yield $item;
        }
    }

    public function count(): int
    {
        return \count($this->collection);
    }

    public function isEmpty(): bool
    {
        return empty($this->collection);
    }

    /**
     * @return array<int, T>
     */
    public function toArray(): array
    {
        return $this->collection;
    }
}
