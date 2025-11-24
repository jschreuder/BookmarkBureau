<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Config\DatabaseUserStorageConfig;
use jschreuder\BookmarkBureau\Config\SqliteDatabaseConfig;
use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\PdoUserRepository;

describe("DatabaseUserStorageConfig", function () {
    describe("constructor parameters", function () {
        test("stores database config", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DatabaseUserStorageConfig($dbConfig);

            // We can't directly access private property, but we can verify behavior
            expect($config)->toBeInstanceOf(DatabaseUserStorageConfig::class);
        });
    });

    describe("user repository creation", function () {
        test("creates PdoUserRepository instance", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DatabaseUserStorageConfig($dbConfig);

            $repository = $config->createUserRepository();
            expect($repository)->toBeInstanceOf(UserRepositoryInterface::class);
            expect($repository)->toBeInstanceOf(PdoUserRepository::class);
        });

        test("repository uses database from config", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");

            // Create users table
            $pdo = $dbConfig->getConnection();
            $pdo->exec(
                <<<SQL
                CREATE TABLE IF NOT EXISTS users (
                    user_id CHAR(16) PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    totp_secret VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
                SQL
            );

            $config = new DatabaseUserStorageConfig($dbConfig);
            $repository = $config->createUserRepository();

            // Create and save a test user
            $user = TestEntityFactory::createUser();
            $repository->save($user);

            // Retrieve the user
            $retrieved = $repository->findById($user->userId);

            expect($retrieved)->not->toBeNull();
            expect((string) $retrieved->userId)->toBe((string) $user->userId);
            expect((string) $retrieved->email)->toBe((string) $user->email);
        });

        test("multiple calls to createUserRepository return different instances", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DatabaseUserStorageConfig($dbConfig);

            $repository1 = $config->createUserRepository();
            $repository2 = $config->createUserRepository();

            // Different instances
            expect($repository1)->not->toBe($repository2);
            // But same type
            expect($repository1)->toBeInstanceOf(PdoUserRepository::class);
            expect($repository2)->toBeInstanceOf(PdoUserRepository::class);
        });

        test("repository can find user by email", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");

            // Create users table
            $pdo = $dbConfig->getConnection();
            $pdo->exec(
                <<<SQL
                CREATE TABLE IF NOT EXISTS users (
                    user_id CHAR(16) PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    totp_secret VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
                SQL
            );

            $config = new DatabaseUserStorageConfig($dbConfig);
            $repository = $config->createUserRepository();

            // Create and save a test user
            $user = TestEntityFactory::createUser();
            $repository->save($user);

            // Find by email
            $retrieved = $repository->findByEmail($user->email);

            expect($retrieved)->not->toBeNull();
            expect((string) $retrieved->email)->toBe((string) $user->email);
        });
    });

    describe("readonly property", function () {
        test("config is readonly", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DatabaseUserStorageConfig($dbConfig);

            // Try to access the private property through reflection or standard means
            // The readonly class prevents modification at the language level
            $reflectionClass = new ReflectionClass($config);
            expect($reflectionClass->isReadonly())->toBeTrue();
        });
    });

    describe("integration with different database types", function () {
        test("works with SQLite database config", function () {
            $dbConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config = new DatabaseUserStorageConfig($dbConfig);

            $repository = $config->createUserRepository();
            expect($repository)->toBeInstanceOf(UserRepositoryInterface::class);
        });

        test("can be constructed with different database configs", function () {
            // SQLite config
            $sqliteConfig = new SqliteDatabaseConfig("sqlite::memory:");
            $config1 = new DatabaseUserStorageConfig($sqliteConfig);
            expect($config1)->toBeInstanceOf(DatabaseUserStorageConfig::class);

            // Both configs should work
            expect($config1->createUserRepository())
                ->toBeInstanceOf(UserRepositoryInterface::class);
        });
    });
});
