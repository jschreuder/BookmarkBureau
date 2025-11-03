<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use InvalidArgumentException;

final readonly class Email
{
    use StringValueTrait;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email Value object must get a valid e-mail address, was given: ' . $value);
        }
        $this->value = $value;
    }
}
