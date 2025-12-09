<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use DateTimeImmutable;
use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Entity\Mapper\DashboardEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\FavoriteEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\TagEntityMapper;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use jschreuder\BookmarkBureau\Util\SqlBuilder;
use jschreuder\BookmarkBureau\Util\SqlExceptionHandler;

final readonly class PdoFavoriteRepository implements
    FavoriteRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly FavoriteEntityMapper $favoriteMapper,
        private readonly LinkEntityMapper $linkMapper,
        private readonly TagEntityMapper $tagMapper,
    ) {}

    /**
     * Get all favorites for a dashboard, ordered by sort_order
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function listForDashboardId(
        UuidInterface $dashboardId,
    ): FavoriteCollection {
        // Verify dashboard exists and reuse it for all favorites
        $dashboard = $this->dashboardRepository->findById($dashboardId);

        $favoriteFields = SqlBuilder::selectFieldsFromMapper(
            $this->favoriteMapper,
            "f",
            ["created_at" => "favorite_created_at"],
        );
        $linkFields = SqlBuilder::selectFieldsFromMapper(
            $this->linkMapper,
            "l",
            ["created_at" => "link_created_at"],
        );
        $tagFields = SqlBuilder::selectFieldsFromMapper($this->tagMapper, "t");
        $statement = $this->pdo->prepare(
            "SELECT {$favoriteFields}, {$linkFields}, {$tagFields}
             FROM favorites f
             INNER JOIN links l ON f.link_id = l.link_id
             LEFT JOIN link_tags lt ON l.link_id = lt.link_id
             LEFT JOIN tags t ON lt.tag_name = t.tag_name
             WHERE f.dashboard_id = :dashboard_id
             ORDER BY f.sort_order ASC",
        );
        $statement->execute([":dashboard_id" => $dashboardId->getBytes()]);

        /** @var array<int, array{dashboard_id: string, link_id: string, sort_order: string, favorite_created_at: string, url: string, title: string, description: string, icon: string|null, link_created_at: string, updated_at: string, tag_name: string|null, color: string|null}> $rows */
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

        // Build Favorite entities with their tags
        $favorites = [];
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

            unset($row["dashboard_id"]);
            $link = $this->linkMapper->mapToEntity([
                "link_id" => $row["link_id"],
                "url" => $row["url"],
                "title" => $row["title"],
                "description" => $row["description"],
                "icon" => $row["icon"],
                "created_at" => $row["link_created_at"],
                "updated_at" => $row["updated_at"],
                "tags" => new TagCollection(...$tags),
            ]);

            $favorites[] = $this->favoriteMapper->mapToEntity([
                "dashboard" => $dashboard,
                "link" => $link,
                "sort_order" => $row["sort_order"],
                "created_at" => $row["favorite_created_at"],
            ]);
        }

        return new FavoriteCollection(...$favorites);
    }

    /**
     * Get the highest sort_order value for favorites in a dashboard
     * Returns -1 if dashboard has no favorites
     */
    #[\Override]
    public function computeMaxSortOrderForDashboardId(
        UuidInterface $dashboardId,
    ): int {
        $sql = SqlBuilder::buildMax(
            "favorites",
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
     * Add a link as favorite to a dashboard
     * @throws DashboardNotFoundException when dashboard doesn't exist (FK violation)
     * @throws LinkNotFoundException when link doesn't exist (FK violation)
     */
    #[\Override]
    public function addFavorite(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
        int $sortOrder,
    ): Favorite {
        // Verify both dashboard and link exist
        $dashboard = $this->dashboardRepository->findById($dashboardId);
        $link = $this->linkRepository->findById($linkId);

        try {
            $now = new DateTimeImmutable();
            $query = SqlBuilder::buildInsert("favorites", [
                "dashboard_id" => $dashboardId->getBytes(),
                "link_id" => $linkId->getBytes(),
                "sort_order" => $sortOrder,
                "created_at" => $now->format(SqlFormat::TIMESTAMP),
            ]);
            $this->pdo->prepare($query["sql"])->execute($query["params"]);

            return new Favorite(
                dashboard: $dashboard,
                link: $link,
                sortOrder: $sortOrder,
                createdAt: $now,
            );
        } catch (\PDOException $e) {
            if (SqlExceptionHandler::isForeignKeyViolation($e)) {
                $field = SqlExceptionHandler::getForeignKeyField($e);
                if ($field === "dashboard_id") {
                    throw DashboardNotFoundException::forId($dashboardId);
                } else {
                    throw LinkNotFoundException::forId($linkId);
                }
            }
            throw $e;
        }
    }

    /**
     * Remove a favorite from a dashboard
     * @throws FavoriteNotFoundException when favorite doesn't exist
     */
    #[\Override]
    public function removeFavorite(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
    ): void {
        // Check if the favorite exists
        if (!$this->hasLinkAsFavorite($dashboardId, $linkId)) {
            throw FavoriteNotFoundException::forDashboardAndLink(
                $dashboardId,
                $linkId,
            );
        }

        $query = SqlBuilder::buildDelete("favorites", [
            "dashboard_id" => $dashboardId->getBytes(),
            "link_id" => $linkId->getBytes(),
        ]);
        $this->pdo->prepare($query["sql"])->execute($query["params"]);
    }

    /**
     * Check if a link is favorited on a dashboard
     */
    #[\Override]
    public function hasLinkAsFavorite(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
    ): bool {
        $statement = $this->pdo->prepare(
            "SELECT 1 FROM favorites WHERE dashboard_id = :dashboard_id AND link_id = :link_id LIMIT 1",
        );
        $statement->execute([
            ":dashboard_id" => $dashboardId->getBytes(),
            ":link_id" => $linkId->getBytes(),
        ]);

        return $statement->fetch() !== false;
    }

    /**
     * Reorder favorites in a dashboard
     * The index (position) of each favorite in the collection becomes its sort order
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function reorderFavorites(
        UuidInterface $dashboardId,
        FavoriteCollection $favorites,
    ): void {
        // Verify dashboard exists
        $this->dashboardRepository->findById($dashboardId);

        $sortOrder = 0;
        foreach ($favorites as $favorite) {
            $this->updateSortOrder(
                $dashboardId,
                $favorite->link->linkId,
                $sortOrder,
            );
            $sortOrder++;
        }
    }

    /**
     * Update sort order for a favorite
     * @throws FavoriteNotFoundException when favorite doesn't exist
     */
    private function updateSortOrder(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
        int $sortOrder,
    ): void {
        // Check if the favorite exists
        if (!$this->hasLinkAsFavorite($dashboardId, $linkId)) {
            throw FavoriteNotFoundException::forDashboardAndLink(
                $dashboardId,
                $linkId,
            );
        }

        $query = SqlBuilder::buildUpdate(
            "favorites",
            [
                "dashboard_id" => $dashboardId->getBytes(),
                "link_id" => $linkId->getBytes(),
                "sort_order" => $sortOrder,
            ],
            ["dashboard_id", "link_id"],
        );
        $this->pdo->prepare($query["sql"])->execute($query["params"]);
    }
}
