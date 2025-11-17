<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Composite\DashboardCollection;
use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface FavoriteRepositoryInterface
{
    /**
     * Get all favorites for a dashboard, ordered by sort_order
     */
    public function findByDashboardId(
        UuidInterface $dashboardId,
    ): FavoriteCollection;

    /**
     * Get the highest sort_order value for favorites in a dashboard
     * Returns -1 if dashboard has no favorites
     */
    public function getMaxSortOrderForDashboardId(
        UuidInterface $dashboardId,
    ): int;

    /**
     * Add a link as favorite to a dashboard
     * @throws DashboardNotFoundException when dashboard doesn't exist (FK violation)
     * @throws LinkNotFoundException when link doesn't exist (FK violation)
     */
    public function addFavorite(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
        int $sortOrder,
    ): Favorite;

    /**
     * Remove a favorite from a dashboard
     * @throws FavoriteNotFoundException when favorite doesn't exist
     */
    public function removeFavorite(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
    ): void;

    /**
     * Check if a link is favorited on a dashboard
     */
    public function isFavorite(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
    ): bool;

    /**
     * Update sort order for a favorite
     * @throws FavoriteNotFoundException when favorite doesn't exist
     */
    public function updateSortOrder(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
        int $sortOrder,
    ): void;

    /**
     * Reorder favorites in a dashboard
     * @param array<string, int> $linkIdToSortOrder Map of link UUID strings to sort orders
     */
    public function reorderFavorites(
        UuidInterface $dashboardId,
        array $linkIdToSortOrder,
    ): void;

    /**
     * Count favorites in a dashboard
     */
    public function countForDashboardId(UuidInterface $dashboardId): int;

    /**
     * Get all dashboards where a link is favorited
     * @throws LinkNotFoundException when link doesn't exist (FK violation)
     */
    public function findDashboardsWithLinkAsFavorite(
        UuidInterface $linkId,
    ): DashboardCollection;
}
