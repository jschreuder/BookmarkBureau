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
            WHERE (username = :username OR ip = :ip)
              AND expires_at > :now
            ORDER BY expires_at DESC
            LIMIT 1
        ");
        $stmt->execute([
            ":username" => $username,
            ":ip" => $ip,
            ":now" => $now,
        ]);
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
            VALUES (:timestamp, :ip, :username)
        ");
        $stmt->execute([
            ":timestamp" => $timestamp,
            ":ip" => $ip,
            ":username" => $username,
        ]);
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
                SUM(CASE WHEN username = :username THEN 1 ELSE 0 END) as user_count,
                SUM(CASE WHEN ip = :ip THEN 1 ELSE 0 END) as ip_count
            FROM failed_login_attempts
            WHERE timestamp > :since
        ");
        $stmt->execute([
            ":username" => $username,
            ":ip" => $ip,
            ":since" => $since,
        ]);
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
        $now = new \DateTimeImmutable()->format(SqlFormat::TIMESTAMP);
        $stmt = $this->connection->prepare("
            INSERT INTO login_blocks (username, ip, blocked_at, expires_at)
            VALUES (:username, :ip, :blocked_at, :expires_at)
        ");
        $stmt->execute([
            ":username" => $username,
            ":ip" => $ip,
            ":blocked_at" => $now,
            ":expires_at" => $expiresAt,
        ]);
    }

    #[\Override]
    public function clearUsernameFromAttempts(string $username): void
    {
        $stmt = $this->connection->prepare("
            UPDATE failed_login_attempts
            SET username = NULL
            WHERE username = :username
        ");
        $stmt->execute([":username" => $username]);
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
            WHERE timestamp < :cutoff
        ");
        $stmt->execute([":cutoff" => $cutoff]);
        $deleted = $stmt->rowCount();

        $stmt = $this->connection->prepare("
            DELETE FROM login_blocks
            WHERE expires_at < :now
        ");
        $stmt->execute([":now" => $now]);

        return $deleted + $stmt->rowCount();
    }
}
