<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use jschreuder\BookmarkBureau\Util\SqlFormat;

/**
 * @implements EntityMapperInterface<Link>
 */
final readonly class LinkEntityMapper implements EntityMapperInterface
{
    /** @use EntityMapperTrait<Link> */
    use EntityMapperTrait;

    private const array FIELDS = [
        "link_id",
        "url",
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
        return $entity instanceof Link;
    }

    #[\Override]
    private function doMapToEntity(array $data): Link
    {
        $tags = $data["tags"] ?? new TagCollection();

        return new Link(
            linkId: Uuid::fromBytes($data["link_id"]),
            url: new Url($data["url"]),
            title: new Title($data["title"]),
            description: $data["description"],
            icon: $data["icon"] !== null ? new Icon($data["icon"]) : null,
            createdAt: new DateTimeImmutable($data["created_at"]),
            updatedAt: new DateTimeImmutable($data["updated_at"]),
            tags: $tags,
        );
    }

    /** @param Link $entity */
    #[\Override]
    private function doMapToRow(object $entity): array
    {
        return [
            "link_id" => $entity->linkId->getBytes(),
            "url" => (string) $entity->url,
            "title" => (string) $entity->title,
            "description" => $entity->description,
            "icon" => $entity->icon?->value,
            "created_at" => $entity->createdAt->format(SqlFormat::TIMESTAMP),
            "updated_at" => $entity->updatedAt->format(SqlFormat::TIMESTAMP),
        ];
    }
}
