<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

interface ValueEqualityInterface
{
    public function equals(object $value): bool;
}
