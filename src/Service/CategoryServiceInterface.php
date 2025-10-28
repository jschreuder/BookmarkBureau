<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface CategoryServiceInterface
{
    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function getCategory(UuidInterface $categoryId): Category;

    /**
     * Create a new category in a dashboard
     *
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    public function createCategory(
        UuidInterface $dashboardId,
        string $title,
        ?string $color = null
    ): Category;

    /**
     * Update an existing category
     *
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function updateCategory(
        UuidInterface $categoryId,
        string $title,
        ?string $color = null
    ): Category;

    /**
     * Delete a category (cascades to category links)
     *
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function deleteCategory(UuidInterface $categoryId): void;

    /**
     * Reorder categories within a dashboard
     *
     * @param UuidInterface $dashboardId
     * @param array<string, int> $categoryIdToSortOrder Map of category UUID strings to sort orders
     */
    public function reorderCategories(UuidInterface $dashboardId, array $categoryIdToSortOrder): void;

    /**
     * Add a link to a category
     *
     * @throws CategoryNotFoundException when category doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function addLinkToCategory(UuidInterface $categoryId, UuidInterface $linkId): void;

    /**
     * Remove a link from a category
     *
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function removeLinkFromCategory(UuidInterface $categoryId, UuidInterface $linkId): void;

    /**
     * Reorder links within a category
     * The index (position) of each link in the collection becomes its sort order
     *
     * @param UuidInterface $categoryId
     * @param LinkCollection $links Links in the desired order
     */
    public function reorderLinksInCategory(UuidInterface $categoryId, LinkCollection $links): void;
}
