<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

use jschreuder\BookmarkBureau\Exception\IncompleteConfigException;
use jschreuder\BookmarkBureau\Repository\JwtJtiRepositoryInterface;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\LcobucciJwtService;
use jschreuder\BookmarkBureau\Service\OtphpTotpVerifier;
use jschreuder\BookmarkBureau\Service\PasswordHasherInterface;
use jschreuder\BookmarkBureau\Service\PasswordHasherStrengthDecorator;
use jschreuder\BookmarkBureau\Service\PhpPasswordHasher;
use jschreuder\BookmarkBureau\Service\TotpVerifierInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\Clock;

final readonly class DefaultAuthConfig implements AuthConfigInterface
{
    public function __construct(
        public string $jwtSecret,
        public string $applicationName,
        public int $sessionTtl,
        public int $rememberMeTtl,
        public int $passwordMinLength = 12,
        public int $totpWindow = 1,
    ) {
        $this->validateJwtSecret();
        $this->validateWindow();
    }

    #[\Override]
    public function getJwtConfiguration(): Configuration
    {
        /** @var non-empty-string $secret */
        $secret = $this->jwtSecret;
        return Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secret),
        )->withValidationConstraints();
    }

    #[\Override]
    public function getPasswordHasher(): PasswordHasherInterface
    {
        return new PasswordHasherStrengthDecorator(
            hasher: new PhpPasswordHasher(),
            minLength: $this->passwordMinLength,
        );
    }

    #[\Override]
    public function getTotpVerifier(): TotpVerifierInterface
    {
        /** @var int<1, max> $window */
        $window = $this->totpWindow;
        return new OtphpTotpVerifier($this->getClock(), window: $window);
    }

    #[\Override]
    public function getClock(): ClockInterface
    {
        return Clock::get();
    }

    #[\Override]
    public function createJwtService(
        JwtJtiRepositoryInterface $jwtJtiRepository,
    ): JwtServiceInterface {
        return new LcobucciJwtService(
            $this->getJwtConfiguration(),
            $this->applicationName,
            $this->sessionTtl,
            $this->rememberMeTtl,
            $this->getClock(),
            $jwtJtiRepository,
        );
    }

    private function validateJwtSecret(): void
    {
        if (\strlen($this->jwtSecret) < 32) {
            throw new IncompleteConfigException(
                "JWT secret must be at least 32 bytes (256 bits) for HS256. Current length: " .
                    \strlen($this->jwtSecret) .
                    " bytes.",
            );
        }
    }

    private function validateWindow(): void
    {
        if ($this->totpWindow < 1) {
            throw new IncompleteConfigException(
                "TOTP window must be at least 1. Current value: {$this->totpWindow}.",
            );
        }
    }
}
