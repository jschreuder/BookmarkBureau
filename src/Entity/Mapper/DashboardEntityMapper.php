<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Util\SqlFormat;

/**
 * @phpstan-type DashboardEntityData array{dashboard_id: string, title: string, description: string, icon: string|null, created_at: string, updated_at: string}
 * @phpstan-type DashboardRowData array{dashboard_id: string, title: string, description: string, icon: ?string, created_at: string, updated_at: string}
 *
 * @implements EntityMapperInterface<Dashboard, DashboardEntityData, DashboardRowData>
 */
final readonly class DashboardEntityMapper implements EntityMapperInterface
{
    /** @use EntityMapperTrait<Dashboard, DashboardEntityData, DashboardRowData> */
    use EntityMapperTrait;

    private const array FIELDS = [
        "dashboard_id",
        "title",
        "description",
        "icon",
        "created_at",
        "updated_at",
    ];

    #[\Override]
    public function getFields(): array
    {
        return self::FIELDS;
    }

    #[\Override]
    public function supports(object $entity): bool
    {
        return $entity instanceof Dashboard;
    }

    #[\Override]
    private function doMapToEntity(array $data): Dashboard
    {
        return new Dashboard(
            dashboardId: Uuid::fromBytes($data["dashboard_id"]),
            title: new Title($data["title"]),
            description: $data["description"],
            icon: $data["icon"] !== null ? new Icon($data["icon"]) : null,
            createdAt: new DateTimeImmutable($data["created_at"]),
            updatedAt: new DateTimeImmutable($data["updated_at"]),
        );
    }

    #[\Override]
    private function doMapToRow(object $entity): array
    {
        return [
            "dashboard_id" => $entity->dashboardId->getBytes(),
            "title" => (string) $entity->title,
            "description" => $entity->description,
            "icon" => $entity->icon?->value,
            "created_at" => $entity->createdAt->format(SqlFormat::TIMESTAMP),
            "updated_at" => $entity->updatedAt->format(SqlFormat::TIMESTAMP),
        ];
    }
}
