<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\Security;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PDO;

final class CreateRateLimitDatabaseCommand extends Command
{
    public function __construct(private PDO $rateLimitDb)
    {
        parent::__construct("security:create-ratelimit-db");
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription(
            "Create rate limiting database tables (SQLite or MySQL)",
        );
    }

    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $output->writeln(
                "<info>Creating rate limiting database tables...</info>",
            );

            // Check if we're using SQLite or MySQL
            $driver = $this->rateLimitDb->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === "sqlite") {
                $this->createSqliteTables();
                $output->writeln(
                    "<info>✓ Created SQLite tables successfully</info>",
                );
            } elseif ($driver === "mysql") {
                $this->createMysqlTables();
                $output->writeln(
                    "<info>✓ Created MySQL tables successfully</info>",
                );
            } else {
                $output->writeln(
                    "<error>Unsupported database driver: {$driver}</error>",
                );
                return Command::FAILURE;
            }

            $output->writeln("<comment>Tables created:</comment>");
            $output->writeln("  - failed_login_attempts");
            $output->writeln("  - login_blocks");

            return Command::SUCCESS;
        } catch (\PDOException $e) {
            $output->writeln(
                "<error>Database error: {$e->getMessage()}</error>",
            );
            return Command::FAILURE;
        }
    }

    private function createSqliteTables(): void
    {
        $this->rateLimitDb->exec("
            CREATE TABLE IF NOT EXISTS failed_login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp DATETIME NOT NULL,
                ip TEXT NOT NULL,
                username TEXT
            )
        ");

        $this->rateLimitDb->exec("
            CREATE INDEX IF NOT EXISTS idx_failed_attempts_timestamp
            ON failed_login_attempts(timestamp)
        ");

        $this->rateLimitDb->exec("
            CREATE INDEX IF NOT EXISTS idx_failed_attempts_username
            ON failed_login_attempts(username)
        ");

        $this->rateLimitDb->exec("
            CREATE INDEX IF NOT EXISTS idx_failed_attempts_ip
            ON failed_login_attempts(ip)
        ");

        $this->rateLimitDb->exec("
            CREATE TABLE IF NOT EXISTS login_blocks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT,
                ip TEXT,
                blocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL
            )
        ");

        $this->rateLimitDb->exec("
            CREATE INDEX IF NOT EXISTS idx_blocks_expires
            ON login_blocks(expires_at)
        ");
    }

    private function createMysqlTables(): void
    {
        $this->rateLimitDb->exec("
            CREATE TABLE IF NOT EXISTS failed_login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                timestamp DATETIME NOT NULL,
                ip VARCHAR(45) NOT NULL,
                username VARCHAR(255),
                INDEX idx_failed_attempts_timestamp (timestamp),
                INDEX idx_failed_attempts_username (username),
                INDEX idx_failed_attempts_ip (ip)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->rateLimitDb->exec("
            CREATE TABLE IF NOT EXISTS login_blocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255),
                ip VARCHAR(45),
                blocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                INDEX idx_blocks_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
