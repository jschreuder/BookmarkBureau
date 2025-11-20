<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Repository\FileJwtJtiRepository;
use Ramsey\Uuid\Uuid;

$tempDir = null;
$filePath = null;

beforeEach(function () use (&$tempDir, &$filePath) {
    $tempDir = sys_get_temp_dir() . "/jwt-jti-" . uniqid();
    mkdir($tempDir);
    $filePath = $tempDir . "/jwt.txt";
});

afterEach(function () use (&$tempDir) {
    if ($tempDir && file_exists($tempDir)) {
        foreach (glob("{$tempDir}/*") as $file) {
            unlink($file);
        }
        rmdir($tempDir);
    }
});

describe("FileJwtJtiRepository", function () use (&$tempDir, &$filePath) {
    describe("constructor", function () use (&$tempDir, &$filePath) {
        test(
            "should create repository with writable directory",
            function () use (&$tempDir, &$filePath) {
                $repo = new FileJwtJtiRepository($filePath);
                expect($repo)->toBeInstanceOf(FileJwtJtiRepository::class);
            },
        );

        test("should throw exception for non-existent directory", function () {
            $nonExistentPath = "/non/existent/directory/jwt.txt";
            expect(fn() => new FileJwtJtiRepository($nonExistentPath))->toThrow(
                RepositoryStorageException::class,
            );
        });

        test(
            "should throw exception for non-writable directory",
            function () use (&$tempDir) {
                chmod($tempDir, 0444);
                try {
                    expect(
                        fn() => new FileJwtJtiRepository("{$tempDir}/jwt.txt"),
                    )->toThrow(RepositoryStorageException::class);
                } finally {
                    chmod($tempDir, 0755);
                }
            },
        );

        test(
            "should throw exception for non-writable existing file",
            function () use (&$filePath) {
                touch($filePath);
                chmod($filePath, 0444);
                try {
                    expect(
                        fn() => new FileJwtJtiRepository($filePath),
                    )->toThrow(RepositoryStorageException::class);
                } finally {
                    chmod($filePath, 0755);
                }
            },
        );
    });

    describe("saveJti", function () use (&$filePath) {
        test("should save JTI to file", function () use (&$filePath) {
            $repo = new FileJwtJtiRepository($filePath);
            $jti = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable("2025-11-20 10:00:00");

            $repo->saveJti($jti, $userId, $createdAt);

            expect(file_exists($filePath))->toBeTrue();
            $content = file_get_contents($filePath);
            expect($content)->toContain($jti->toString());
            expect($content)->toContain($userId->toString());
            expect($content)->toContain((string) $createdAt->getTimestamp());
        });

        test("should append multiple JTIs to file", function () use (
            &$filePath,
        ) {
            $repo = new FileJwtJtiRepository($filePath);
            $jti1 = Uuid::uuid4();
            $jti2 = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable("2025-11-20 10:00:00");

            $repo->saveJti($jti1, $userId, $createdAt);
            $repo->saveJti($jti2, $userId, $createdAt);

            $content = file_get_contents($filePath);
            $lines = explode("\n", trim($content));
            expect(count($lines))->toBe(2);
        });

        test(
            "should format JTI as CSV with correct separator",
            function () use (&$filePath) {
                $repo = new FileJwtJtiRepository($filePath);
                $jti = Uuid::uuid4();
                $userId = Uuid::uuid4();
                $createdAt = new DateTimeImmutable("2025-11-20 10:00:00");

                $repo->saveJti($jti, $userId, $createdAt);

                $content = trim(file_get_contents($filePath));
                $parts = explode(",", $content);
                expect(count($parts))->toBe(3);
                expect($parts[0])->toBe($jti->toString());
                expect($parts[1])->toBe($userId->toString());
                expect($parts[2])->toBe((string) $createdAt->getTimestamp());
            },
        );
    });

    describe("hasJti", function () use (&$filePath) {
        test("should return false for non-existent file", function () use (
            &$filePath,
        ) {
            $repo = new FileJwtJtiRepository($filePath);
            $jti = Uuid::uuid4();

            expect($repo->hasJti($jti))->toBeFalse();
        });

        test("should return true for existing JTI", function () use (
            &$filePath,
        ) {
            $repo = new FileJwtJtiRepository($filePath);
            $jti = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti, $userId, $createdAt);

            expect($repo->hasJti($jti))->toBeTrue();
        });

        test(
            "should return false for non-existing JTI when file exists",
            function () use (&$filePath) {
                $repo = new FileJwtJtiRepository($filePath);
                $jti1 = Uuid::uuid4();
                $jti2 = Uuid::uuid4();
                $userId = Uuid::uuid4();
                $createdAt = new DateTimeImmutable();

                $repo->saveJti($jti1, $userId, $createdAt);

                expect($repo->hasJti($jti2))->toBeFalse();
            },
        );

        test("should find JTI among multiple entries", function () use (
            &$filePath,
        ) {
            $repo = new FileJwtJtiRepository($filePath);
            $jti1 = Uuid::uuid4();
            $jti2 = Uuid::uuid4();
            $jti3 = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti1, $userId, $createdAt);
            $repo->saveJti($jti2, $userId, $createdAt);
            $repo->saveJti($jti3, $userId, $createdAt);

            expect($repo->hasJti($jti2))->toBeTrue();
            expect($repo->hasJti($jti3))->toBeTrue();
            expect($repo->hasJti(Uuid::uuid4()))->toBeFalse();
        });
    });

    describe("deleteJti", function () use (&$filePath, &$tempDir) {
        test("should do nothing for non-existent file", function () use (
            &$filePath,
        ) {
            $repo = new FileJwtJtiRepository($filePath);
            $jti = Uuid::uuid4();

            $repo->deleteJti($jti);
            expect(file_exists($filePath))->toBeFalse();
        });

        test("should delete JTI from file", function () use (&$filePath) {
            $repo = new FileJwtJtiRepository($filePath);
            $jti = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti, $userId, $createdAt);
            expect($repo->hasJti($jti))->toBeTrue();

            $repo->deleteJti($jti);
            expect($repo->hasJti($jti))->toBeFalse();
        });

        test("should delete file when last JTI is removed", function () use (
            &$filePath,
        ) {
            $repo = new FileJwtJtiRepository($filePath);
            $jti = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti, $userId, $createdAt);
            expect(file_exists($filePath))->toBeTrue();

            $repo->deleteJti($jti);
            expect(file_exists($filePath))->toBeFalse();
        });

        test("should preserve other JTIs when deleting one", function () use (
            &$filePath,
        ) {
            $repo = new FileJwtJtiRepository($filePath);
            $jti1 = Uuid::uuid4();
            $jti2 = Uuid::uuid4();
            $jti3 = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti1, $userId, $createdAt);
            $repo->saveJti($jti2, $userId, $createdAt);
            $repo->saveJti($jti3, $userId, $createdAt);

            $repo->deleteJti($jti2);

            expect($repo->hasJti($jti1))->toBeTrue();
            expect($repo->hasJti($jti2))->toBeFalse();
            expect($repo->hasJti($jti3))->toBeTrue();
        });

        test(
            "should delete correct JTI when similar prefixes exist",
            function () use (&$filePath) {
                $repo = new FileJwtJtiRepository($filePath);
                $userId = Uuid::uuid4();
                $createdAt = new DateTimeImmutable();

                $jti1 = Uuid::fromString(
                    "550e8400-e29b-41d4-a716-446655440000",
                );
                $jti2 = Uuid::fromString(
                    "550e8400-e29b-41d4-a716-446655440001",
                );

                $repo->saveJti($jti1, $userId, $createdAt);
                $repo->saveJti($jti2, $userId, $createdAt);

                $repo->deleteJti($jti1);

                expect($repo->hasJti($jti1))->toBeFalse();
                expect($repo->hasJti($jti2))->toBeTrue();
            },
        );
    });

    describe("integration", function () use (&$filePath) {
        test("should handle complete lifecycle", function () use (&$filePath) {
            $repo = new FileJwtJtiRepository($filePath);
            $jti1 = Uuid::uuid4();
            $jti2 = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti1, $userId, $createdAt);
            $repo->saveJti($jti2, $userId, $createdAt);

            expect($repo->hasJti($jti1))->toBeTrue();
            expect($repo->hasJti($jti2))->toBeTrue();

            $repo->deleteJti($jti1);
            expect($repo->hasJti($jti1))->toBeFalse();
            expect($repo->hasJti($jti2))->toBeTrue();

            $repo->deleteJti($jti2);
            expect(file_exists($filePath))->toBeFalse();
        });

        test("should persist across repository instances", function () use (
            &$filePath,
        ) {
            $jti = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo1 = new FileJwtJtiRepository($filePath);
            $repo1->saveJti($jti, $userId, $createdAt);

            $repo2 = new FileJwtJtiRepository($filePath);
            expect($repo2->hasJti($jti))->toBeTrue();

            $repo2->deleteJti($jti);

            expect($repo1->hasJti($jti))->toBeFalse();
        });
    });
});
