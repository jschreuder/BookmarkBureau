<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Composite\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\Favorite;

/**
 * @implements IteratorAggregate<int, Favorite>
 */
final readonly class FavoriteCollection implements IteratorAggregate, Countable
{
    /** @use CollectionTrait<Favorite> */
    use CollectionTrait;

    public function __construct(Favorite ...$favorites)
    {
        $this->collection = $favorites;
    }
}
