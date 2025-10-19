<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Collection;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Collection\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\Category;

final readonly class CategoryCollection implements IteratorAggregate, Countable
{
    use CollectionTrait;

    public function __construct(
        Category ...$categories
    ) {
        $this->collection = $categories;
    }
}
