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
            $this->mapper->getDbFields(),
            "dashboard_id = :dashboard_id LIMIT 1",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":dashboard_id" => $dashboardId->getBytes()]);

        /** @var array{dashboard_id: string, title: string, description: string, icon: string|null, created_at: string, updated_at: string}|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw DashboardNotFoundException::forId($dashboardId);
        }

        return $this->mapper->mapToEntity($row);
    }

    /**
     * Get all dashboards ordered by title
     */
    #[\Override]
    public function listAll(): DashboardCollection
    {
        $sql = SqlBuilder::buildSelect(
            "dashboards",
            $this->mapper->getDbFields(),
            null,
            "title ASC",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute();

        $dashboards = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            /** @var array{dashboard_id: string, title: string, description: string, icon: string|null, created_at: string, updated_at: string} $row */
            $dashboards[] = $this->mapper->mapToEntity($row);
        }

        return new DashboardCollection(...$dashboards);
    }

    /**
     * Save a new dashboard
     */
    #[\Override]
    public function insert(Dashboard $dashboard): void
    {
        $row = $this->mapper->mapToRow($dashboard);
        $build = SqlBuilder::buildInsert("dashboards", $row);
        $this->pdo->prepare($build["sql"])->execute($build["params"]);
    }

    /**
     * Update existing dashboard
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function update(Dashboard $dashboard): void
    {
        $row = $this->mapper->mapToRow($dashboard);
        $build = SqlBuilder::buildUpdate("dashboards", $row, "dashboard_id");
        $statement = $this->pdo->prepare($build["sql"]);
        $statement->execute($build["params"]);

        if ($statement->rowCount() === 0) {
            throw DashboardNotFoundException::forId($dashboard->dashboardId);
        }
    }

    /**
     * Delete a dashboard (cascades to categories, favorites)
     */
    #[\Override]
    public function delete(Dashboard $dashboard): void
    {
        // Delete cascades are handled by database constraints
        $query = SqlBuilder::buildDelete("dashboards", [
            "dashboard_id" => $dashboard->dashboardId->getBytes(),
        ]);
        $this->pdo->prepare($query["sql"])->execute($query["params"]);
    }
}
