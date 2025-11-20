<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

final class Tag implements EntityEqualityInterface
{
    public function __construct(
        public readonly Value\TagName $tagName,
        public ?Value\HexColor $color,
    ) {}

    #[\Override]
    public function equals(object $entity): bool
    {
        return match (true) {
            !$entity instanceof self => false,
            default => $entity->tagName->equals($this->tagName),
        };
    }
}
