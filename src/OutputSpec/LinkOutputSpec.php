<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

use DateTimeInterface;
use jschreuder\BookmarkBureau\Entity\Link;

final readonly class LinkOutputSpec implements OutputSpecInterface
{
    use OutputSpecTrait;

    public function __construct(
        private readonly TagOutputSpec $tagOutputSpec,
    ) {}

    #[\Override]
    public function supports(object $data): bool
    {
        return $data instanceof Link;
    }

    /**
     * @param  Link $link
     */
    #[\Override]
    private function doTransform(object $link): array
    {
        return [
            "id" => $link->linkId->toString(),
            "url" => $link->url->value,
            "title" => $link->title->value,
            "description" => $link->description,
            "icon" => $link->icon?->value,
            "created_at" => $link->createdAt->format(DateTimeInterface::ATOM),
            "updated_at" => $link->updatedAt->format(DateTimeInterface::ATOM),
            "tags" => array_map(
                fn($tag) => $this->tagOutputSpec->transform($tag),
                iterator_to_array($link->tags),
            ),
        ];
    }
}
