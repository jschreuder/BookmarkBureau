<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;

/**
 * Lists all dashboards without detailed data
 *
 * No input filtering or validation is needed since the action takes no input parameters.
 */
final readonly class DashboardListAction implements ActionInterface
{
    public function __construct(
        private DashboardServiceInterface $dashboardService,
        private OutputSpecInterface $outputSpec
    ) {}

    #[\Override]
    public function filter(array $rawData): array
    {
        // No input parameters needed for listing all dashboards
        return [];
    }

    #[\Override]
    public function validate(array $data): void
    {
        // No validation needed for listing all dashboards
    }

    #[\Override]
    public function execute(array $data): array
    {
        $dashboards = $this->dashboardService->listAllDashboards();

        $result = [];
        foreach ($dashboards as $dashboard) {
            $result[] = $this->outputSpec->transform($dashboard);
        }

        return ['dashboards' => $result];
    }
}
