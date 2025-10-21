<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Exception;

use Ramsey\Uuid\UuidInterface;
use RuntimeException;

final class DashboardNotFoundException extends RuntimeException
{
    public static function forId(UuidInterface $dashboardId): self
    {
        return new self("Dashboard with ID '{$dashboardId}' not found", 404);
    }
}
