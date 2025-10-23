<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use DateTimeImmutable;
use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Collection\DashboardCollection;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;

final readonly class PdoDashboardRepository implements DashboardRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    public function findById(UuidInterface $dashboardId): Dashboard
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM dashboards WHERE dashboard_id = :dashboard_id LIMIT 1'
        );
        $statement->execute([':dashboard_id' => $dashboardId->getBytes()]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new DashboardNotFoundException('Dashboard not found: ' . $dashboardId->toString());
        }

        return $this->mapRowToDashboard($row);
    }

    /**
     * Get all dashboards ordered by title
     */
    public function findAll(): DashboardCollection
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM dashboards ORDER BY title ASC'
        );
        $statement->execute();

        $dashboards = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $dashboards[] = $this->mapRowToDashboard($row);
        }

        return new DashboardCollection(...$dashboards);
    }

    /**
     * Save a new dashboard or update existing one
     */
    public function save(Dashboard $dashboard): void
    {
        $dashboardIdBytes = $dashboard->dashboardId->getBytes();

        // Check if dashboard exists
        $check = $this->pdo->prepare('SELECT 1 FROM dashboards WHERE dashboard_id = :dashboard_id LIMIT 1');
        $check->execute([':dashboard_id' => $dashboardIdBytes]);

        if ($check->fetch() === false) {
            // Insert new dashboard
            $statement = $this->pdo->prepare(
                'INSERT INTO dashboards (dashboard_id, title, description, icon, created_at, updated_at)
                 VALUES (:dashboard_id, :title, :description, :icon, :created_at, :updated_at)'
            );
            $statement->execute([
                ':dashboard_id' => $dashboardIdBytes,
                ':title' => (string) $dashboard->title,
                ':description' => $dashboard->description,
                ':icon' => $dashboard->icon ? (string) $dashboard->icon : null,
                ':created_at' => $dashboard->createdAt->format('Y-m-d H:i:s'),
                ':updated_at' => $dashboard->updatedAt->format('Y-m-d H:i:s'),
            ]);
        } else {
            // Update existing dashboard
            $statement = $this->pdo->prepare(
                'UPDATE dashboards
                 SET title = :title, description = :description,
                     icon = :icon, updated_at = :updated_at
                 WHERE dashboard_id = :dashboard_id'
            );
            $statement->execute([
                ':dashboard_id' => $dashboardIdBytes,
                ':title' => (string) $dashboard->title,
                ':description' => $dashboard->description,
                ':icon' => $dashboard->icon ? (string) $dashboard->icon : null,
                ':updated_at' => $dashboard->updatedAt->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Delete a dashboard (cascades to categories, favorites)
     */
    public function delete(Dashboard $dashboard): void
    {
        // Delete cascades are handled by database constraints
        $statement = $this->pdo->prepare('DELETE FROM dashboards WHERE dashboard_id = :dashboard_id');
        $statement->execute([':dashboard_id' => $dashboard->dashboardId->getBytes()]);
    }

    /**
     * Count total number of dashboards
     */
    public function count(): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) as count FROM dashboards');
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    /**
     * Map a database row to a Dashboard entity
     */
    private function mapRowToDashboard(array $row): Dashboard
    {
        return new Dashboard(
            dashboardId: \Ramsey\Uuid\Uuid::fromBytes($row['dashboard_id']),
            title: new Title($row['title']),
            description: $row['description'],
            icon: $row['icon'] !== null ? new Icon($row['icon']) : null,
            createdAt: new DateTimeImmutable($row['created_at']),
            updatedAt: new DateTimeImmutable($row['updated_at']),
        );
    }
}
