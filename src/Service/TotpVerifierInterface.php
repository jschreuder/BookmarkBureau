<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;

interface TotpVerifierInterface
{
    /**
     * Verify a TOTP code against a secret.
     *
     * @param string $code The 6-digit (or similar) TOTP code provided by the user
     * @param TotpSecret $secret The TOTP secret stored for the user
     * @return bool True if the code is valid within the time window
     */
    public function verify(string $code, TotpSecret $secret): bool;
}
