<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Composite\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\Tag;

/**
 * @implements IteratorAggregate<int, Tag>
 */
final readonly class TagCollection implements IteratorAggregate, Countable
{
    /** @use CollectionTrait<Tag> */
    use CollectionTrait;

    public function __construct(Tag ...$tags)
    {
        $this->collection = $tags;
    }
}
