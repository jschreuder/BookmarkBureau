<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class TokenClaims implements ValueEqualityInterface
{
    public function __construct(
        public UuidInterface $userId,
        public TokenType $tokenType,
        public DateTimeInterface $issuedAt,
        public ?DateTimeInterface $expiresAt,
        public ?UuidInterface $jti = null,
    ) {}

    #[\Override]
    public function equals(object $value): bool
    {
        return match (true) {
            !$value instanceof self => false,
            default => $value->userId->equals($this->userId) &&
                $value->tokenType === $this->tokenType &&
                $value->issuedAt->getTimestamp() ===
                    $this->issuedAt->getTimestamp() &&
                $this->compareDateTimes($value->expiresAt, $this->expiresAt) &&
                $this->compareUuids($value->jti, $this->jti),
        };
    }

    private function compareDateTimes(
        ?DateTimeInterface $a,
        ?DateTimeInterface $b,
    ): bool {
        return match (true) {
            $a === null && $b === null => true,
            $a === null || $b === null => false,
            default => $a->getTimestamp() === $b->getTimestamp(),
        };
    }

    private function compareUuids(?UuidInterface $a, ?UuidInterface $b): bool
    {
        return match (true) {
            $a === null && $b === null => true,
            $a === null || $b === null => false,
            default => $a->equals($b),
        };
    }
}
