<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Collection;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Collection\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\Value\TagName;

final readonly class TagNameCollection implements IteratorAggregate, Countable
{
    use CollectionTrait;

    public function __construct(
        TagName ...$tagNames
    ) {
        $this->collection = $tagNames;
    }
}
