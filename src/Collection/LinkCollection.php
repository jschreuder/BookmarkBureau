<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Collection;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Collection\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\Link;

final readonly class LinkCollection implements IteratorAggregate, Countable
{
    use CollectionTrait;

    public function __construct(Link ...$links)
    {
        $this->collection = $links;
    }
}
