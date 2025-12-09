<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use DateTimeImmutable;
use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Composite\CategoryCollection;
use jschreuder\BookmarkBureau\Composite\CategoryLinkCollection;
use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Entity\Mapper\CategoryEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\TagEntityMapper;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use jschreuder\BookmarkBureau\Util\SqlBuilder;
use jschreuder\BookmarkBureau\Util\SqlExceptionHandler;
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
        private readonly TagEntityMapper $tagMapper,
    ) {}

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function findById(UuidInterface $categoryId): Category
    {
        $sql = SqlBuilder::buildSelect(
            "categories",
            $this->categoryMapper->getDbFields(),
            "category_id = :category_id LIMIT 1",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":category_id" => $categoryId->getBytes()]);

        /** @var array{category_id: string, dashboard_id: string, title: string, color: string|null, sort_order: string, created_at: string, updated_at: string}|false $row */
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
    public function listForDashboardId(
        UuidInterface $dashboardId,
    ): CategoryCollection {
        // Verify dashboard exists and reuse it for all categories
        $dashboard = $this->dashboardRepository->findById($dashboardId);

        $sql = SqlBuilder::buildSelect(
            "categories",
            $this->categoryMapper->getDbFields(),
            "dashboard_id = :dashboard_id",
            "sort_order ASC",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":dashboard_id" => $dashboardId->getBytes()]);

        $categories = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            /** @var array{category_id: string, dashboard_id: string, title: string, color: string|null, sort_order: string, created_at: string, updated_at: string} $row */
            unset($row["dashboard_id"]);
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
    public function listCategoryLinksForCategoryId(
        UuidInterface $categoryId,
    ): CategoryLinkCollection {
        // Verify category exists
        $category = $this->findById($categoryId);

        $linkFields = SqlBuilder::selectFieldsFromMapper(
            $this->linkMapper,
            "l",
        );
        $tagFields = SqlBuilder::selectFieldsFromMapper($this->tagMapper, "t");
        $statement = $this->pdo->prepare(
            "SELECT cl.category_id, cl.link_id, cl.sort_order, cl.created_at as category_link_created_at, {$linkFields}, {$tagFields}
             FROM category_links cl
             INNER JOIN links l ON cl.link_id = l.link_id
             LEFT JOIN link_tags lt ON l.link_id = lt.link_id
             LEFT JOIN tags t ON lt.tag_name = t.tag_name
             WHERE cl.category_id = :category_id
             ORDER BY cl.sort_order ASC",
        );
        $statement->execute([":category_id" => $categoryId->getBytes()]);

        /** @var array<int, array{category_id: string, link_id: string, sort_order: string, category_link_created_at: string, url: string, title: string, icon: string|null, description: string, created_at: string, updated_at: string, tag_name: string|null, color: string|null}> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Group rows by link_id to handle multiple tags per link
        $groupedByLink = [];
        foreach ($rows as $row) {
            $linkId = $row["link_id"];
            if (!isset($groupedByLink[$linkId])) {
                $groupedByLink[$linkId] = [
                    "linkRow" => $row,
                    "tagRows" => [],
                ];
            }
            // Only collect tag data if a tag was actually joined
            if ($row["tag_name"] !== null) {
                $groupedByLink[$linkId]["tagRows"][] = $row;
            }
        }

        // Build CategoryLink entities with their tags
        $categoryLinks = [];
        foreach ($groupedByLink as $group) {
            $row = $group["linkRow"];
            $tagRows = $group["tagRows"];

            // Map tags to Tag entities
            $tags = [];
            foreach ($tagRows as $tagRow) {
                $tags[] = $this->tagMapper->mapToEntity([
                    "tag_name" => $tagRow["tag_name"],
                    "color" => $tagRow["color"],
                ]);
            }

            $link = $this->linkMapper->mapToEntity([
                "link_id" => $row["link_id"],
                "url" => $row["url"],
                "title" => $row["title"],
                "description" => $row["description"],
                "icon" => $row["icon"],
                "created_at" => $row["created_at"],
                "updated_at" => $row["updated_at"],
                "tags" => new TagCollection(...$tags),
            ]);

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
    public function computeCategoryMaxSortOrderForDashboardId(
        UuidInterface $dashboardId,
    ): int {
        $sql = SqlBuilder::buildMax(
            "categories",
            "sort_order",
            "dashboard_id = :dashboard_id",
            "max_sort",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":dashboard_id" => $dashboardId->getBytes()]);

        /** @var array<string, mixed>|false $result */
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return SqlBuilder::extractMaxValue($result, "max_sort");
    }

    /**
     * Get the highest sort_order value for links in a category
     * Returns -1 if category has no links
     */
    #[\Override]
    public function computeLinkMaxSortOrderForCategoryId(
        UuidInterface $categoryId,
    ): int {
        $sql = SqlBuilder::buildMax(
            "category_links",
            "sort_order",
            "category_id = :category_id",
            "max_sort",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":category_id" => $categoryId->getBytes()]);

        /** @var array<string, mixed>|false $result */
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return SqlBuilder::extractMaxValue($result, "max_sort");
    }

    /**
     * Save a new category
     * @throws DashboardNotFoundException when dashboard doesn't exist (FK violation on insert)
     */
    #[\Override]
    public function insert(Category $category): void
    {
        try {
            $row = $this->categoryMapper->mapToRow($category);
            $query = SqlBuilder::buildInsert("categories", $row);
            $this->pdo->prepare($query["sql"])->execute($query["params"]);
        } catch (\PDOException $e) {
            if (SqlExceptionHandler::isForeignKeyViolation($e)) {
                throw DashboardNotFoundException::forId(
                    $category->dashboard->dashboardId,
                );
            }
            throw $e;
        }
    }

    /**
     * Update existing category
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function update(Category $category): void
    {
        $row = $this->categoryMapper->mapToRow($category);
        $query = SqlBuilder::buildUpdate("categories", $row, "category_id");
        $statement = $this->pdo->prepare($query["sql"]);
        $statement->execute($query["params"]);

        if ($statement->rowCount() === 0) {
            throw CategoryNotFoundException::forId($category->categoryId);
        }
    }

    /**
     * Delete a category (cascades to category_links)
     */
    #[\Override]
    public function delete(Category $category): void
    {
        // Delete cascades are handled by database constraints
        $query = SqlBuilder::buildDelete("categories", [
            "category_id" => $category->categoryId->getBytes(),
        ]);
        $this->pdo->prepare($query["sql"])->execute($query["params"]);
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
            $query = SqlBuilder::buildInsert("category_links", [
                "category_id" => $categoryId->getBytes(),
                "link_id" => $linkId->getBytes(),
                "sort_order" => $sortOrder,
                "created_at" => $now->format(SqlFormat::TIMESTAMP),
            ]);
            $this->pdo->prepare($query["sql"])->execute($query["params"]);

            return new CategoryLink(
                category: $category,
                link: $link,
                sortOrder: $sortOrder,
                createdAt: $now,
            );
        } catch (\PDOException $e) {
            if (SqlExceptionHandler::isForeignKeyViolation($e)) {
                $field = SqlExceptionHandler::getForeignKeyField($e);
                if ($field === "category_id") {
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

        $query = SqlBuilder::buildDelete("category_links", [
            "category_id" => $categoryId->getBytes(),
            "link_id" => $linkId->getBytes(),
        ]);
        $this->pdo->prepare($query["sql"])->execute($query["params"]);
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
     * Reorder categories in a dashboard
     * The index (position) of each category in the collection becomes its sort order
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function reorderCategories(
        UuidInterface $dashboardId,
        CategoryCollection $categories,
    ): void {
        // Verify dashboard exists
        $this->dashboardRepository->findById($dashboardId);

        $sortOrder = 0;
        foreach ($categories as $category) {
            $this->updateCategorySortOrder($category, $sortOrder);
            $sortOrder++;
        }
    }

    /**
     * Update sort order for a category
     */
    private function updateCategorySortOrder(
        Category $category,
        int $sortOrder,
    ): void {
        $query = SqlBuilder::buildUpdate(
            "categories",
            [
                "category_id" => $category->categoryId->getBytes(),
                "sort_order" => $sortOrder,
            ],
            ["category_id"],
        );
        $this->pdo->prepare($query["sql"])->execute($query["params"]);
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
     * Update sort order for a link in a category
     * @throws CategoryNotFoundException when category doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    private function updateLinkSortOrder(
        UuidInterface $categoryId,
        UuidInterface $linkId,
        int $sortOrder,
    ): void {
        // Verify both category and link exist
        $this->findById($categoryId);
        $this->linkRepository->findById($linkId);

        $query = SqlBuilder::buildUpdate(
            "category_links",
            [
                "category_id" => $categoryId->getBytes(),
                "link_id" => $linkId->getBytes(),
                "sort_order" => $sortOrder,
            ],
            ["category_id", "link_id"],
        );
        $this->pdo->prepare($query["sql"])->execute($query["params"]);
    }
}
