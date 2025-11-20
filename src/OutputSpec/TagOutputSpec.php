<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

use jschreuder\BookmarkBureau\Entity\Tag;

final readonly class TagOutputSpec implements OutputSpecInterface
{
    use OutputSpecTrait;

    #[\Override]
    public function supports(object $data): bool
    {
        return $data instanceof Tag;
    }

    /**
     * @param  Tag $tag
     */
    #[\Override]
    private function doTransform(object $tag): array
    {
        return [
            "tag_name" => $tag->tagName->value,
            "color" => $tag->color?->value,
        ];
    }
}
