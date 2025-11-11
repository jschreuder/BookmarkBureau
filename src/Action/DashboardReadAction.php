<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects the IdInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class DashboardReadAction implements ActionInterface
{
    public function __construct(
        private DashboardServiceInterface $dashboardService,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec,
    ) {}

    #[\Override]
    public function filter(array $rawData): array
    {
        return $this->inputSpec->filter($rawData);
    }

    #[\Override]
    public function validate(array $data): void
    {
        $this->inputSpec->validate($data);
    }

    #[\Override]
    public function execute(array $data): array
    {
        $dashboardId = Uuid::fromString($data["id"]);
        $dashboard = $this->dashboardService->getDashboard($dashboardId);
        return $this->outputSpec->transform($dashboard);
    }
}
