<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Entity\User;

/**
 * @implements IteratorAggregate<int, User>
 */
final readonly class UserCollection implements IteratorAggregate, Countable
{
    /** @use CollectionTrait<User> */
    use CollectionTrait;

    public function __construct(User ...$users)
    {
        $this->collection = array_values($users);
    }
}
