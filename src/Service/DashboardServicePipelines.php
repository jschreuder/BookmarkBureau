<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;

final readonly class DashboardServicePipelines
{
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $getDashboard = null,
        private ?PipelineInterface $getFullDashboard = null,
        private ?PipelineInterface $listAllDashboards = null,
        private ?PipelineInterface $createDashboard = null,
        private ?PipelineInterface $updateDashboard = null,
        private ?PipelineInterface $deleteDashboard = null,
    ) {}

    public function getDashboard(): PipelineInterface
    {
        return $this->getDashboard ?? $this->default;
    }

    public function getFullDashboard(): PipelineInterface
    {
        return $this->getFullDashboard ?? $this->default;
    }

    public function listAllDashboards(): PipelineInterface
    {
        return $this->listAllDashboards ?? $this->default;
    }

    public function createDashboard(): PipelineInterface
    {
        return $this->createDashboard ?? $this->default;
    }

    public function updateDashboard(): PipelineInterface
    {
        return $this->updateDashboard ?? $this->default;
    }

    public function deleteDashboard(): PipelineInterface
    {
        return $this->deleteDashboard ?? $this->default;
    }
}
