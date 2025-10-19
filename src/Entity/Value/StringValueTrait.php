<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

trait StringValueTrait
{
    public readonly string $value;

    public function __toString(): string
    {
        return $this->value;
    }
}
