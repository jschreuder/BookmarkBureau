<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

final class Tag
{
    public function __construct(
        public readonly string $tagName,
        public Value\HexColor $color
    ) {}
}
