<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Util\SqlFormat;

final readonly class CategoryLinkEntityMapper implements EntityMapperInterface
{
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
    public function supports(object $entity): bool
    {
        return $entity instanceof CategoryLink;
    }

    #[\Override]
    private function doMapToEntity(array $data): object
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
        /** @var CategoryLink $entity */
        return [
            "category_id" => $entity->category->categoryId->getBytes(),
            "link_id" => $entity->link->linkId->getBytes(),
            "sort_order" => $entity->sortOrder,
            "created_at" => $entity->createdAt->format(SqlFormat::TIMESTAMP),
        ];
    }
}
