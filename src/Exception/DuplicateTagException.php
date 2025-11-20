<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Exception;

use jschreuder\BookmarkBureau\Entity\Value\TagName;
use RuntimeException;

final class DuplicateTagException extends RuntimeException
{
    public static function forName(TagName $tagName): self
    {
        return new self("Tag with name '{$tagName}' already exists", 409);
    }
}
