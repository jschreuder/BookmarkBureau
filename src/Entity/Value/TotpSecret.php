<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use InvalidArgumentException;

final readonly class TotpSecret
{
    /**
     * TOTP secrets are Base32-encoded strings (RFC 4648).
     * Industry standard: 16-32 characters from [A-Z2-7] alphabet.
     */
    public function __construct(
        private string $secret
    ) {
        // Validate Base32 format (RFC 4648) - uppercase A-Z, 2-7
        if (!preg_match('/^[A-Z2-7]{16,}$/i', $secret)) {
            throw new InvalidArgumentException(
                'TOTP secret must be Base32 encoded (min 16 chars, alphabet A-Z2-7)'
            );
        }
    }

    public function getSecret(): string
    {
        return strtoupper($this->secret);
    }
}
