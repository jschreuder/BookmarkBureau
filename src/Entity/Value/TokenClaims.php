<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class TokenClaims
{
    public function __construct(
        public UuidInterface $userId,
        public TokenType $tokenType,
        public DateTimeInterface $issuedAt,
        public ?DateTimeInterface $expiresAt,
        public ?UuidInterface $jti = null,
    ) {}

    public function isExpired(DateTimeInterface $now): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $now >= $this->expiresAt;
    }
}
