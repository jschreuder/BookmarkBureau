<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use jschreuder\BookmarkBureau\Entity\Value\ValueEqualityInterface;

final readonly class JwtToken implements ValueEqualityInterface
{
    use StringValueTrait;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
