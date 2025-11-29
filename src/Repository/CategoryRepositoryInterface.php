<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Composite\CategoryCollection;
use jschreuder\BookmarkBureau\Composite\CategoryLinkCollection;
use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
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
    public function listForDashboardId(
        UuidInterface $dashboardId,
    ): CategoryCollection;

    /**
     * Get all CategoryLink associations for a category, ordered by sort_order
     * Useful when you need the CategoryLink entity with sort_order info
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function listCategoryLinksForCategoryId(
        UuidInterface $categoryId,
    ): CategoryLinkCollection;

    /**
     * Get the highest sort_order value for categories in a dashboard
     * Returns -1 if dashboard has no categories
     */
    public function computeCategoryMaxSortOrderForDashboardId(
        UuidInterface $dashboardId,
    ): int;

    /**
     * Get the highest sort_order value for links in a category
     * Returns -1 if category has no links
     */
    public function computeLinkMaxSortOrderForCategoryId(
        UuidInterface $categoryId,
    ): int;

    /**
     * Save a new category
     * @throws DashboardNotFoundException when dashboard doesn't exist (FK violation on insert)
     */
    public function insert(Category $category): void;

    /**
     * Update existing category
     */
    public function update(Category $category): void;

    /**
     * Delete a category (cascades to category_links)
     */
    public function delete(Category $category): void;

    /**
     * Add a link to a category at specified sort order
     * @throws CategoryNotFoundException when category doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function addLink(
        UuidInterface $categoryId,
        UuidInterface $linkId,
        int $sortOrder,
    ): CategoryLink;

    /**
     * Remove a link from a category
     * @throws CategoryNotFoundException when category doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function removeLink(
        UuidInterface $categoryId,
        UuidInterface $linkId,
    ): void;

    /**
     * Check if a link is in a category
     */
    public function hasLink(
        UuidInterface $categoryId,
        UuidInterface $linkId,
    ): bool;

    /**
     * Reorder links in a category
     * The index (position) of each link in the collection becomes its sort order
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function reorderLinks(
        UuidInterface $categoryId,
        LinkCollection $links,
    ): void;

    /**
     * Count total number of categories
     */
    public function count(): int;

    /**
     * Count links in a category
     */
    public function countLinksForCategoryId(UuidInterface $categoryId): int;
}
