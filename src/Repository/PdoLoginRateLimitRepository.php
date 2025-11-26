<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use PDO;

final readonly class PdoLoginRateLimitRepository implements
    LoginRateLimitRepositoryInterface
{
    public function __construct(
        private PDO $connection,
        private int $windowMinutes = 10,
    ) {}

    #[\Override]
    public function getBlockInfo(
        string $username,
        string $ip,
        string $now,
    ): array {
        $stmt = $this->connection->prepare("
            SELECT username, ip, expires_at
            FROM login_blocks
            WHERE (username = ? OR ip = ?)
              AND expires_at > ?
            ORDER BY expires_at DESC
            LIMIT 1
        ");
        $stmt->execute([$username, $ip, $now]);
        /** @var array{username: string, ip: string, expires_at: string}|false $result */
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return [
                "blocked" => false,
                "username" => null,
                "ip" => null,
                "expires_at" => null,
            ];
        }

        return [
            "blocked" => true,
            "username" => $result["username"],
            "ip" => $result["ip"],
            "expires_at" => $result["expires_at"],
        ];
    }

    #[\Override]
    public function insertFailedAttempt(
        string $username,
        string $ip,
        string $timestamp,
    ): void {
        $stmt = $this->connection->prepare("
            INSERT INTO failed_login_attempts (timestamp, ip, username)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$timestamp, $ip, $username]);
    }

    #[\Override]
    public function countAttempts(
        string $username,
        string $ip,
        string $now,
    ): array {
        // Calculate window cutoff internally
        $since = \date(
            SqlFormat::TIMESTAMP,
            \strtotime(
                "-{$this->windowMinutes} minutes",
                \strtotime($now) ?: null,
            ) ?:
            null,
        );

        $stmt = $this->connection->prepare("
            SELECT
                SUM(CASE WHEN username = ? THEN 1 ELSE 0 END) as user_count,
                SUM(CASE WHEN ip = ? THEN 1 ELSE 0 END) as ip_count
            FROM failed_login_attempts
            WHERE timestamp > ?
        ");
        $stmt->execute([$username, $ip, $since]);
        /** @var array{user_count: string, ip_count: string}|false $result */
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            throw new RepositoryStorageException(
                "Failed to fetch login rate limit data",
            );
        }

        return [
            "user_count" => (int) $result["user_count"],
            "ip_count" => (int) $result["ip_count"],
        ];
    }

    #[\Override]
    public function insertBlock(
        ?string $username,
        ?string $ip,
        string $expiresAt,
    ): void {
        $stmt = $this->connection->prepare("
            INSERT INTO login_blocks (username, ip, blocked_at, expires_at)
            VALUES (?, ?, CURRENT_TIMESTAMP, ?)
        ");
        $stmt->execute([$username, $ip, $expiresAt]);
    }

    #[\Override]
    public function clearUsernameFromAttempts(string $username): void
    {
        $stmt = $this->connection->prepare("
            UPDATE failed_login_attempts
            SET username = NULL
            WHERE username = ?
        ");
        $stmt->execute([$username]);
    }

    #[\Override]
    public function deleteExpired(string $now): int
    {
        // Calculate cutoff internally
        $cutoff = \date(
            SqlFormat::TIMESTAMP,
            \strtotime(
                "-{$this->windowMinutes} minutes",
                \strtotime($now) ?: null,
            ) ?:
            null,
        );

        $stmt = $this->connection->prepare("
            DELETE FROM failed_login_attempts
            WHERE timestamp < ?
        ");
        $stmt->execute([$cutoff]);
        $deleted = $stmt->rowCount();

        $stmt = $this->connection->prepare("
            DELETE FROM login_blocks
            WHERE expires_at < ?
        ");
        $stmt->execute([$now]);

        return $deleted + $stmt->rowCount();
    }
}
