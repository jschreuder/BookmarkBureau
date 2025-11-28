<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Exception;

use jschreuder\BookmarkBureau\Entity\Value\Email;
use RuntimeException;

final class DuplicateUserException extends RuntimeException
{
    public static function forEmail(Email $email): self
    {
        return new self("Tag with name '{$email}' already exists", 409);
    }
}
