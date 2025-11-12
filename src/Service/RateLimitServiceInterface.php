<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Exception\RateLimitExceededException;

interface RateLimitServiceInterface
{
    /**
     * @throws RateLimitExceededException
     */
    public function checkBlock(string $username, string $ip): void;

    public function recordFailure(string $username, string $ip): void;

    public function clearUsername(string $username): void;

    public function cleanup(): int;
}
