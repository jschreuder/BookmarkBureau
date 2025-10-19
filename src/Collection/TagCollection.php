<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Collection;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Collection\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\Tag;

final readonly class TagCollection implements IteratorAggregate, Countable
{
    use CollectionTrait;

    public function __construct(
        Tag ...$tags
    ) {
        $this->collection = $tags;
    }
}
