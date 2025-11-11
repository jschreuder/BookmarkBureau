<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Util\SqlFormat;

final readonly class FavoriteEntityMapper implements EntityMapperInterface
{
    use EntityMapperTrait;

    private const array FIELDS = [
        "dashboard_id",
        "link_id",
        "sort_order",
        "created_at",
    ];

    #[\Override]
    public function getFields(): array
    {
        return self::FIELDS;
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
        /** @var Favorite $entity */
        return [
            "dashboard_id" => $entity->dashboard->dashboardId->getBytes(),
            "link_id" => $entity->link->linkId->getBytes(),
            "sort_order" => $entity->sortOrder,
            "created_at" => $entity->createdAt->format(SqlFormat::TIMESTAMP),
        ];
    }
}
