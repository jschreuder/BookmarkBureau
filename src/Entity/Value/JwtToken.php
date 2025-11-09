<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

final readonly class JwtToken implements \Stringable
{
    public function __construct(private string $token) {}

    #[\Override]
    public function __toString(): string
    {
        return $this->token;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
