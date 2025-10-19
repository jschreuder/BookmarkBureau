<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

final class Tag
{
    public function __construct(
        private readonly string $tagName,
        private Value\HexColor $color
    ) {}

    public function getTagName(): string
    {
        return $this->tagName;
    }

    public function getColor(): Value\HexColor
    {
        return $this->color;
    }

    public function setColor(Value\HexColor $color): void
    {
        $this->color = $color;
    }
}
