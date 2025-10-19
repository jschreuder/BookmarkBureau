<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Collection;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Collection\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\Favorite;

final readonly class FavoriteCollection implements IteratorAggregate, Countable
{
    use CollectionTrait;

    public function __construct(
        Favorite ...$favorites
    ) {
        $this->collection = $favorites;
    }
}
