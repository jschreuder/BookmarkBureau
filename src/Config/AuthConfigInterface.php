<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

use jschreuder\BookmarkBureau\Repository\JwtJtiRepositoryInterface;
use Lcobucci\JWT\Configuration;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\PasswordHasherInterface;
use jschreuder\BookmarkBureau\Service\TotpVerifierInterface;
use Psr\Clock\ClockInterface;

/**
 * Authentication configuration interface.
 * Implementations define JWT, password hashing, TOTP, and clock settings.
 */
interface AuthConfigInterface
{
    /**
     * Get the JWT configuration
     */
    public function getJwtConfiguration(): Configuration;

    /**
     * Get the password hasher implementation
     */
    public function getPasswordHasher(): PasswordHasherInterface;

    /**
     * Get the TOTP verifier implementation
     */
    public function getTotpVerifier(): TotpVerifierInterface;

    /**
     * Get the clock for time-based operations
     */
    public function getClock(): ClockInterface;

    /**
     * Create a JWT service with this configuration
     */
    public function createJwtService(
        JwtJtiRepositoryInterface $jwtJtiRepository,
    ): JwtServiceInterface;
}
