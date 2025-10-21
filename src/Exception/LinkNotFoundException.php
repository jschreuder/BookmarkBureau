<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Exception;

use Ramsey\Uuid\UuidInterface;
use RuntimeException;

final class LinkNotFoundException extends RuntimeException
{
    public static function forId(UuidInterface $linkId): self
    {
        return new self("Link with ID '{$linkId}' not found", 404);
    }
}
