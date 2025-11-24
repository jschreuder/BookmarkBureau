<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Config\DefaultRateLimitConfig;
use jschreuder\BookmarkBureau\Config\SqliteDatabaseConfig;
use jschreuder\BookmarkBureau\Service\RateLimitServiceInterface;

describe("DefaultRateLimitConfig", function () {
    describe("constructor parameters", function () {
        test("stores database config", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DefaultRateLimitConfig(
                database: $dbConfig,
                usernameThreshold: 10,
                ipThreshold: 100,
                windowMinutes: 10,
            );

            expect($config->database)->toBe($dbConfig);
        });

        test("stores threshold and window parameters", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DefaultRateLimitConfig(
                database: $dbConfig,
                usernameThreshold: 5,
                ipThreshold: 50,
                windowMinutes: 15,
            );

            expect($config->usernameThreshold)->toBe(5);
            expect($config->ipThreshold)->toBe(50);
            expect($config->windowMinutes)->toBe(15);
        });

        test("stores trust proxy headers flag", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DefaultRateLimitConfig(
                database: $dbConfig,
                usernameThreshold: 10,
                ipThreshold: 100,
                windowMinutes: 10,
                trustProxyHeaders: true,
            );

            expect($config->trustProxyHeaders)->toBe(true);
        });

        test("defaults trust proxy headers to false", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DefaultRateLimitConfig(
                database: $dbConfig,
                usernameThreshold: 10,
                ipThreshold: 100,
                windowMinutes: 10,
            );

            expect($config->trustProxyHeaders)->toBe(false);
        });
    });

    describe("trust proxy headers boolean method", function () {
        test("returns trust proxy headers as boolean", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DefaultRateLimitConfig(
                database: $dbConfig,
                usernameThreshold: 10,
                ipThreshold: 100,
                windowMinutes: 10,
                trustProxyHeaders: true,
            );

            expect($config->trustProxyHeadersBool())->toBeTrue();
        });

        test("returns false when trust proxy headers is false", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DefaultRateLimitConfig(
                database: $dbConfig,
                usernameThreshold: 10,
                ipThreshold: 100,
                windowMinutes: 10,
                trustProxyHeaders: false,
            );

            expect($config->trustProxyHeadersBool())->toBeFalse();
        });
    });

    describe("rate limit service creation", function () {
        test("creates RateLimitService instance", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DefaultRateLimitConfig(
                database: $dbConfig,
                usernameThreshold: 10,
                ipThreshold: 100,
                windowMinutes: 10,
            );

            $service = $config->createRateLimitService();
            expect($service)->toBeInstanceOf(RateLimitServiceInterface::class);
        });

        test("rate limit service uses configured thresholds", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");

            // Create tables for rate limiting
            $pdo = $dbConfig->getConnection();
            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS failed_login_attempts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    timestamp DATETIME NOT NULL,
                    ip TEXT NOT NULL,
                    username TEXT
                );

                CREATE TABLE IF NOT EXISTS login_blocks (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT,
                    ip TEXT,
                    blocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    expires_at DATETIME NOT NULL
                );
            SQL);

            $config = new DefaultRateLimitConfig(
                database: $dbConfig,
                usernameThreshold: 5,
                ipThreshold: 50,
                windowMinutes: 10,
            );

            $service = $config->createRateLimitService();
            expect($service)->toBeInstanceOf(RateLimitServiceInterface::class);
        });

        test("rate limit service is created with window minutes", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");

            // Create tables for rate limiting
            $pdo = $dbConfig->getConnection();
            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS failed_login_attempts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    timestamp DATETIME NOT NULL,
                    ip TEXT NOT NULL,
                    username TEXT
                );

                CREATE TABLE IF NOT EXISTS login_blocks (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT,
                    ip TEXT,
                    blocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    expires_at DATETIME NOT NULL
                );
            SQL);

            $config = new DefaultRateLimitConfig(
                database: $dbConfig,
                usernameThreshold: 10,
                ipThreshold: 100,
                windowMinutes: 15,
            );

            $service = $config->createRateLimitService();
            expect($service)->toBeInstanceOf(RateLimitServiceInterface::class);
        });
    });

    describe("readonly property", function () {
        test("config is readonly", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DefaultRateLimitConfig(
                database: $dbConfig,
                usernameThreshold: 10,
                ipThreshold: 100,
                windowMinutes: 10,
            );

            // Attempting to modify readonly properties should fail
            expect(fn() => $config->usernameThreshold = 20)
                ->toThrow(Error::class);
        });
    });

    describe("various threshold combinations", function () {
        test("accepts different threshold values", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");

            $combinations = [
                [5, 25, 5],
                [10, 100, 10],
                [20, 200, 30],
                [1, 10, 1],
            ];

            foreach ($combinations as [$username, $ip, $window]) {
                $config = new DefaultRateLimitConfig(
                    database: $dbConfig,
                    usernameThreshold: $username,
                    ipThreshold: $ip,
                    windowMinutes: $window,
                );

                expect($config->usernameThreshold)->toBe($username);
                expect($config->ipThreshold)->toBe($ip);
                expect($config->windowMinutes)->toBe($window);
            }
        });
    });
});
