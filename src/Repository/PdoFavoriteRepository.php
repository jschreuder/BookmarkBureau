<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use DateTimeImmutable;
use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Collection\DashboardCollection;
use jschreuder\BookmarkBureau\Collection\FavoriteCollection;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use Ramsey\Uuid\Uuid;

final readonly class PdoFavoriteRepository implements
    FavoriteRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly LinkRepositoryInterface $linkRepository,
    ) {}

    /**
     * Get all favorites for a dashboard, ordered by sort_order
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function findByDashboardId(
        UuidInterface $dashboardId,
    ): FavoriteCollection {
        // Verify dashboard exists and reuse it for all favorites
        $dashboard = $this->dashboardRepository->findById($dashboardId);

        $statement = $this->pdo->prepare(
            'SELECT f.*, l.* FROM favorites f
             INNER JOIN links l ON f.link_id = l.link_id
             WHERE f.dashboard_id = :dashboard_id
             ORDER BY f.sort_order ASC',
        );
        $statement->execute([":dashboard_id" => $dashboardId->getBytes()]);

        $favorites = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $favorites[] = $this->mapRowToFavorite($row, $dashboard);
        }

        return new FavoriteCollection(...$favorites);
    }

    /**
     * Get the highest sort_order value for favorites in a dashboard
     * Returns -1 if dashboard has no favorites
     */
    #[\Override]
    public function getMaxSortOrderForDashboardId(
        UuidInterface $dashboardId,
    ): int {
        $statement = $this->pdo->prepare(
            "SELECT MAX(sort_order) as max_sort FROM favorites WHERE dashboard_id = :dashboard_id",
        );
        $statement->execute([":dashboard_id" => $dashboardId->getBytes()]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $maxSort = (int) $result["max_sort"];
        return $maxSort === 0 && $result["max_sort"] === null ? -1 : $maxSort;
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
            $statement = $this->pdo->prepare(
                'INSERT INTO favorites (dashboard_id, link_id, sort_order, created_at)
                 VALUES (:dashboard_id, :link_id, :sort_order, :created_at)',
            );
            $statement->execute([
                ":dashboard_id" => $dashboardId->getBytes(),
                ":link_id" => $linkId->getBytes(),
                ":sort_order" => $sortOrder,
                ":created_at" => $now->format(SqlFormat::TIMESTAMP),
            ]);

            return new Favorite(
                dashboard: $dashboard,
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
                if (str_contains($e->getMessage(), "dashboard_id")) {
                    throw new DashboardNotFoundException(
                        "Dashboard not found: " . $dashboardId->toString(),
                    );
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
        if (!$this->isFavorite($dashboardId, $linkId)) {
            throw new FavoriteNotFoundException(
                "Favorite not found: dashboard=" .
                    $dashboardId->toString() .
                    ", link=" .
                    $linkId->toString(),
            );
        }

        $statement = $this->pdo->prepare(
            "DELETE FROM favorites WHERE dashboard_id = :dashboard_id AND link_id = :link_id",
        );
        $statement->execute([
            ":dashboard_id" => $dashboardId->getBytes(),
            ":link_id" => $linkId->getBytes(),
        ]);
    }

    /**
     * Check if a link is favorited on a dashboard
     */
    #[\Override]
    public function isFavorite(
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
     * Update sort order for a favorite
     * @throws FavoriteNotFoundException when favorite doesn't exist
     */
    #[\Override]
    public function updateSortOrder(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
        int $sortOrder,
    ): void {
        // Check if the favorite exists
        if (!$this->isFavorite($dashboardId, $linkId)) {
            throw new FavoriteNotFoundException(
                "Favorite not found: dashboard=" .
                    $dashboardId->toString() .
                    ", link=" .
                    $linkId->toString(),
            );
        }

        $statement = $this->pdo->prepare(
            'UPDATE favorites SET sort_order = :sort_order
             WHERE dashboard_id = :dashboard_id AND link_id = :link_id',
        );
        $statement->execute([
            ":dashboard_id" => $dashboardId->getBytes(),
            ":link_id" => $linkId->getBytes(),
            ":sort_order" => $sortOrder,
        ]);
    }

    /**
     * Reorder favorites in a dashboard
     * @param array<string, int> $linkIdToSortOrder Map of link UUID strings to sort orders
     */
    #[\Override]
    public function reorderFavorites(
        UuidInterface $dashboardId,
        array $linkIdToSortOrder,
    ): void {
        foreach ($linkIdToSortOrder as $linkIdString => $sortOrder) {
            $linkId = Uuid::fromString($linkIdString);
            $this->updateSortOrder($dashboardId, $linkId, $sortOrder);
        }
    }

    /**
     * Count favorites in a dashboard
     */
    #[\Override]
    public function countForDashboardId(UuidInterface $dashboardId): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) as count FROM favorites WHERE dashboard_id = :dashboard_id",
        );
        $statement->execute([":dashboard_id" => $dashboardId->getBytes()]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (int) $result["count"];
    }

    /**
     * Get all dashboards where a link is favorited
     * @throws LinkNotFoundException when link doesn't exist (FK violation)
     */
    #[\Override]
    public function findDashboardsWithLinkAsFavorite(
        UuidInterface $linkId,
    ): DashboardCollection {
        // Verify link exists
        $this->linkRepository->findById($linkId);

        $statement = $this->pdo->prepare(
            'SELECT DISTINCT d.* FROM dashboards d
             INNER JOIN favorites f ON d.dashboard_id = f.dashboard_id
             WHERE f.link_id = :link_id
             ORDER BY d.title ASC',
        );
        $statement->execute([":link_id" => $linkId->getBytes()]);

        $dashboards = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $dashboards[] = $this->mapRowToDashboard($row);
        }

        return new DashboardCollection(...$dashboards);
    }

    /**
     * Map a database row to a Favorite entity
     * When called from findByDashboardId, the dashboard is passed to avoid redundant lookups
     */
    private function mapRowToFavorite(
        array $row,
        ?Dashboard $dashboard = null,
    ): Favorite {
        // Get dashboard if not provided (fallback for other methods that construct the Favorite)
        if ($dashboard === null) {
            $dashboardId = Uuid::fromBytes($row["dashboard_id"]);
            $dashboard = $this->dashboardRepository->findById($dashboardId);
        }

        // Map link directly from the row data without hitting the repository
        $link = $this->mapRowToLink($row);

        return new Favorite(
            dashboard: $dashboard,
            link: $link,
            sortOrder: (int) $row["sort_order"],
            createdAt: new DateTimeImmutable($row["created_at"]),
        );
    }

    /**
     * Map a database row to a Link entity (helper for Favorite mapping)
     * Constructs Link directly from row data instead of calling repository
     */
    private function mapRowToLink(array $row): Link
    {
        return new Link(
            linkId: Uuid::fromBytes($row["link_id"]),
            url: new Url($row["url"]),
            title: new Title($row["title"]),
            description: $row["description"],
            icon: $row["icon"] !== null ? new Icon($row["icon"]) : null,
            createdAt: new DateTimeImmutable($row["created_at"]),
            updatedAt: new DateTimeImmutable($row["updated_at"]),
        );
    }

    /**
     * Map a database row to a Dashboard entity
     */
    private function mapRowToDashboard(array $row): Dashboard
    {
        return new Dashboard(
            dashboardId: Uuid::fromBytes($row["dashboard_id"]),
            title: new Title($row["title"]),
            description: $row["description"],
            icon: $row["icon"] !== null ? new Icon($row["icon"]) : null,
            createdAt: new DateTimeImmutable($row["created_at"]),
            updatedAt: new DateTimeImmutable($row["updated_at"]),
        );
    }
}
