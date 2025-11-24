<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Composite\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\Value\TagName;

/**
 * @implements IteratorAggregate<int, TagName>
 */
final readonly class TagNameCollection implements IteratorAggregate, Countable
{
    /** @use CollectionTrait<TagName> */
    use CollectionTrait;

    public function __construct(TagName ...$tagNames)
    {
        $this->collection = $tagNames;
    }
}
