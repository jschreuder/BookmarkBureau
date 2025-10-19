<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

trait StringValueTrait
{
    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}
