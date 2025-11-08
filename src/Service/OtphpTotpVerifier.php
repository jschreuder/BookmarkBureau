<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use OTPHP\TOTP;
use Psr\Clock\ClockInterface;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;

final readonly class OtphpTotpVerifier implements TotpVerifierInterface
{
    public function __construct(
        private ClockInterface $clock,
        private int $window = 1,
    ) {}

    #[\Override]
    public function verify(string $code, TotpSecret $secret): bool
    {
        try {
            $totp = TOTP::create($secret->getSecret(), clock: $this->clock);
            return $totp->verify(
                $code,
                $this->clock->now()->getTimestamp(),
                $this->window,
            );
        } catch (\Exception) {
            return false;
        }
    }
}
