<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\ServiceContainer;

use jschreuder\BookmarkBureau\Config\AuthConfigInterface;
use jschreuder\BookmarkBureau\Config\DatabaseConfigInterface;
use jschreuder\BookmarkBureau\Config\IpWhitelistConfigInterface;
use jschreuder\BookmarkBureau\Config\LoggerConfigInterface;
use jschreuder\BookmarkBureau\Config\RateLimitConfigInterface;
use jschreuder\BookmarkBureau\Config\UserStorageConfigInterface;

/**
 * Service container configured with typed config objects.
 * This is the wiring point for the entire application.
 * It must be non-final as Middle DI must be able to extend it.
 */
class DefaultServiceContainer
{
    use ApplicationStackTrait;
    use AuthenticationTrait;
    use DatabaseTrait;
    use RepositoryTrait;
    use ServiceTrait;

    public function __construct(
        private DatabaseConfigInterface $databaseConfig,
        private DatabaseConfigInterface $rateLimitDatabaseConfig,
        private AuthConfigInterface $authConfig,
        private LoggerConfigInterface $loggerConfig,
        private RateLimitConfigInterface $rateLimitConfig,
        private IpWhitelistConfigInterface $ipWhitelistConfig,
        private UserStorageConfigInterface $userStorageConfig,
        private string $siteUrl,
    ) {}

    public function getDatabaseConfig(): DatabaseConfigInterface
    {
        return $this->databaseConfig;
    }

    public function getRateLimitDatabaseConfig(): DatabaseConfigInterface
    {
        return $this->rateLimitDatabaseConfig;
    }

    public function getAuthConfig(): AuthConfigInterface
    {
        return $this->authConfig;
    }

    public function getLoggerConfig(): LoggerConfigInterface
    {
        return $this->loggerConfig;
    }

    public function getRateLimitConfig(): RateLimitConfigInterface
    {
        return $this->rateLimitConfig;
    }

    public function getIpWhitelistConfig(): IpWhitelistConfigInterface
    {
        return $this->ipWhitelistConfig;
    }

    public function getUserStorageConfig(): UserStorageConfigInterface
    {
        return $this->userStorageConfig;
    }

    public function siteUrlString(): string
    {
        return $this->siteUrl;
    }
}
