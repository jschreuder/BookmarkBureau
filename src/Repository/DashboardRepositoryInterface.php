<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Collection\DashboardCollection;
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
    public function findAll(): DashboardCollection;

    /**
     * Save a new dashboard or update existing one
     */
    public function save(Dashboard $dashboard): void;

    /**
     * Delete a dashboard (cascades to categories, favorites)
     */
    public function delete(Dashboard $dashboard): void;

    /**
     * Count total number of dashboards
     */
    public function count(): int;
}
