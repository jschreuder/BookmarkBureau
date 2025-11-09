<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class TokenClaims
{
    public function __construct(
        private UuidInterface $userId,
        private TokenType $tokenType,
        private DateTimeInterface $issuedAt,
        private ?DateTimeInterface $expiresAt,
    ) {}

    public function getUserId(): UuidInterface
    {
        return $this->userId;
    }

    public function getTokenType(): TokenType
    {
        return $this->tokenType;
    }

    public function getIssuedAt(): DateTimeInterface
    {
        return $this->issuedAt;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function isExpired(DateTimeInterface $now): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $now >= $this->expiresAt;
    }
}
