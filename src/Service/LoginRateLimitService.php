<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Exception\RateLimitExceededException;
use jschreuder\BookmarkBureau\Repository\LoginRateLimitRepositoryInterface;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use Psr\Clock\ClockInterface;

final readonly class LoginRateLimitService implements RateLimitServiceInterface
{
    public function __construct(
        private LoginRateLimitRepositoryInterface $repository,
        private ClockInterface $clock,
        private int $usernameThreshold = 10,
        private int $ipThreshold = 100,
        private int $windowMinutes = 10,
    ) {}

    /**
     * @throws RateLimitExceededException
     */
    #[\Override]
    public function checkBlock(string $username, string $ip): void
    {
        $now = $this->clock->now();
        $nowStr = $now->format(SqlFormat::TIMESTAMP);
        $blockInfo = $this->repository->getBlockInfo($username, $ip, $nowStr);

        if ($blockInfo["blocked"]) {
            $expiresAt = $blockInfo["expires_at"]
                ? new \DateTimeImmutable($blockInfo["expires_at"])
                : null;

            throw new RateLimitExceededException(
                $blockInfo["username"],
                $blockInfo["ip"],
                $expiresAt,
            );
        }
    }

    #[\Override]
    public function recordFailure(string $username, string $ip): void
    {
        $now = $this->clock->now();
        $nowStr = $now->format(SqlFormat::TIMESTAMP);

        // Record failure
        $this->repository->insertFailedAttempt($username, $ip, $nowStr);

        // Count attempts (repository calculates window internally)
        $counts = $this->repository->countAttempts($username, $ip, $nowStr);

        // Add blocks if over threshold
        $expiresAt = $now
            ->modify("+{$this->windowMinutes} minutes")
            ->format(SqlFormat::TIMESTAMP);

        if ($counts["user_count"] > $this->usernameThreshold) {
            $this->repository->insertBlock($username, null, $expiresAt);
        }

        if ($counts["ip_count"] > $this->ipThreshold) {
            $this->repository->insertBlock(null, $ip, $expiresAt);
        }
    }

    #[\Override]
    public function clearUsername(string $username): void
    {
        $this->repository->clearUsernameFromAttempts($username);
    }

    #[\Override]
    public function cleanup(): int
    {
        $now = $this->clock->now()->format(SqlFormat::TIMESTAMP);
        return $this->repository->deleteExpired($now);
    }
}
