<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

use jschreuder\BookmarkBureau\Service\LoginRateLimitService;
use jschreuder\BookmarkBureau\Service\RateLimitServiceInterface;
use jschreuder\BookmarkBureau\Repository\PdoLoginRateLimitRepository;
use Symfony\Component\Clock\Clock;

final readonly class DefaultRateLimitConfig implements RateLimitConfigInterface
{
    public function __construct(
        public DatabaseConfigInterface $database,
        public int $usernameThreshold,
        public int $ipThreshold,
        public int $windowMinutes,
        public bool $trustProxyHeaders = false,
    ) {}

    #[\Override]
    public function createRateLimitService(): RateLimitServiceInterface
    {
        $clock = Clock::get();
        $repository = new PdoLoginRateLimitRepository(
            $this->database->getConnection(),
            $this->windowMinutes,
        );

        return new LoginRateLimitService(
            $repository,
            $clock,
            $this->usernameThreshold,
            $this->ipThreshold,
            $this->windowMinutes,
        );
    }

    public function trustProxyHeadersBool(): bool
    {
        return $this->trustProxyHeaders;
    }
}
