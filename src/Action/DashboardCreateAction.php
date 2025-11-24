<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;

/**
 * Expects the DashboardInputSpec, but it can be replaced to modify filtering
 * and validation.
 */
final readonly class DashboardCreateAction implements ActionInterface
{
    /** @param  OutputSpecInterface<Dashboard> $outputSpec */
    public function __construct(
        private DashboardServiceInterface $dashboardService,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec,
    ) {}

    #[\Override]
    public function filter(array $rawData): array
    {
        // Create operations need all fields except "id", since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), ["id"]);
        return $this->inputSpec->filter($rawData, $fields);
    }

    #[\Override]
    public function validate(array $data): void
    {
        // Create operations need all fields except "id", since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), ["id"]);
        $this->inputSpec->validate($data, $fields);
    }

    /** @param array{title: string, description: string, icon: ?string} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $dashboard = $this->dashboardService->createDashboard(
            title: $data["title"],
            description: $data["description"],
            icon: $data["icon"],
        );
        return $this->outputSpec->transform($dashboard);
    }
}
