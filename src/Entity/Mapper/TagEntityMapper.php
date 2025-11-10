<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\TagName;

final readonly class TagEntityMapper implements EntityMapperInterface
{
    use EntityMapperTrait;

    #[\Override]
    public function getFields(): array
    {
        return [
            "tag_name",
            "color",
        ];
    }

    #[\Override]
    public function supports(object $entity): bool
    {
        return $entity instanceof Tag;
    }

    #[\Override]
    private function doMapToEntity(array $data): object
    {
        return new Tag(
            tagName: new TagName($data["tag_name"]),
            color: !\is_null($data["color"])
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
