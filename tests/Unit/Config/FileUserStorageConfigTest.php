<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Config\FileUserStorageConfig;
use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FileUserRepository;

describe("FileUserStorageConfig", function () {
    describe("constructor parameters", function () {
        test("stores file path", function () {
            $filePath = sys_get_temp_dir() . "/users.json";
            $config = new FileUserStorageConfig(filePath: $filePath);

            expect($config->filePath)->toBe($filePath);
        });

        test("accepts various file paths", function () {
            $paths = [
                "/var/data/users.json",
                "./users.json",
                sys_get_temp_dir() . "/test_users.json",
            ];

            foreach ($paths as $path) {
                $config = new FileUserStorageConfig(filePath: $path);
                expect($config->filePath)->toBe($path);
            }
        });
    });

    describe("user repository creation", function () {
        test("creates FileUserRepository instance", function () {
            $filePath = sys_get_temp_dir() . "/test_users_" . uniqid() . ".json";
            $config = new FileUserStorageConfig(filePath: $filePath);

            $repository = $config->createUserRepository();
            expect($repository)->toBeInstanceOf(UserRepositoryInterface::class);
            expect($repository)->toBeInstanceOf(FileUserRepository::class);

            // Cleanup
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        });

        test("repository can be used to save and retrieve users", function () {
            $filePath = sys_get_temp_dir() . "/test_users_" . uniqid() . ".json";
            $config = new FileUserStorageConfig(filePath: $filePath);

            $repository = $config->createUserRepository();

            // Create a test user
            $user = TestEntityFactory::createUser();

            // Save the user
            $repository->save($user);

            // Retrieve the user
            $retrieved = $repository->findById($user->userId);

            expect($retrieved)->not->toBeNull();
            expect((string) $retrieved->userId)->toBe((string) $user->userId);

            // Cleanup
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        });

        test("multiple calls to createUserRepository return different instances", function () {
            $filePath = sys_get_temp_dir() . "/test_users_" . uniqid() . ".json";
            $config = new FileUserStorageConfig(filePath: $filePath);

            $repository1 = $config->createUserRepository();
            $repository2 = $config->createUserRepository();

            // Different instances
            expect($repository1)->not->toBe($repository2);
            // But same type
            expect($repository1)->toBeInstanceOf(FileUserRepository::class);
            expect($repository2)->toBeInstanceOf(FileUserRepository::class);

            // Cleanup
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        });
    });

    describe("readonly property", function () {
        test("config is readonly", function () {
            $config = new FileUserStorageConfig(
                filePath: "/tmp/users.json",
            );

            // Attempting to modify readonly properties should fail
            expect(fn() => $config->filePath = "/tmp/other.json")
                ->toThrow(Error::class);
        });
    });

    describe("file path variations", function () {
        test("works with absolute paths", function () {
            $filePath = "/tmp/bookmark_users_" . uniqid() . ".json";
            $config = new FileUserStorageConfig(filePath: $filePath);

            expect($config->filePath)->toBe($filePath);
        });

        test("works with relative paths", function () {
            $filePath = "./var/users.json";
            $config = new FileUserStorageConfig(filePath: $filePath);

            expect($config->filePath)->toBe($filePath);
        });

        test("works with php:// stream wrapper paths", function () {
            $filePath = "php://memory";
            $config = new FileUserStorageConfig(filePath: $filePath);

            expect($config->filePath)->toBe($filePath);
        });
    });
});
