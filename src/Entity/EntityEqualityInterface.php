<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

interface EntityEqualityInterface
{
    public function equals(object $entity): bool;
}
