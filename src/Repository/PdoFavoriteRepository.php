<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use DateTimeImmutable;
use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Composite\DashboardCollection;
use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Entity\Mapper\DashboardEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\FavoriteEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use jschreuder\BookmarkBureau\Util\SqlBuilder;
use Ramsey\Uuid\Uuid;

final readonly class PdoFavoriteRepository implements
    FavoriteRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly FavoriteEntityMapper $favoriteMapper,
        private readonly DashboardEntityMapper $dashboardMapper,
        private readonly LinkEntityMapper $linkMapper,
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

        $favoriteFields = SqlBuilder::selectFieldsFromMapper(
            $this->favoriteMapper,
            "f",
        );
        $linkFields = SqlBuilder::selectFieldsFromMapper(
            $this->linkMapper,
            "l",
        );
        $statement = $this->pdo->prepare(
            "SELECT " .
                $favoriteFields .
                ", " .
                $linkFields .
                ' FROM favorites f
             INNER JOIN links l ON f.link_id = l.link_id
             WHERE f.dashboard_id = :dashboard_id
             ORDER BY f.sort_order ASC',
        );
        $statement->execute([":dashboard_id" => $dashboardId->getBytes()]);

        $favorites = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $row["dashboard"] = $dashboard;
            $row["link"] = $this->linkMapper->mapToEntity($row);
            $favorites[] = $this->favoriteMapper->mapToEntity($row);
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

        $dashboardFields = SqlBuilder::selectFieldsFromMapper(
            $this->dashboardMapper,
            "d",
        );
        $statement = $this->pdo->prepare(
            "SELECT DISTINCT " .
                $dashboardFields .
                ' FROM dashboards d
             INNER JOIN favorites f ON d.dashboard_id = f.dashboard_id
             WHERE f.link_id = :link_id
             ORDER BY d.title ASC',
        );
        $statement->execute([":link_id" => $linkId->getBytes()]);

        $dashboards = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $dashboards[] = $this->dashboardMapper->mapToEntity($row);
        }

        return new DashboardCollection(...$dashboards);
    }
}
