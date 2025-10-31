<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects the IdInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class DashboardDeleteAction implements ActionInterface
{
    public function __construct(
        private DashboardServiceInterface $dashboardService,
        private InputSpecInterface $inputSpec
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
        $dashboardId = Uuid::fromString($data['id']);
        $this->dashboardService->deleteDashboard($dashboardId);

        return [];
    }
}
