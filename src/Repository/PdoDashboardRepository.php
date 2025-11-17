<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Composite\DashboardCollection;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Mapper\DashboardEntityMapper;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Util\SqlBuilder;

final readonly class PdoDashboardRepository implements
    DashboardRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DashboardEntityMapper $mapper,
    ) {}

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function findById(UuidInterface $dashboardId): Dashboard
    {
        $sql = SqlBuilder::buildSelect(
            "dashboards",
            $this->mapper->getFields(),
            "dashboard_id = :dashboard_id LIMIT 1",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":dashboard_id" => $dashboardId->getBytes()]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new DashboardNotFoundException(
                "Dashboard not found: " . $dashboardId->toString(),
            );
        }

        return $this->mapper->mapToEntity($row);
    }

    /**
     * Get all dashboards ordered by title
     */
    #[\Override]
    public function findAll(): DashboardCollection
    {
        $sql = SqlBuilder::buildSelect(
            "dashboards",
            $this->mapper->getFields(),
            null,
            "title ASC",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute();

        $dashboards = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $dashboards[] = $this->mapper->mapToEntity($row);
        }

        return new DashboardCollection(...$dashboards);
    }

    /**
     * Save a new dashboard or update existing one
     */
    #[\Override]
    public function save(Dashboard $dashboard): void
    {
        $row = $this->mapper->mapToRow($dashboard);
        $dashboardIdBytes = $row["dashboard_id"];

        // Check if dashboard exists
        $check = $this->pdo->prepare(
            "SELECT 1 FROM dashboards WHERE dashboard_id = :dashboard_id LIMIT 1",
        );
        $check->execute([":dashboard_id" => $dashboardIdBytes]);

        if ($check->fetch() === false) {
            // Insert new dashboard
            $build = SqlBuilder::buildInsert("dashboards", $row);
            $this->pdo->prepare($build["sql"])->execute($build["params"]);
        } else {
            // Update existing dashboard
            $build = SqlBuilder::buildUpdate(
                "dashboards",
                $row,
                "dashboard_id",
            );
            $this->pdo->prepare($build["sql"])->execute($build["params"]);
        }
    }

    /**
     * Delete a dashboard (cascades to categories, favorites)
     */
    #[\Override]
    public function delete(Dashboard $dashboard): void
    {
        // Delete cascades are handled by database constraints
        $statement = $this->pdo->prepare(
            "DELETE FROM dashboards WHERE dashboard_id = :dashboard_id",
        );
        $statement->execute([
            ":dashboard_id" => $dashboard->dashboardId->getBytes(),
        ]);
    }

    /**
     * Count total number of dashboards
     */
    #[\Override]
    public function count(): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) as count FROM dashboards",
        );
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (int) $result["count"];
    }
}
