<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use InvalidArgumentException;

final readonly class HexColor
{
    use StringValueTrait;

    public function __construct(
        private string $value
    )
    {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            throw new InvalidArgumentException('HexColor Value object must be a valid HTML hex color (#RRGGBB), was given: ' . $value);
        }
    }
}
