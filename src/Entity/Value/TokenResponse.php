<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use DateTimeInterface;

final readonly class TokenResponse implements ValueEqualityInterface
{
    public function __construct(
        public JwtToken $token,
        public string $type,
        public ?DateTimeInterface $expiresAt,
    ) {}

    #[\Override]
    public function equals(object $value): bool
    {
        return match (true) {
            !$value instanceof self => false,
            default => $value->token->equals($this->token) &&
                $value->type === $this->type &&
                $this->compareDateTimes($value->expiresAt, $this->expiresAt),
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
}
