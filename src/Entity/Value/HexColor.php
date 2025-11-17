<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Entity\Value\ValueEqualityInterface;

final readonly class HexColor implements ValueEqualityInterface
{
    use StringValueTrait;

    public function __construct(string $value)
    {
        if (!preg_match("/^#[0-9a-fA-F]{6}$/", $value)) {
            throw new InvalidArgumentException(
                "HexColor Value object must be a valid HTML hex color (#RRGGBB), was given: {$value}",
            );
        }
        $this->value = $value;
    }
}
