<?php

use jschreuder\BookmarkBureau\Repository\FileJwtJtiRepository;
use Ramsey\Uuid\Uuid;

describe("FileJwtJtiRepository", function () {
    describe("saveJti", function () {
        test("saves a new JTI to the file", function () {
            $tempFile = tempnam(sys_get_temp_dir(), "jti_");
            try {
                $repo = new FileJwtJtiRepository($tempFile);
                $jti = Uuid::uuid4();
                $userId = Uuid::uuid4();
                $createdAt = new DateTimeImmutable();

                $repo->saveJti($jti, $userId, $createdAt);

                // Verify file was created and contains the entry
                expect(file_exists($tempFile))->toBeTrue();
                $content = file_get_contents($tempFile);
                expect($content)->toContain($jti->toString());
                expect($content)->toContain($userId->toString());
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });

        test("appends multiple JTIs to the file", function () {
            $tempFile = tempnam(sys_get_temp_dir(), "jti_");
            try {
                $repo = new FileJwtJtiRepository($tempFile);
                $jti1 = Uuid::uuid4();
                $jti2 = Uuid::uuid4();
                $userId = Uuid::uuid4();
                $createdAt = new DateTimeImmutable();

                $repo->saveJti($jti1, $userId, $createdAt);
                $repo->saveJti($jti2, $userId, $createdAt);

                $content = file_get_contents($tempFile);
                $lines = array_filter(explode("\n", $content));

                expect(count($lines))->toBe(2);
                expect($lines[0])->toContain($jti1->toString());
                expect($lines[1])->toContain($jti2->toString());
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });

        test("requires directory to exist", function () {
            $nonExistentDir = sys_get_temp_dir() . "/nonexistent_" . uniqid();
            $filePath = $nonExistentDir . "/jti_whitelist";

            expect(fn() => new FileJwtJtiRepository($filePath))->toThrow(
                jschreuder\BookmarkBureau\Exception\RepositoryStorageException::class,
            );
        });

        test(
            "throws RepositoryStorageException when file is not writable",
            function () {
                // Use /dev/null which exists but is not writable for files
                $invalidPath = "/dev/null";

                expect(fn() => new FileJwtJtiRepository($invalidPath))->toThrow(
                    jschreuder\BookmarkBureau\Exception\RepositoryStorageException::class,
                );
            },
        );
    });

    describe("hasJti", function () {
        test("returns true when JTI exists in file", function () {
            $tempFile = tempnam(sys_get_temp_dir(), "jti_");
            try {
                $repo = new FileJwtJtiRepository($tempFile);
                $jti = Uuid::uuid4();
                $userId = Uuid::uuid4();
                $createdAt = new DateTimeImmutable();

                $repo->saveJti($jti, $userId, $createdAt);

                expect($repo->hasJti($jti))->toBeTrue();
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });

        test("returns false when JTI does not exist", function () {
            $tempFile = tempnam(sys_get_temp_dir(), "jti_");
            try {
                $repo = new FileJwtJtiRepository($tempFile);
                $jti = Uuid::uuid4();

                expect($repo->hasJti($jti))->toBeFalse();
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });

        test("returns false when file does not exist", function () {
            $nonExistentFile = sys_get_temp_dir() . "/nonexistent_" . uniqid();
            $repo = new FileJwtJtiRepository($nonExistentFile);
            $jti = Uuid::uuid4();

            expect($repo->hasJti($jti))->toBeFalse();
        });

        test("finds JTI among multiple entries", function () {
            $tempFile = tempnam(sys_get_temp_dir(), "jti_");
            try {
                $repo = new FileJwtJtiRepository($tempFile);
                $userId = Uuid::uuid4();
                $createdAt = new DateTimeImmutable();
                $targetJti = Uuid::uuid4();

                // Add some JTIs before target
                $repo->saveJti(Uuid::uuid4(), $userId, $createdAt);
                $repo->saveJti(Uuid::uuid4(), $userId, $createdAt);
                // Add target JTI
                $repo->saveJti($targetJti, $userId, $createdAt);
                // Add some JTIs after target
                $repo->saveJti(Uuid::uuid4(), $userId, $createdAt);

                expect($repo->hasJti($targetJti))->toBeTrue();
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });
    });

    describe("deleteJti", function () {
        test("deletes a JTI from the file", function () {
            $tempFile = tempnam(sys_get_temp_dir(), "jti_");
            try {
                $repo = new FileJwtJtiRepository($tempFile);
                $jti = Uuid::uuid4();
                $userId = Uuid::uuid4();
                $createdAt = new DateTimeImmutable();

                $repo->saveJti($jti, $userId, $createdAt);
                expect($repo->hasJti($jti))->toBeTrue();

                $repo->deleteJti($jti);

                expect($repo->hasJti($jti))->toBeFalse();
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });

        test("does not affect other JTIs when deleting", function () {
            $tempFile = tempnam(sys_get_temp_dir(), "jti_");
            try {
                $repo = new FileJwtJtiRepository($tempFile);
                $userId = Uuid::uuid4();
                $createdAt = new DateTimeImmutable();
                $jti1 = Uuid::uuid4();
                $jti2 = Uuid::uuid4();
                $jti3 = Uuid::uuid4();

                $repo->saveJti($jti1, $userId, $createdAt);
                $repo->saveJti($jti2, $userId, $createdAt);
                $repo->saveJti($jti3, $userId, $createdAt);

                $repo->deleteJti($jti2);

                expect($repo->hasJti($jti1))->toBeTrue();
                expect($repo->hasJti($jti2))->toBeFalse();
                expect($repo->hasJti($jti3))->toBeTrue();
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });

        test(
            "does not throw error when deleting non-existent JTI",
            function () {
                $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                try {
                    $repo = new FileJwtJtiRepository($tempFile);
                    $jti = Uuid::uuid4();

                    $repo->deleteJti($jti); // Should not throw
                    expect(true)->toBeTrue();
                } finally {
                    if (file_exists($tempFile)) {
                        unlink($tempFile);
                    }
                }
            },
        );

        test("does not throw error when file does not exist", function () {
            $nonExistentFile = sys_get_temp_dir() . "/nonexistent_" . uniqid();
            $repo = new FileJwtJtiRepository($nonExistentFile);
            $jti = Uuid::uuid4();

            $repo->deleteJti($jti); // Should not throw
            expect(true)->toBeTrue();
        });
    });

    describe("file format", function () {
        test(
            "stores JTI in CSV format with user_id and timestamp",
            function () {
                $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                try {
                    $repo = new FileJwtJtiRepository($tempFile);
                    $jti = Uuid::uuid4();
                    $userId = Uuid::uuid4();
                    $createdAt = new DateTimeImmutable("2024-01-15 10:30:00");

                    $repo->saveJti($jti, $userId, $createdAt);

                    $content = file_get_contents($tempFile);
                    $parts = explode(",", trim($content));

                    expect(count($parts))->toBe(3);
                    expect($parts[0])->toBe($jti->toString());
                    expect($parts[1])->toBe($userId->toString());
                    expect($parts[2])->toBe(
                        (string) $createdAt->getTimestamp(),
                    );
                } finally {
                    if (file_exists($tempFile)) {
                        unlink($tempFile);
                    }
                }
            },
        );
    });
});
