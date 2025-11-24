<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Entity\Value\ValueEqualityInterface;

final readonly class JwtToken implements ValueEqualityInterface
{
    use StringValueTrait;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException("JWT token cannot be empty");
        }

        $this->value = $value;
    }
}
