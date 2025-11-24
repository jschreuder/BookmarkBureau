<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Util\SqlFormat;

/**
 * @implements EntityMapperInterface<Category>
 */
final readonly class CategoryEntityMapper implements EntityMapperInterface
{
    /** @use EntityMapperTrait<Category> */
    use EntityMapperTrait;

    private const array FIELDS = [
        "category_id",
        "dashboard_id",
        "title",
        "color",
        "sort_order",
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
        return $entity instanceof Category;
    }

    #[\Override]
    private function doMapToEntity(array $data): Category
    {
        return new Category(
            categoryId: Uuid::fromBytes($data["category_id"]),
            dashboard: $data["dashboard"],
            title: new Title($data["title"]),
            color: $data["color"] !== null
                ? new HexColor($data["color"])
                : null,
            sortOrder: (int) $data["sort_order"],
            createdAt: new DateTimeImmutable($data["created_at"]),
            updatedAt: new DateTimeImmutable($data["updated_at"]),
        );
    }

    /** @param Category $entity */
    #[\Override]
    private function doMapToRow(object $entity): array
    {
        return [
            "category_id" => $entity->categoryId->getBytes(),
            "dashboard_id" => $entity->dashboard->dashboardId->getBytes(),
            "title" => (string) $entity->title,
            "color" => $entity->color?->value,
            "sort_order" => $entity->sortOrder,
            "created_at" => $entity->createdAt->format(SqlFormat::TIMESTAMP),
            "updated_at" => $entity->updatedAt->format(SqlFormat::TIMESTAMP),
        ];
    }
}
