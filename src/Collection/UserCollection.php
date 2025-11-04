<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Collection;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Entity\User;

final readonly class UserCollection implements IteratorAggregate, Countable
{
    use CollectionTrait;

    public function __construct(
        User ...$users
    ) {
        $this->collection = $users;
    }
}
