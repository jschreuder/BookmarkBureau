<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use InvalidArgumentException;

final readonly class Title
{
    use StringValueTrait;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === "") {
            throw new InvalidArgumentException("Title cannot be empty");
        }
        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException(
                "Title cannot exceed 255 characters",
            );
        }
        $this->value = $trimmed;
    }
}
