<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller\Action;

use DateTimeInterface;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use Ramsey\Uuid\Uuid;

final readonly class UpdateDashboardAction implements ActionInterface
{
    public function __construct(
        private DashboardServiceInterface $dashboardService,
        private InputSpecInterface $inputSpec
    ) {}

    public function filter(array $rawData): array
    {
        return $this->inputSpec->filter($rawData);
    }

    public function validate(array $data): void
    {
        $this->inputSpec->validate($data);
    }

    public function execute(array $data): array
    {
        $dashboardId = Uuid::fromString($data['id']);
        $dashboard = $this->dashboardService->updateDashboard(
            dashboardId: $dashboardId,
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
