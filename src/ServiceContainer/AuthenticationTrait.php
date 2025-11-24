<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\ServiceContainer;

use jschreuder\BookmarkBureau\Config\AuthConfigInterface;
use jschreuder\BookmarkBureau\Config\RateLimitConfigInterface;
use jschreuder\BookmarkBureau\Repository\JwtJtiRepositoryInterface;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\PasswordHasherInterface;
use jschreuder\BookmarkBureau\Service\RateLimitServiceInterface;
use jschreuder\BookmarkBureau\Service\TotpVerifierInterface;
use Lcobucci\JWT\Configuration;
use Psr\Clock\ClockInterface;

trait AuthenticationTrait
{
    // Abstract for methods from config/ServiceContainer and RepositoryTrait
    abstract public function getAuthConfig(): AuthConfigInterface;
    abstract public function getRateLimitConfig(): RateLimitConfigInterface;
    abstract public function getJwtJtiRepository(): JwtJtiRepositoryInterface;

    public function getPasswordHasher(): PasswordHasherInterface
    {
        return $this->getAuthConfig()->getPasswordHasher();
    }

    public function getClock(): ClockInterface
    {
        return $this->getAuthConfig()->getClock();
    }

    public function getTotpVerifier(): TotpVerifierInterface
    {
        return $this->getAuthConfig()->getTotpVerifier();
    }

    public function getJwtConfiguration(): Configuration
    {
        return $this->getAuthConfig()->getJwtConfiguration();
    }

    public function getJwtService(): JwtServiceInterface
    {
        return $this->getAuthConfig()->createJwtService(
            $this->getJwtJtiRepository(),
        );
    }

    public function getRateLimitService(): RateLimitServiceInterface
    {
        return $this->getRateLimitConfig()->createRateLimitService();
    }
}
