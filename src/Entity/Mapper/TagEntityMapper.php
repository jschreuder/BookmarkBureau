<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\TagName;

final readonly class TagEntityMapper implements EntityMapperInterface
{
    use EntityMapperTrait;

    private const array FIELDS = ["tag_name", "color"];

    #[\Override]
    public function getFields(): array
    {
        return self::FIELDS;
    }

    #[\Override]
    public function supports(object $entity): bool
    {
        return $entity instanceof Tag;
    }

    #[\Override]
    private function doMapToEntity(array $data): Tag
    {
        return new Tag(
            tagName: new TagName($data["tag_name"]),
            color: $data["color"] !== null
                ? new HexColor($data["color"])
                : null,
        );
    }

    #[\Override]
    private function doMapToRow(object $entity): array
    {
        /** @var Tag $entity */
        return [
            "tag_name" => (string) $entity->tagName,
            "color" => $entity->color?->value,
        ];
    }
}
