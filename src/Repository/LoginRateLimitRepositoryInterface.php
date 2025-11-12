<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

interface LoginRateLimitRepositoryInterface
{
    /**
     * @return array{blocked: bool, username: ?string, ip: ?string, expires_at: ?string}
     */
    public function getBlockInfo(
        string $username,
        string $ip,
        string $now,
    ): array;

    public function insertFailedAttempt(
        string $username,
        string $ip,
        string $timestamp,
    ): void;

    /**
     * @return array{user_count: int, ip_count: int}
     */
    public function countAttempts(
        string $username,
        string $ip,
        string $now,
    ): array;

    public function insertBlock(
        ?string $username,
        ?string $ip,
        string $expiresAt,
    ): void;

    public function clearUsernameFromAttempts(string $username): void;

    public function deleteExpired(string $now): int;
}
