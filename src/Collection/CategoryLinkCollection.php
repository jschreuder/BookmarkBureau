<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Collection;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Collection\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\CategoryLink;

final readonly class CategoryLinkCollection implements IteratorAggregate, Countable
{
    use CollectionTrait;

    public function __construct(
        CategoryLink ...$categoryLinks
    ) {
        $this->collection = $categoryLinks;
    }
}
