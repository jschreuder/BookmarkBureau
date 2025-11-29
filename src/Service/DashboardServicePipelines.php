<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Composite\DashboardCollection;
use jschreuder\BookmarkBureau\Composite\DashboardWithCategoriesAndFavorites;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class DashboardServicePipelines
{
    /**
     * @param PipelineInterface<UuidInterface, Dashboard>|null $getDashboard
     * @param PipelineInterface<UuidInterface, DashboardWithCategoriesAndFavorites>|null $getFullDashboard
     * @param PipelineInterface<null, DashboardCollection>|null $getAllDashboards
     * @param PipelineInterface<Dashboard, Dashboard>|null $createDashboard
     * @param PipelineInterface<Dashboard, Dashboard>|null $updateDashboard
     * @param PipelineInterface<Dashboard, null>|null $deleteDashboard
     */
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $getDashboard = null,
        private ?PipelineInterface $getFullDashboard = null,
        private ?PipelineInterface $getAllDashboards = null,
        private ?PipelineInterface $createDashboard = null,
        private ?PipelineInterface $updateDashboard = null,
        private ?PipelineInterface $deleteDashboard = null,
    ) {}

    /** @return PipelineInterface<UuidInterface, Dashboard> */
    public function getDashboard(): PipelineInterface
    {
        return $this->getDashboard ?? $this->default;
    }

    /** @return PipelineInterface<UuidInterface, DashboardWithCategoriesAndFavorites> */
    public function getFullDashboard(): PipelineInterface
    {
        return $this->getFullDashboard ?? $this->default;
    }

    /** @return PipelineInterface<null, DashboardCollection> */
    public function getAllDashboards(): PipelineInterface
    {
        return $this->getAllDashboards ?? $this->default;
    }

    /** @return PipelineInterface<Dashboard, Dashboard> */
    public function createDashboard(): PipelineInterface
    {
        return $this->createDashboard ?? $this->default;
    }

    /** @return PipelineInterface<Dashboard, Dashboard> */
    public function updateDashboard(): PipelineInterface
    {
        return $this->updateDashboard ?? $this->default;
    }

    /** @return PipelineInterface<Dashboard, null> */
    public function deleteDashboard(): PipelineInterface
    {
        return $this->deleteDashboard ?? $this->default;
    }
}
