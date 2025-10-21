<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Exception;

use Ramsey\Uuid\UuidInterface;
use RuntimeException;

final class CategoryNotFoundException extends RuntimeException
{
    public static function forId(UuidInterface $categoryId): self
    {
        return new self("Category with ID '{$categoryId}' not found", 404);
    }
}
