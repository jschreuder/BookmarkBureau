<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use InvalidArgumentException;

final readonly class Url
{
    use StringValueTrait;

    public function __construct(
        private string $value
    )
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Url Value object must get a valid URL, was given: ' . $value);
        }
    }
}
