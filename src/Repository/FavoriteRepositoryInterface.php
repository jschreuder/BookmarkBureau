<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Collection\DashboardCollection;
use jschreuder\BookmarkBureau\Collection\FavoriteCollection;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;

interface FavoriteRepositoryInterface
{
    /**
     * Get all favorites for a dashboard, ordered by sort_order
     */
    public function findByDashboard(Dashboard $dashboard): FavoriteCollection;

    /**
     * Get the highest sort_order value for favorites in a dashboard
     * Returns -1 if dashboard has no favorites
     */
    public function getMaxSortOrderForDashboard(Dashboard $dashboard): int;

    /**
     * Add a link as favorite to a dashboard
     */
    public function addFavorite(Dashboard $dashboard, Link $link, int $sortOrder): Favorite;

    /**
     * Remove a favorite from a dashboard
     * @throws FavoriteNotFoundException when favorite doesn't exist
     */
    public function removeFavorite(Dashboard $dashboard, Link $link): void;

    /**
     * Check if a link is favorited on a dashboard
     */
    public function isFavorite(Dashboard $dashboard, Link $link): bool;

    /**
     * Update sort order for a favorite
     * @throws FavoriteNotFoundException when favorite doesn't exist
     */
    public function updateSortOrder(Dashboard $dashboard, Link $link, int $sortOrder): void;

    /**
     * Reorder favorites in a dashboard
     * @param array<string, int> $linkIdToSortOrder Map of link UUID strings to sort orders
     */
    public function reorderFavorites(Dashboard $dashboard, array $linkIdToSortOrder): void;

    /**
     * Count favorites in a dashboard
     */
    public function countForDashboard(Dashboard $dashboard): int;

    /**
     * Get all dashboards where a link is favorited
     */
    public function findDashboardsWithLinkAsFavorite(Link $link): DashboardCollection;
}
