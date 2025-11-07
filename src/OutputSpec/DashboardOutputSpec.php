<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

use DateTimeInterface;
use jschreuder\BookmarkBureau\Entity\Dashboard;

final readonly class DashboardOutputSpec implements OutputSpecInterface
{
    use OutputSpecTrait;

    #[\Override]
    public function supports(object $data): bool
    {
        return $data instanceof Dashboard;
    }

    /**
     * @param  Dashboard $dashboard
     */
    private function doTransform(object $dashboard): array
    {
        return [
            "id" => $dashboard->dashboardId->toString(),
            "title" => $dashboard->title->value,
            "description" => $dashboard->description,
            "icon" => $dashboard->icon?->value,
            "created_at" => $dashboard->createdAt->format(
                DateTimeInterface::ATOM,
            ),
            "updated_at" => $dashboard->updatedAt->format(
                DateTimeInterface::ATOM,
            ),
        ];
    }
}
