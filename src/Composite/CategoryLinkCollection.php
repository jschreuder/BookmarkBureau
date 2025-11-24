<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Composite\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\CategoryLink;

/**
 * @implements IteratorAggregate<int, CategoryLink>
 */
final readonly class CategoryLinkCollection implements
    IteratorAggregate,
    Countable
{
    /** @use CollectionTrait<CategoryLink> */
    use CollectionTrait;

    public function __construct(CategoryLink ...$categoryLinks)
    {
        $this->collection = $categoryLinks;
    }
}
