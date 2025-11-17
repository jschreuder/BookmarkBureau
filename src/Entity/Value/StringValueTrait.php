<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

trait StringValueTrait
{
    public readonly string $value;

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(object $value): bool
    {
        return match (true) {
            !$value instanceof self => false,
            default => $value->value === $this->value,
        };
    }
}
