<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Entity\Value\ValueEqualityInterface;

final readonly class Icon implements ValueEqualityInterface
{
    use StringValueTrait;

    public function __construct(string $value)
    {
        if (trim($value) === "") {
            throw new InvalidArgumentException("Icon cannot be empty");
        }
        $this->value = $value;
    }
}
