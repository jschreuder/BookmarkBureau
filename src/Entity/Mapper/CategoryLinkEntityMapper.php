<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Util\SqlFormat;

/**
 * @phpstan-type CategoryLinkEntityData array{category: Category, link: Link, sort_order: string, created_at: string}
 * @phpstan-type CategoryLinkRowData array{category_id: string, link_id: string, sort_order: string, created_at: string}
 *
 * @implements EntityMapperInterface<CategoryLink, CategoryLinkEntityData, CategoryLinkRowData>
 */
final readonly class CategoryLinkEntityMapper implements EntityMapperInterface
{
    /** @use EntityMapperTrait<CategoryLink, CategoryLinkEntityData, CategoryLinkRowData> */
    use EntityMapperTrait;

    private const array FIELDS = [
        "category_id",
        "link_id",
        "sort_order",
        "created_at",
        "category",
        "link",
    ];

    #[\Override]
    public function getFields(): array
    {
        return self::FIELDS;
    }

    #[\Override]
    public function getDbFields(): array
    {
        return $this->getFields();
    }

    #[\Override]
    public function supports(object $entity): bool
    {
        return $entity instanceof CategoryLink;
    }

    #[\Override]
    private function doMapToEntity(array $data): CategoryLink
    {
        return new CategoryLink(
            category: $data["category"],
            link: $data["link"],
            sortOrder: (int) $data["sort_order"],
            createdAt: new DateTimeImmutable($data["created_at"]),
        );
    }

    #[\Override]
    private function doMapToRow(object $entity): array
    {
        return [
            "category_id" => $entity->category->categoryId->getBytes(),
            "link_id" => $entity->link->linkId->getBytes(),
            "sort_order" => (string) $entity->sortOrder,
            "created_at" => $entity->createdAt->format(SqlFormat::TIMESTAMP),
        ];
    }
}
