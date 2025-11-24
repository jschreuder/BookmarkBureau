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
    ) {
        if ($window < 1) {
            throw new \InvalidArgumentException(
                "Window must be greater than zero",
            );
        }
    }

    #[\Override]
    public function verify(string $code, TotpSecret $secret): bool
    {
        if (empty($code)) {
            return false;
        }

        try {
            /** @var non-empty-string secretString */
            $secretString = (string) $secret;
            $totp = TOTP::create($secretString, clock: $this->clock);
            $timestamp = $this->clock->now()->getTimestamp();
            /** @var positive-int $window */
            $window = $this->window;

            return $totp->verify(
                $code,
                $timestamp > 0 ? $timestamp : null,
                $window,
            );
        } catch (\Throwable) {
            return false;
        }
    }
}
