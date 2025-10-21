<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Exception;

use Ramsey\Uuid\UuidInterface;
use RuntimeException;

final class TagNotFoundException extends RuntimeException
{
    public static function forName(UuidInterface $tagName): self
    {
        return new self("Tag with name '{$tagName}' not found", 404);
    }
}
