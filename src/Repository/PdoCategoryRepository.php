<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use DateTimeImmutable;
use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Collection\CategoryCollection;
use jschreuder\BookmarkBureau\Collection\CategoryLinkCollection;
use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Entity\Mapper\CategoryEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use jschreuder\BookmarkBureau\Util\SqlBuilder;
use Ramsey\Uuid\Uuid;

final readonly class PdoCategoryRepository implements
    CategoryRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly CategoryEntityMapper $categoryMapper,
        private readonly LinkEntityMapper $linkMapper,
    ) {}

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function findById(UuidInterface $categoryId): Category
    {
        $sql = SqlBuilder::buildSelect(
            "categories",
            $this->categoryMapper->getFields(),
            "category_id = :category_id LIMIT 1",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":category_id" => $categoryId->getBytes()]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw CategoryNotFoundException::forId($categoryId);
        }

        $dashboardId = Uuid::fromBytes($row["dashboard_id"]);
        $dashboard = $this->dashboardRepository->findById($dashboardId);
        $row["dashboard"] = $dashboard;

        return $this->categoryMapper->mapToEntity($row);
    }

    /**
     * Get all categories for a dashboard, ordered by sort_order
     * @throws DashboardNotFoundException when dashboard doesn't exist (FK violation)
     */
    #[\Override]
    public function findByDashboardId(
        UuidInterface $dashboardId,
    ): CategoryCollection {
        // Verify dashboard exists and reuse it for all categories
        $dashboard = $this->dashboardRepository->findById($dashboardId);

        $sql = SqlBuilder::buildSelect(
            "categories",
            $this->categoryMapper->getFields(),
            "dashboard_id = :dashboard_id",
            "sort_order ASC",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":dashboard_id" => $dashboardId->getBytes()]);

        $categories = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $row["dashboard"] = $dashboard;
            $categories[] = $this->categoryMapper->mapToEntity($row);
        }

        return new CategoryCollection(...$categories);
    }

    /**
     * Get all CategoryLink associations for a category, ordered by sort_order
     * Useful when you need the CategoryLink entity with sort_order info
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function findCategoryLinksForCategoryId(
        UuidInterface $categoryId,
    ): CategoryLinkCollection {
        // Verify category exists
        $category = $this->findById($categoryId);

        $linkFields = SqlBuilder::selectFieldsFromMapper(
            $this->linkMapper,
            "l",
        );
        $statement = $this->pdo->prepare(
            "SELECT cl.category_id, cl.link_id, cl.sort_order, cl.created_at as category_link_created_at, {$linkFields}
             FROM category_links cl
             INNER JOIN links l ON cl.link_id = l.link_id
             WHERE cl.category_id = :category_id
             ORDER BY cl.sort_order ASC",
        );
        $statement->execute([":category_id" => $categoryId->getBytes()]);

        $categoryLinks = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $link = $this->linkMapper->mapToEntity($row);
            $categoryLinks[] = new CategoryLink(
                category: $category,
                link: $link,
                sortOrder: (int) $row["sort_order"],
                createdAt: new DateTimeImmutable(
                    $row["category_link_created_at"],
                ),
            );
        }

        return new CategoryLinkCollection(...$categoryLinks);
    }

    /**
     * Get the highest sort_order value for categories in a dashboard
     * Returns -1 if dashboard has no categories
     */
    #[\Override]
    public function getMaxSortOrderForDashboardId(
        UuidInterface $dashboardId,
    ): int {
        $statement = $this->pdo->prepare(
            "SELECT MAX(sort_order) as max_sort FROM categories WHERE dashboard_id = :dashboard_id",
        );
        $statement->execute([":dashboard_id" => $dashboardId->getBytes()]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $maxSort = (int) $result["max_sort"];
        return $maxSort === 0 && $result["max_sort"] === null ? -1 : $maxSort;
    }

    /**
     * Get the highest sort_order value for links in a category
     * Returns -1 if category has no links
     */
    #[\Override]
    public function getMaxSortOrderForCategoryId(UuidInterface $categoryId): int
    {
        $statement = $this->pdo->prepare(
            "SELECT MAX(sort_order) as max_sort FROM category_links WHERE category_id = :category_id",
        );
        $statement->execute([":category_id" => $categoryId->getBytes()]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $maxSort = (int) $result["max_sort"];
        return $maxSort === 0 && $result["max_sort"] === null ? -1 : $maxSort;
    }

    /**
     * Save a new category or update existing one
     * @throws DashboardNotFoundException when dashboard doesn't exist (FK violation on insert)
     */
    #[\Override]
    public function save(Category $category): void
    {
        $row = $this->categoryMapper->mapToRow($category);
        $categoryIdBytes = $row["category_id"];

        // Check if category exists
        $check = $this->pdo->prepare(
            "SELECT 1 FROM categories WHERE category_id = :category_id LIMIT 1",
        );
        $check->execute([":category_id" => $categoryIdBytes]);

        if ($check->fetch() === false) {
            // Insert new category
            try {
                $build = SqlBuilder::buildInsert("categories", $row);
                $this->pdo->prepare($build["sql"])->execute($build["params"]);
            } catch (\PDOException $e) {
                if (
                    str_contains(
                        $e->getMessage(),
                        "FOREIGN KEY constraint failed",
                    ) ||
                    str_contains(
                        $e->getMessage(),
                        "foreign key constraint fails",
                    )
                ) {
                    throw new DashboardNotFoundException(
                        "Dashboard not found: " .
                            $category->dashboard->dashboardId->toString(),
                    );
                }
                throw $e;
            }
        } else {
            // Update existing category
            $build = SqlBuilder::buildUpdate("categories", $row, "category_id");
            $this->pdo->prepare($build["sql"])->execute($build["params"]);
        }
    }

    /**
     * Delete a category (cascades to category_links)
     */
    #[\Override]
    public function delete(Category $category): void
    {
        // Delete cascades are handled by database constraints
        $statement = $this->pdo->prepare(
            "DELETE FROM categories WHERE category_id = :category_id",
        );
        $statement->execute([
            ":category_id" => $category->categoryId->getBytes(),
        ]);
    }

    /**
     * Add a link to a category at specified sort order
     * @throws CategoryNotFoundException when category doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function addLink(
        UuidInterface $categoryId,
        UuidInterface $linkId,
        int $sortOrder,
    ): CategoryLink {
        // Verify both category and link exist
        $category = $this->findById($categoryId);
        $link = $this->linkRepository->findById($linkId);

        try {
            $now = new DateTimeImmutable();
            $statement = $this->pdo->prepare(
                'INSERT INTO category_links (category_id, link_id, sort_order, created_at)
                 VALUES (:category_id, :link_id, :sort_order, :created_at)',
            );
            $statement->execute([
                ":category_id" => $categoryId->getBytes(),
                ":link_id" => $linkId->getBytes(),
                ":sort_order" => $sortOrder,
                ":created_at" => $now->format(SqlFormat::TIMESTAMP),
            ]);

            return new CategoryLink(
                category: $category,
                link: $link,
                sortOrder: $sortOrder,
                createdAt: $now,
            );
        } catch (\PDOException $e) {
            if (
                str_contains(
                    $e->getMessage(),
                    "FOREIGN KEY constraint failed",
                ) ||
                str_contains($e->getMessage(), "foreign key constraint fails")
            ) {
                if (str_contains($e->getMessage(), "category_id")) {
                    throw CategoryNotFoundException::forId($categoryId);
                } else {
                    throw LinkNotFoundException::forId($linkId);
                }
            }
            throw $e;
        }
    }

    /**
     * Remove a link from a category
     * @throws CategoryNotFoundException when category doesn't exist
     * @throws LinkNotFoundException when link is not in category
     */
    #[\Override]
    public function removeLink(
        UuidInterface $categoryId,
        UuidInterface $linkId,
    ): void {
        // Verify both category and link exist
        $this->findById($categoryId);
        $this->linkRepository->findById($linkId);

        // Verify the link is actually in the category
        if (!$this->hasLink($categoryId, $linkId)) {
            throw LinkNotFoundException::forId($linkId);
        }

        $statement = $this->pdo->prepare(
            "DELETE FROM category_links WHERE category_id = :category_id AND link_id = :link_id",
        );
        $statement->execute([
            ":category_id" => $categoryId->getBytes(),
            ":link_id" => $linkId->getBytes(),
        ]);
    }

    /**
     * Check if a link is in a category
     */
    #[\Override]
    public function hasLink(
        UuidInterface $categoryId,
        UuidInterface $linkId,
    ): bool {
        $statement = $this->pdo->prepare(
            "SELECT 1 FROM category_links WHERE category_id = :category_id AND link_id = :link_id LIMIT 1",
        );
        $statement->execute([
            ":category_id" => $categoryId->getBytes(),
            ":link_id" => $linkId->getBytes(),
        ]);

        return $statement->fetch() !== false;
    }

    /**
     * Update sort order for a link in a category
     * @throws CategoryNotFoundException when category doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function updateLinkSortOrder(
        UuidInterface $categoryId,
        UuidInterface $linkId,
        int $sortOrder,
    ): void {
        // Verify both category and link exist
        $this->findById($categoryId);
        $this->linkRepository->findById($linkId);

        $statement = $this->pdo->prepare(
            'UPDATE category_links SET sort_order = :sort_order
             WHERE category_id = :category_id AND link_id = :link_id',
        );
        $statement->execute([
            ":category_id" => $categoryId->getBytes(),
            ":link_id" => $linkId->getBytes(),
            ":sort_order" => $sortOrder,
        ]);
    }

    /**
     * Reorder links in a category
     * The index (position) of each link in the collection becomes its sort order
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function reorderLinks(
        UuidInterface $categoryId,
        LinkCollection $links,
    ): void {
        // Verify category exists
        $this->findById($categoryId);

        $sortOrder = 0;
        foreach ($links as $link) {
            $this->updateLinkSortOrder($categoryId, $link->linkId, $sortOrder);
            $sortOrder++;
        }
    }

    /**
     * Count total number of categories
     */
    #[\Override]
    public function count(): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) as count FROM categories",
        );
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (int) $result["count"];
    }

    /**
     * Count links in a category
     */
    #[\Override]
    public function countLinksInCategory(UuidInterface $categoryId): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) as count FROM category_links WHERE category_id = :category_id",
        );
        $statement->execute([":category_id" => $categoryId->getBytes()]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (int) $result["count"];
    }
}
