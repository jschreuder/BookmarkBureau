<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Exception;

use jschreuder\BookmarkBureau\Entity\Value\Email;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

final class UserNotFoundException extends RuntimeException
{
    public static function forId(UuidInterface $id): self
    {
        return new self("User with id '{$id->toString()}' not found");
    }

    public static function forEmail(Email $email): self
    {
        return new self("User with email '{$email}' not found");
    }
}
