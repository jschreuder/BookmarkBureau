<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Collection\CategoryCollection;
use jschreuder\BookmarkBureau\Collection\CategoryLinkCollection;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface CategoryRepositoryInterface
{
    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function findById(UuidInterface $categoryId): Category;

    /**
     * Get all categories for a dashboard, ordered by sort_order
     * @throws DashboardNotFoundException when dashboard doesn't exist (FK violation)
     */
    public function findByDashboardId(UuidInterface $dashboardId): CategoryCollection;

    /**
     * Get all CategoryLink associations for a category, ordered by sort_order
     * Useful when you need the CategoryLink entity with sort_order info
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function findCategoryLinksForCategoryId(UuidInterface $categoryId): CategoryLinkCollection;

    /**
     * Get the highest sort_order value for categories in a dashboard
     * Returns -1 if dashboard has no categories
     */
    public function getMaxSortOrderForDashboardId(UuidInterface $dashboardId): int;

    /**
     * Get the highest sort_order value for links in a category
     * Returns -1 if category has no links
     */
    public function getMaxSortOrderForCategoryId(UuidInterface $categoryId): int;

    /**
     * Save a new category or update existing one
     * @throws DashboardNotFoundException when dashboard doesn't exist (FK violation on insert)
     */
    public function save(Category $category): void;

    /**
     * Delete a category (cascades to category_links)
     */
    public function delete(Category $category): void;

    /**
     * Add a link to a category at specified sort order
     * @throws CategoryNotFoundException when category doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function addLink(UuidInterface $categoryId, UuidInterface $linkId, int $sortOrder): CategoryLink;

    /**
     * Remove a link from a category
     */
    public function removeLink(UuidInterface $categoryId, UuidInterface $linkId): void;

    /**
     * Check if a link is in a category
     */
    public function hasLink(UuidInterface $categoryId, UuidInterface $linkId): bool;

    /**
     * Update sort order for a link in a category
     * @throws CategoryNotFoundException when category doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function updateLinkSortOrder(UuidInterface $categoryId, UuidInterface $linkId, int $sortOrder): void;

    /**
     * Reorder links in a category
     * @param array<string, int> $linkIdToSortOrder Map of link UUID strings to sort orders
     */
    public function reorderLinks(UuidInterface $categoryId, array $linkIdToSortOrder): void;

    /**
     * Count total number of categories
     */
    public function count(): int;

    /**
     * Count links in a category
     */
    public function countLinksInCategory(UuidInterface $categoryId): int;
}
