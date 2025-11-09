<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use DateTimeInterface;

final readonly class TokenResponse
{
    public function __construct(
        public JwtToken $token,
        public string $type,
        public ?DateTimeInterface $expiresAt,
    ) {}
}
