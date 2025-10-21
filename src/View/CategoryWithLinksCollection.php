<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\View;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Collection\CollectionTrait;

/**
 * View object representing a list of categories with their links
 */
final readonly class CategoryWithLinksCollection implements IteratorAggregate, Countable
{
    use CollectionTrait;

    public function __construct(
        CategoryWithLinks ...$categoryWithLinks
    ) {
        $this->collection = $categoryWithLinks;
    }
}
