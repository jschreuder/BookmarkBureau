<?php

use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Repository\PdoLoginRateLimitRepository;

describe("PdoLoginRateLimitRepository", function () {
    function createRateLimitDatabase(): PDO
    {
        $pdo = new PDO("sqlite::memory:");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create schema
        $pdo->exec("
            CREATE TABLE failed_login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp DATETIME NOT NULL,
                ip TEXT NOT NULL,
                username TEXT
            );

            CREATE TABLE login_blocks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT,
                ip TEXT,
                blocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL
            );

            CREATE INDEX idx_failed_attempts_timestamp ON failed_login_attempts(timestamp);
            CREATE INDEX idx_failed_attempts_username ON failed_login_attempts(username);
            CREATE INDEX idx_failed_attempts_ip ON failed_login_attempts(ip);
            CREATE INDEX idx_blocks_expires ON login_blocks(expires_at);
        ");

        return $pdo;
    }

    describe("getBlockInfo method", function () {
        test("should return not blocked when no blocks exist", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            $result = $repo->getBlockInfo(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );

            expect($result["blocked"])->toBeFalse();
            expect($result["username"])->toBeNull();
            expect($result["ip"])->toBeNull();
            expect($result["expires_at"])->toBeNull();
        });

        test("should return block info when username is blocked", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            // Insert a block
            $pdo->exec("
                INSERT INTO login_blocks (username, ip, expires_at)
                VALUES ('user@example.com', NULL, '2024-01-01 13:00:00')
            ");

            $result = $repo->getBlockInfo(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );

            expect($result["blocked"])->toBeTrue();
            expect($result["username"])->toBe("user@example.com");
            expect($result["ip"])->toBeNull();
            expect($result["expires_at"])->toBe("2024-01-01 13:00:00");
        });

        test("should return block info when IP is blocked", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            // Insert a block
            $pdo->exec("
                INSERT INTO login_blocks (username, ip, expires_at)
                VALUES (NULL, '192.168.1.1', '2024-01-01 13:00:00')
            ");

            $result = $repo->getBlockInfo(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );

            expect($result["blocked"])->toBeTrue();
            expect($result["username"])->toBeNull();
            expect($result["ip"])->toBe("192.168.1.1");
            expect($result["expires_at"])->toBe("2024-01-01 13:00:00");
        });

        test("should return not blocked when block has expired", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            // Insert an expired block
            $pdo->exec("
                INSERT INTO login_blocks (username, ip, expires_at)
                VALUES ('user@example.com', NULL, '2024-01-01 11:00:00')
            ");

            $result = $repo->getBlockInfo(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );

            expect($result["blocked"])->toBeFalse();
        });

        test(
            "should return block when either username or IP matches",
            function () {
                $pdo = createRateLimitDatabase();
                $repo = new PdoLoginRateLimitRepository($pdo);

                // Insert a username block
                $pdo->exec("
                INSERT INTO login_blocks (username, ip, expires_at)
                VALUES ('different@example.com', NULL, '2024-01-01 13:00:00')
            ");

                // Check with different username but matching IP (should be false)
                $result = $repo->getBlockInfo(
                    "user@example.com",
                    "192.168.1.1",
                    "2024-01-01 12:00:00",
                );

                expect($result["blocked"])->toBeFalse();

                // Now add IP block
                $pdo->exec("
                INSERT INTO login_blocks (username, ip, expires_at)
                VALUES (NULL, '192.168.1.1', '2024-01-01 13:00:00')
            ");

                // Should now be blocked
                $result = $repo->getBlockInfo(
                    "user@example.com",
                    "192.168.1.1",
                    "2024-01-01 12:00:00",
                );

                expect($result["blocked"])->toBeTrue();
                expect($result["ip"])->toBe("192.168.1.1");
            },
        );
    });

    describe("insertFailedAttempt method", function () {
        test("should insert a failed login attempt", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );

            $stmt = $pdo->query("SELECT COUNT(*) FROM failed_login_attempts");
            $count = $stmt->fetchColumn();

            expect($count)->toBe(1);
        });

        test("should store all attempt details correctly", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.100",
                "2024-01-01 14:30:45",
            );

            $stmt = $pdo->query("SELECT * FROM failed_login_attempts");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            expect($row["username"])->toBe("user@example.com");
            expect($row["ip"])->toBe("192.168.1.100");
            expect($row["timestamp"])->toBe("2024-01-01 14:30:45");
        });

        test("should allow multiple attempts from same source", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:01:00",
            );
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:02:00",
            );

            $stmt = $pdo->query("SELECT COUNT(*) FROM failed_login_attempts");
            $count = $stmt->fetchColumn();

            expect($count)->toBe(3);
        });
    });

    describe("countAttempts method", function () {
        test("should return zero counts when no attempts exist", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            $counts = $repo->countAttempts(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );

            expect($counts["user_count"])->toBe(0);
            expect($counts["ip_count"])->toBe(0);
        });

        test("should count username attempts within window", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo, windowMinutes: 10);

            // Insert attempts within window
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 11:55:00",
            );
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.2",
                "2024-01-01 11:58:00",
            );
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.3",
                "2024-01-01 12:00:00",
            );

            // Insert attempt outside window
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.4",
                "2024-01-01 11:45:00",
            );

            $counts = $repo->countAttempts(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );

            expect($counts["user_count"])->toBe(3);
        });

        test("should count IP attempts within window", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo, windowMinutes: 10);

            // Insert attempts within window
            $repo->insertFailedAttempt(
                "user1@example.com",
                "192.168.1.1",
                "2024-01-01 11:55:00",
            );
            $repo->insertFailedAttempt(
                "user2@example.com",
                "192.168.1.1",
                "2024-01-01 11:58:00",
            );
            $repo->insertFailedAttempt(
                "user3@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );

            // Insert attempt outside window
            $repo->insertFailedAttempt(
                "user4@example.com",
                "192.168.1.1",
                "2024-01-01 11:45:00",
            );

            $counts = $repo->countAttempts(
                "user1@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );

            expect($counts["ip_count"])->toBe(3);
        });

        test(
            "should count both username and IP attempts independently",
            function () {
                $pdo = createRateLimitDatabase();
                $repo = new PdoLoginRateLimitRepository(
                    $pdo,
                    windowMinutes: 10,
                );

                // Username attempts from different IPs
                $repo->insertFailedAttempt(
                    "user@example.com",
                    "192.168.1.1",
                    "2024-01-01 11:55:00",
                );
                $repo->insertFailedAttempt(
                    "user@example.com",
                    "192.168.1.2",
                    "2024-01-01 11:56:00",
                );
                $repo->insertFailedAttempt(
                    "user@example.com",
                    "192.168.1.3",
                    "2024-01-01 11:57:00",
                );

                // IP attempts from different users
                $repo->insertFailedAttempt(
                    "other1@example.com",
                    "192.168.1.1",
                    "2024-01-01 11:58:00",
                );
                $repo->insertFailedAttempt(
                    "other2@example.com",
                    "192.168.1.1",
                    "2024-01-01 11:59:00",
                );

                $counts = $repo->countAttempts(
                    "user@example.com",
                    "192.168.1.1",
                    "2024-01-01 12:00:00",
                );

                // 3 attempts for username, 3 attempts for IP (1 from user + 2 from others)
                expect($counts["user_count"])->toBe(3);
                expect($counts["ip_count"])->toBe(3);
            },
        );

        test("should respect custom window size", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo, windowMinutes: 5);

            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 11:56:00",
            );
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 11:58:00",
            );
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 11:54:00",
            ); // Outside 5-min window

            $counts = $repo->countAttempts(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );

            expect($counts["user_count"])->toBe(2);
        });

        test("throws RepositoryStorageException when fetch fails", function () {
            $mockPdo = Mockery::mock(PDO::class);
            $mockStmt = Mockery::mock(PDOStatement::class);

            $mockStmt->shouldReceive("execute")->andReturn(true);
            $mockStmt->shouldReceive("fetch")->andReturn(false);

            $mockPdo->shouldReceive("prepare")->andReturn($mockStmt);

            $repo = new PdoLoginRateLimitRepository($mockPdo);

            expect(
                fn() => $repo->countAttempts(
                    "user@example.com",
                    "192.168.1.1",
                    "2024-01-01 12:00:00",
                ),
            )->toThrow(RepositoryStorageException::class);
        });
    });

    describe("insertBlock method", function () {
        test("should insert a username block", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            $repo->insertBlock("user@example.com", null, "2024-01-01 13:00:00");

            $stmt = $pdo->query("SELECT * FROM login_blocks");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            expect($row["username"])->toBe("user@example.com");
            expect($row["ip"])->toBeNull();
            expect($row["expires_at"])->toBe("2024-01-01 13:00:00");
        });

        test("should insert an IP block", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            $repo->insertBlock(null, "192.168.1.1", "2024-01-01 13:00:00");

            $stmt = $pdo->query("SELECT * FROM login_blocks");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            expect($row["username"])->toBeNull();
            expect($row["ip"])->toBe("192.168.1.1");
            expect($row["expires_at"])->toBe("2024-01-01 13:00:00");
        });

        test("should allow multiple blocks", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            $repo->insertBlock(
                "user1@example.com",
                null,
                "2024-01-01 13:00:00",
            );
            $repo->insertBlock(
                "user2@example.com",
                null,
                "2024-01-01 13:00:00",
            );
            $repo->insertBlock(null, "192.168.1.1", "2024-01-01 13:00:00");

            $stmt = $pdo->query("SELECT COUNT(*) FROM login_blocks");
            $count = $stmt->fetchColumn();

            expect($count)->toBe(3);
        });
    });

    describe("clearUsernameFromAttempts method", function () {
        test("should set username to NULL for matching attempts", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.2",
                "2024-01-01 12:01:00",
            );

            $repo->clearUsernameFromAttempts("user@example.com");

            $stmt = $pdo->query(
                "SELECT username FROM failed_login_attempts WHERE username IS NULL",
            );
            $rows = $stmt->fetchAll();

            expect(count($rows))->toBe(2);
        });

        test(
            "should preserve IP addresses when clearing username",
            function () {
                $pdo = createRateLimitDatabase();
                $repo = new PdoLoginRateLimitRepository($pdo);

                $repo->insertFailedAttempt(
                    "user@example.com",
                    "192.168.1.1",
                    "2024-01-01 12:00:00",
                );

                $repo->clearUsernameFromAttempts("user@example.com");

                $stmt = $pdo->query("SELECT ip FROM failed_login_attempts");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                expect($row["ip"])->toBe("192.168.1.1");
            },
        );

        test("should only clear matching username", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            $repo->insertFailedAttempt(
                "user1@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );
            $repo->insertFailedAttempt(
                "user2@example.com",
                "192.168.1.2",
                "2024-01-01 12:01:00",
            );

            $repo->clearUsernameFromAttempts("user1@example.com");

            $stmt = $pdo->query(
                "SELECT username FROM failed_login_attempts WHERE username IS NOT NULL",
            );
            $rows = $stmt->fetchAll();

            expect(count($rows))->toBe(1);
            expect($rows[0]["username"])->toBe("user2@example.com");
        });

        test(
            "should handle clearing non-existent username gracefully",
            function () {
                $pdo = createRateLimitDatabase();
                $repo = new PdoLoginRateLimitRepository($pdo);

                $repo->insertFailedAttempt(
                    "user@example.com",
                    "192.168.1.1",
                    "2024-01-01 12:00:00",
                );

                $repo->clearUsernameFromAttempts("nonexistent@example.com");

                $stmt = $pdo->query(
                    "SELECT COUNT(*) FROM failed_login_attempts WHERE username IS NOT NULL",
                );
                $count = $stmt->fetchColumn();

                expect($count)->toBe(1);
            },
        );
    });

    describe("deleteExpired method", function () {
        test("should delete expired attempts", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo, windowMinutes: 10);

            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 11:45:00",
            );
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 11:55:00",
            );

            $deleted = $repo->deleteExpired("2024-01-01 12:00:00");

            // Should delete the one from 11:45 (15 minutes ago, outside 10-min window)
            expect($deleted)->toBe(1);

            $stmt = $pdo->query("SELECT COUNT(*) FROM failed_login_attempts");
            $count = $stmt->fetchColumn();
            expect($count)->toBe(1);
        });

        test("should delete expired blocks", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo);

            $pdo->exec("
                INSERT INTO login_blocks (username, ip, expires_at)
                VALUES ('user@example.com', NULL, '2024-01-01 11:00:00')
            ");
            $pdo->exec("
                INSERT INTO login_blocks (username, ip, expires_at)
                VALUES ('user2@example.com', NULL, '2024-01-01 13:00:00')
            ");

            $deleted = $repo->deleteExpired("2024-01-01 12:00:00");

            // Should delete at least the expired block (and possibly expired attempts)
            expect($deleted)->toBeGreaterThanOrEqual(1);

            $stmt = $pdo->query("SELECT COUNT(*) FROM login_blocks");
            $count = $stmt->fetchColumn();
            expect($count)->toBe(1);
        });

        test("should return total count of deleted records", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo, windowMinutes: 10);

            // Add expired attempts
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 11:40:00",
            );
            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 11:45:00",
            );

            // Add expired block
            $pdo->exec("
                INSERT INTO login_blocks (username, ip, expires_at)
                VALUES ('user@example.com', NULL, '2024-01-01 11:30:00')
            ");

            $deleted = $repo->deleteExpired("2024-01-01 12:00:00");

            // Should delete 2 attempts + 1 block = 3
            expect($deleted)->toBe(3);
        });

        test("should not delete recent attempts or blocks", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo, windowMinutes: 10);

            $repo->insertFailedAttempt(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 11:55:00",
            );
            $pdo->exec("
                INSERT INTO login_blocks (username, ip, expires_at)
                VALUES ('user@example.com', NULL, '2024-01-01 13:00:00')
            ");

            $deleted = $repo->deleteExpired("2024-01-01 12:00:00");

            expect($deleted)->toBe(0);

            $stmt = $pdo->query("SELECT COUNT(*) FROM failed_login_attempts");
            $attemptCount = $stmt->fetchColumn();
            expect($attemptCount)->toBe(1);

            $stmt = $pdo->query("SELECT COUNT(*) FROM login_blocks");
            $blockCount = $stmt->fetchColumn();
            expect($blockCount)->toBe(1);
        });
    });

    describe("integration scenarios", function () {
        test("should handle complete rate limit workflow", function () {
            $pdo = createRateLimitDatabase();
            $repo = new PdoLoginRateLimitRepository($pdo, windowMinutes: 10);

            // Not blocked initially
            $blockInfo = $repo->getBlockInfo(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:00:00",
            );
            expect($blockInfo["blocked"])->toBeFalse();

            // Record multiple failures
            for ($i = 0; $i < 11; $i++) {
                $timestamp = sprintf("2024-01-01 12:%02d:00", $i);
                $repo->insertFailedAttempt(
                    "user@example.com",
                    "192.168.1.1",
                    $timestamp,
                );
            }

            // Count attempts
            $counts = $repo->countAttempts(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:10:00",
            );
            expect($counts["user_count"])->toBe(10); // Only 10 within the window (12:00 - 12:09)

            // Create block
            $repo->insertBlock("user@example.com", null, "2024-01-01 12:20:00");

            // Now blocked
            $blockInfo = $repo->getBlockInfo(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:10:00",
            );
            expect($blockInfo["blocked"])->toBeTrue();
            expect($blockInfo["username"])->toBe("user@example.com");

            // Clear username on successful login
            $repo->clearUsernameFromAttempts("user@example.com");

            // Count after clearing
            $counts = $repo->countAttempts(
                "user@example.com",
                "192.168.1.1",
                "2024-01-01 12:10:00",
            );
            expect($counts["user_count"])->toBe(0);
            expect($counts["ip_count"])->toBe(10); // IP still tracked (10 within window)

            // Cleanup expired data
            $deleted = $repo->deleteExpired("2024-01-01 12:30:00");
            expect($deleted)->toBeGreaterThan(0);
        });
    });
});
