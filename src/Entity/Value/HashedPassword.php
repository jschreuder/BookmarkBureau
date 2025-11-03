<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

final readonly class HashedPassword
{
    public function __construct(
        private string $hash
    ) {}

    public function getHash(): string
    {
        return $this->hash;
    }
}
