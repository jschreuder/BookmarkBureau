<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Command\Security\CreateRateLimitDatabaseCommand;
use Symfony\Component\Console\Tester\CommandTester;

describe("CreateRateLimitDatabaseCommand", function () {
    describe("execute with SQLite", function () {
        test("should create all necessary tables and columns", function () {
            // Create in-memory SQLite database
            $pdo = new PDO("sqlite::memory:");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $command = new CreateRateLimitDatabaseCommand($pdo);
            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain(
                "sqlite tables successfully",
            );
            expect($tester->getDisplay())->toContain("failed_login_attempts");
            expect($tester->getDisplay())->toContain("login_blocks");

            // Verify failed_login_attempts table
            $columns = $pdo
                ->query("PRAGMA table_info(failed_login_attempts)")
                ->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, "name");

            expect($columnNames)->toContain("id");
            expect($columnNames)->toContain("timestamp");
            expect($columnNames)->toContain("ip");
            expect($columnNames)->toContain("username");

            // Verify login_blocks table
            $columns = $pdo
                ->query("PRAGMA table_info(login_blocks)")
                ->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, "name");

            expect($columnNames)->toContain("id");
            expect($columnNames)->toContain("username");
            expect($columnNames)->toContain("ip");
            expect($columnNames)->toContain("blocked_at");
            expect($columnNames)->toContain("expires_at");

            // Verify indexes exist
            $indexes = $pdo
                ->query("SELECT name FROM sqlite_master WHERE type='index'")
                ->fetchAll(PDO::FETCH_ASSOC);
            $indexNames = array_column($indexes, "name");

            expect($indexNames)->toContain("idx_failed_attempts_timestamp");
            expect($indexNames)->toContain("idx_failed_attempts_username");
            expect($indexNames)->toContain("idx_failed_attempts_ip");
            expect($indexNames)->toContain("idx_blocks_expires");
        });

        test("should be idempotent with existing tables", function () {
            $pdo = new PDO("sqlite::memory:");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $command = new CreateRateLimitDatabaseCommand($pdo);
            $tester = new CommandTester($command);

            // First execution
            $statusCode1 = $tester->execute([]);
            expect($statusCode1)->toBe(0);

            // Second execution should not fail
            $statusCode2 = $tester->execute([]);
            expect($statusCode2)->toBe(0);
            expect($tester->getDisplay())->toContain(
                "sqlite tables successfully",
            );
        });
    });

    describe("execute with MySQL", function () {
        test("should create MySQL tables successfully", function () {
            // Create a mock MySQL connection (we won't connect to a real DB for this test)
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("getAttribute")
                ->with(PDO::ATTR_DRIVER_NAME)
                ->andReturn("mysql");

            // MySQL: 2 CREATE TABLE statements (indexes are inline in the CREATE TABLE)
            $pdo->shouldReceive("exec")->times(2)->andReturn(0);

            $command = new CreateRateLimitDatabaseCommand($pdo);
            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain(
                "mysql tables successfully",
            );
        });
    });

    describe("execute with unsupported driver", function () {
        test("should fail with unsupported database driver", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("getAttribute")
                ->with(PDO::ATTR_DRIVER_NAME)
                ->andReturn("postgresql");

            $command = new CreateRateLimitDatabaseCommand($pdo);
            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain(
                "Unsupported database driver",
            );
        });
    });

    describe("execute with database error", function () {
        test("should handle PDO exceptions gracefully", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("getAttribute")
                ->with(PDO::ATTR_DRIVER_NAME)
                ->andReturn("sqlite");

            $pdo->shouldReceive("exec")->andThrow(
                new PDOException("Connection failed"),
            );

            $command = new CreateRateLimitDatabaseCommand($pdo);
            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain("Database error");
            expect($tester->getDisplay())->toContain("Connection failed");
        });
    });
});
