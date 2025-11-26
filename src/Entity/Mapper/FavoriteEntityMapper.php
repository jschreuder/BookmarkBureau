<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Util\SqlFormat;

/**
 * @phpstan-type FavoriteEntityData array{dashboard: Dashboard, link: Link, sort_order: string, created_at: string}
 * @phpstan-type FavoriteRowData array{dashboard_id: string, link_id: string, sort_order: string, created_at: string}
 *
 * @implements EntityMapperInterface<Favorite, FavoriteEntityData, FavoriteRowData>
 */
final readonly class FavoriteEntityMapper implements EntityMapperInterface
{
    /** @use EntityMapperTrait<Favorite, FavoriteEntityData, FavoriteRowData> */
    use EntityMapperTrait;

    private const array FIELDS = [
        "dashboard",
        "link",
        "sort_order",
        "created_at",
    ];

    #[\Override]
    public function getFields(): array
    {
        return self::FIELDS;
    }

    #[\Override]
    public function getDbFields(): array
    {
        return $this->replaceField(
            $this->replaceField(
                $this->getFields(),
                "dashboard",
                "dashboard_id",
            ),
            "link",
            "link_id",
        );
    }

    #[\Override]
    public function supports(object $entity): bool
    {
        return $entity instanceof Favorite;
    }

    #[\Override]
    private function doMapToEntity(array $data): Favorite
    {
        return new Favorite(
            dashboard: $data["dashboard"],
            link: $data["link"],
            sortOrder: (int) $data["sort_order"],
            createdAt: new DateTimeImmutable($data["created_at"]),
        );
    }

    #[\Override]
    private function doMapToRow(object $entity): array
    {
        return [
            "dashboard_id" => $entity->dashboard->dashboardId->getBytes(),
            "link_id" => $entity->link->linkId->getBytes(),
            "sort_order" => (string) $entity->sortOrder,
            "created_at" => $entity->createdAt->format(SqlFormat::TIMESTAMP),
        ];
    }
}
