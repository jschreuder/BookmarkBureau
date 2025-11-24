<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Composite\CollectionTrait;

/**
 * View object representing a list of categories with their links
   @implements IteratorAggregate<int, CategoryWithLinks>
 */
final readonly class CategoryWithLinksCollection implements
    IteratorAggregate,
    Countable
{
    /** @use CollectionTrait<CategoryWithLinks> */
    use CollectionTrait;

    public function __construct(CategoryWithLinks ...$categoryWithLinks)
    {
        $this->collection = array_values($categoryWithLinks);
    }
}
