<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use DateTimeInterface;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;

/**
 * Expects the DashboardInputSpec, but it can be replaced to modify filtering
 * and validation.
 */
final readonly class DashboardCreateAction implements ActionInterface
{
    public function __construct(
        private DashboardServiceInterface $dashboardService,
        private InputSpecInterface $inputSpec
    ) {}

    public function filter(array $rawData): array
    {
        // Create operations need all fields except 'id', since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), ['id']);
        return $this->inputSpec->filter($rawData, $fields);
    }

    public function validate(array $data): void
    {
        // Create operations need all fields except 'id', since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), ['id']);
        $this->inputSpec->validate($data, $fields);
    }

    public function execute(array $data): array
    {
        $dashboard = $this->dashboardService->createDashboard(
            title: $data['title'],
            description: $data['description'],
            icon: $data['icon']
        );
        return [
            'id' => $dashboard->dashboardId->toString(),
            'title' => $dashboard->title->value,
            'description' => $dashboard->description,
            'icon' => $dashboard->icon?->value,
            'created_at' => $dashboard->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $dashboard->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
