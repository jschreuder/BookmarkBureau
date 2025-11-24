<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Composite\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\Link;

/**
 * @implements IteratorAggregate<int, Link>
 */
final readonly class LinkCollection implements IteratorAggregate, Countable
{
    /** @use CollectionTrait<Link> */
    use CollectionTrait;

    public function __construct(Link ...$links)
    {
        $this->collection = array_values($links);
    }
}
