<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

final readonly class HashedPassword
{
    use StringValueTrait;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
