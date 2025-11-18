<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\ServiceContainer;

use jschreuder\BookmarkBureau\Exception\IncompleteConfigException;
use jschreuder\BookmarkBureau\Repository\JwtJtiRepositoryInterface;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\LcobucciJwtService;
use jschreuder\BookmarkBureau\Service\OtphpTotpVerifier;
use jschreuder\BookmarkBureau\Service\PasswordHasherInterface;
use jschreuder\BookmarkBureau\Service\PasswordHasherStrengthDecorator;
use jschreuder\BookmarkBureau\Service\PhpPasswordHasher;
use jschreuder\BookmarkBureau\Service\RateLimitServiceInterface;
use jschreuder\BookmarkBureau\Service\LoginRateLimitService;
use jschreuder\BookmarkBureau\Service\TotpVerifierInterface;
use jschreuder\BookmarkBureau\Repository\LoginRateLimitRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\PdoLoginRateLimitRepository;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use PDO;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\Clock;

trait AuthenticationTrait
{
    // Abstract for methods from ConfigTrait, DatabaseTrait, RepositoryTrait
    abstract protected function config(string $key): mixed;
    abstract public function getRateLimitDb(): PDO;
    abstract public function getJwtJtiRepository(): JwtJtiRepositoryInterface;

    public function getPasswordHasher(): PasswordHasherInterface
    {
        return new PasswordHasherStrengthDecorator(
            hasher: new PhpPasswordHasher(),
            minLength: 12,
        );
    }

    public function getClock(): ClockInterface
    {
        return Clock::get();
    }

    public function getTotpVerifier(): TotpVerifierInterface
    {
        return new OtphpTotpVerifier($this->getClock(), window: 1);
    }

    public function getJwtConfiguration(): Configuration
    {
        $jwtSecret = $this->config("auth.jwt_secret");
        if (!$jwtSecret) {
            throw new IncompleteConfigException(
                "JWT secret is not configured. Set auth.jwt_secret in config.",
            );
        }

        // Enforce minimum key length for HS256 (256 bits = 32 bytes)
        if (\strlen($jwtSecret) < 32) {
            throw new IncompleteConfigException(
                "JWT secret must be at least 32 bytes (256 bits) for HS256. Current length: " .
                    \strlen($jwtSecret) .
                    " bytes.",
            );
        }

        return Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($jwtSecret),
        )
            // Clear default validation constraints to use only our custom ones
            ->withValidationConstraints();
    }

    public function getJwtService(): JwtServiceInterface
    {
        return new LcobucciJwtService(
            $this->getJwtConfiguration(),
            $this->config("auth.application_name"),
            $this->config("auth.session_ttl"),
            $this->config("auth.remember_me_ttl"),
            $this->getClock(),
            $this->getJwtJtiRepository(),
        );
    }

    public function getRateLimitService(): RateLimitServiceInterface
    {
        return new LoginRateLimitService(
            $this->getLoginRateLimitRepository(),
            $this->getClock(),
            $this->config("ratelimit.username_threshold"),
            $this->config("ratelimit.ip_threshold"),
            $this->config("ratelimit.window_minutes"),
        );
    }

    private function getLoginRateLimitRepository(): LoginRateLimitRepositoryInterface
    {
        return new PdoLoginRateLimitRepository(
            $this->getRateLimitDb(),
            $this->config("ratelimit.window_minutes"),
        );
    }
}
