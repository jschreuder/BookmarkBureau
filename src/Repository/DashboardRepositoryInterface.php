<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Composite\DashboardCollection;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface DashboardRepositoryInterface
{
    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    public function findById(UuidInterface $dashboardId): Dashboard;

    /**
     * Get all dashboards ordered by title
     */
    public function listAll(): DashboardCollection;

    /**
     * Save a new dashboard
     */
    public function insert(Dashboard $dashboard): void;

    /**
     * Update existing dashboard
     */
    public function update(Dashboard $dashboard): void;

    /**
     * Delete a dashboard (cascades to categories, favorites)
     */
    public function delete(Dashboard $dashboard): void;
}
