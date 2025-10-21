<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Exception;

use RuntimeException;

final class CategoryNotFoundException extends RuntimeException
{
    public static function forId(string $categoryId): self
    {
        return new self("Category with ID '{$categoryId}' not found", 404);
    }
}
