<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use DateTimeInterface;

final readonly class TokenResponse
{
    public function __construct(
        private JwtToken $token,
        private string $type,
        private ?DateTimeInterface $expiresAt,
    ) {}

    public function getToken(): JwtToken
    {
        return $this->token;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }
}
