<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Collection\DashboardCollection;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\View\DashboardView;
use Ramsey\Uuid\UuidInterface;

interface DashboardServiceInterface
{
    /**
     * Get complete dashboard data for rendering
     * 
     * Returns the dashboard with all categories (and their links) plus favorites
     * 
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    public function getDashboardView(UuidInterface $dashboardId): DashboardView;

    /**
     * List all dashboards (without detailed data)
     */
    public function listAllDashboards(): DashboardCollection;

    /**
     * Create a new dashboard
     */
    public function createDashboard(
        string $title,
        string $description,
        ?string $icon = null
    ): Dashboard;

    /**
     * Update an existing dashboard
     * 
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    public function updateDashboard(
        UuidInterface $dashboardId,
        string $title,
        string $description,
        ?string $icon = null
    ): Dashboard;

    /**
     * Delete a dashboard (cascades to categories and favorites)
     * 
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    public function deleteDashboard(UuidInterface $dashboardId): void;
}
