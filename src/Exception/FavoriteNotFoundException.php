<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Exception;

use Ramsey\Uuid\UuidInterface;
use RuntimeException;

final class FavoriteNotFoundException extends RuntimeException
{
    public static function forDashboardAndLink(UuidInterface $dashboardId, UuidInterface $linkId): self
    {
        return new self("Favorite not found for dashboard '{$dashboardId}' and link '{$linkId}'", 404);
    }
}
