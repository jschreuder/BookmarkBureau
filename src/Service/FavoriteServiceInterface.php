<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface FavoriteServiceInterface
{
    /**
     * Add a link as favorite to a dashboard
     *
     * @throws DashboardNotFoundException when dashboard doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function addFavorite(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
    ): Favorite;

    /**
     * Remove a favorite from a dashboard
     *
     * @throws FavoriteNotFoundException when favorite doesn't exist
     */
    public function removeFavorite(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
    ): void;

    /**
     * Reorder favorites within a dashboard
     *
     * @param UuidInterface $dashboardId
     * @param array<string, int> $linkIdToSortOrder Map of link UUID strings to sort orders
     * @return FavoriteCollection The reordered favorites
     */
    public function reorderFavorites(
        UuidInterface $dashboardId,
        array $linkIdToSortOrder,
    ): FavoriteCollection;
}
