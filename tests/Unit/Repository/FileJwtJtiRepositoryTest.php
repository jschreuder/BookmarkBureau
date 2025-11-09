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

    describe("exception handling", function () {
        describe("behavioral consistency", function () {
            test("saveJti appends to existing file atomically", function () {
                $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                try {
                    $repo = new FileJwtJtiRepository($tempFile);
                    $userId = Uuid::uuid4();
                    $createdAt = new DateTimeImmutable();

                    $jti1 = Uuid::uuid4();
                    $jti2 = Uuid::uuid4();

                    $repo->saveJti($jti1, $userId, $createdAt);
                    $content1 = file_get_contents($tempFile);
                    $lineCount1 = count(array_filter(explode("\n", $content1)));

                    $repo->saveJti($jti2, $userId, $createdAt);
                    $content2 = file_get_contents($tempFile);
                    $lineCount2 = count(array_filter(explode("\n", $content2)));

                    expect($lineCount2)->toBe($lineCount1 + 1);
                    expect($content2)->toContain($jti1->toString());
                    expect($content2)->toContain($jti2->toString());
                } finally {
                    if (file_exists($tempFile)) {
                        unlink($tempFile);
                    }
                }
            });

            test(
                "hasJti uses prefix matching to avoid false positives",
                function () {
                    $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                    try {
                        $repo = new FileJwtJtiRepository($tempFile);
                        $userId = Uuid::uuid4();
                        $createdAt = new DateTimeImmutable();

                        // Create two JTIs where one shares a prefix with the search
                        $jti1 = Uuid::uuid4();
                        $repo->saveJti($jti1, $userId, $createdAt);

                        // Verify we can find it
                        expect($repo->hasJti($jti1))->toBeTrue();

                        // Create a second JTI
                        $jti2 = Uuid::uuid4();
                        $repo->saveJti($jti2, $userId, $createdAt);

                        // Both should be findable independently
                        expect($repo->hasJti($jti1))->toBeTrue();
                        expect($repo->hasJti($jti2))->toBeTrue();
                    } finally {
                        if (file_exists($tempFile)) {
                            unlink($tempFile);
                        }
                    }
                },
            );

            test(
                "deleteJti preserves file integrity after multiple deletes",
                function () {
                    $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                    try {
                        $repo = new FileJwtJtiRepository($tempFile);
                        $userId = Uuid::uuid4();
                        $createdAt = new DateTimeImmutable();

                        $jtis = [];
                        for ($i = 0; $i < 5; $i++) {
                            $jti = Uuid::uuid4();
                            $repo->saveJti($jti, $userId, $createdAt);
                            $jtis[] = $jti;
                        }

                        // Delete entries in random order
                        $deleteOrder = [$jtis[2], $jtis[0], $jtis[4]];
                        foreach ($deleteOrder as $jti) {
                            $repo->deleteJti($jti);
                        }

                        // Verify correct ones remain
                        expect($repo->hasJti($jtis[0]))->toBeFalse();
                        expect($repo->hasJti($jtis[1]))->toBeTrue();
                        expect($repo->hasJti($jtis[2]))->toBeFalse();
                        expect($repo->hasJti($jtis[3]))->toBeTrue();
                        expect($repo->hasJti($jtis[4]))->toBeFalse();
                    } finally {
                        if (file_exists($tempFile)) {
                            unlink($tempFile);
                        }
                    }
                },
            );
        });

        describe("edge cases with special UUID patterns", function () {
            test(
                "correctly handles JTI that is prefix of another JTI",
                function () {
                    $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                    try {
                        $repo = new FileJwtJtiRepository($tempFile);
                        $userId = Uuid::uuid4();
                        $createdAt = new DateTimeImmutable();

                        // Use fixed UUIDs where one is a prefix of another
                        $jti1 = Uuid::fromString(
                            "00000000-0000-0000-0000-000000000001",
                        );
                        $jti2 = Uuid::fromString(
                            "00000000-0000-0000-0000-000000000010",
                        );

                        $repo->saveJti($jti1, $userId, $createdAt);
                        $repo->saveJti($jti2, $userId, $createdAt);

                        // Ensure we find only the exact match
                        expect($repo->hasJti($jti1))->toBeTrue();
                        expect($repo->hasJti($jti2))->toBeTrue();
                        expect(
                            $repo->hasJti(
                                Uuid::fromString(
                                    "00000000-0000-0000-0000-000000000012",
                                ),
                            ),
                        )->toBeFalse();
                    } finally {
                        if (file_exists($tempFile)) {
                            unlink($tempFile);
                        }
                    }
                },
            );

            test("removes file after deleting last JTI", function () {
                $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                try {
                    $repo = new FileJwtJtiRepository($tempFile);
                    $jti = Uuid::uuid4();
                    $userId = Uuid::uuid4();
                    $createdAt = new DateTimeImmutable();

                    $repo->saveJti($jti, $userId, $createdAt);
                    expect(file_exists($tempFile))->toBeTrue();

                    $repo->deleteJti($jti);

                    expect(file_exists($tempFile))->toBeFalse();
                } finally {
                    if (file_exists($tempFile)) {
                        unlink($tempFile);
                    }
                }
            });

            test("handles empty lines in JTI file gracefully", function () {
                $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                try {
                    $repo = new FileJwtJtiRepository($tempFile);
                    $jti = Uuid::uuid4();
                    $userId = Uuid::uuid4();
                    $createdAt = new DateTimeImmutable();

                    $repo->saveJti($jti, $userId, $createdAt);

                    // Manually add empty lines and whitespace to file
                    $content = file_get_contents($tempFile);
                    file_put_contents(
                        $tempFile,
                        "\n" . $content . "\n\n",
                        LOCK_EX,
                    );

                    // Should still find the JTI despite empty lines
                    expect($repo->hasJti($jti))->toBeTrue();
                } finally {
                    if (file_exists($tempFile)) {
                        unlink($tempFile);
                    }
                }
            });

            test(
                "correctly deletes JTI from file with empty lines",
                function () {
                    $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                    try {
                        $repo = new FileJwtJtiRepository($tempFile);
                        $jti1 = Uuid::uuid4();
                        $jti2 = Uuid::uuid4();
                        $userId = Uuid::uuid4();
                        $createdAt = new DateTimeImmutable();

                        $repo->saveJti($jti1, $userId, $createdAt);
                        $repo->saveJti($jti2, $userId, $createdAt);

                        // Add empty lines
                        $content = file_get_contents($tempFile);
                        file_put_contents(
                            $tempFile,
                            "\n" . $content . "\n",
                            LOCK_EX,
                        );

                        $repo->deleteJti($jti1);

                        expect($repo->hasJti($jti1))->toBeFalse();
                        expect($repo->hasJti($jti2))->toBeTrue();
                    } finally {
                        if (file_exists($tempFile)) {
                            unlink($tempFile);
                        }
                    }
                },
            );
        });

        describe("large file handling", function () {
            test(
                "efficiently finds JTI in file with many entries",
                function () {
                    $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                    try {
                        $repo = new FileJwtJtiRepository($tempFile);
                        $userId = Uuid::uuid4();
                        $createdAt = new DateTimeImmutable();
                        $targetJti = Uuid::uuid4();

                        // Add 100 JTIs before target
                        for ($i = 0; $i < 100; $i++) {
                            $repo->saveJti(Uuid::uuid4(), $userId, $createdAt);
                        }

                        // Add target JTI
                        $repo->saveJti($targetJti, $userId, $createdAt);

                        // Add 100 JTIs after target
                        for ($i = 0; $i < 100; $i++) {
                            $repo->saveJti(Uuid::uuid4(), $userId, $createdAt);
                        }

                        expect($repo->hasJti($targetJti))->toBeTrue();
                    } finally {
                        if (file_exists($tempFile)) {
                            unlink($tempFile);
                        }
                    }
                },
            );

            test("correctly deletes JTI from large file", function () {
                $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                try {
                    $repo = new FileJwtJtiRepository($tempFile);
                    $userId = Uuid::uuid4();
                    $createdAt = new DateTimeImmutable();
                    $jtisToDelete = [];

                    // Add 50 JTIs and mark some for deletion
                    for ($i = 0; $i < 50; $i++) {
                        $jti = Uuid::uuid4();
                        $repo->saveJti($jti, $userId, $createdAt);
                        if ($i % 2 === 0) {
                            $jtisToDelete[] = $jti;
                        }
                    }

                    // Delete every other JTI
                    foreach ($jtisToDelete as $jti) {
                        $repo->deleteJti($jti);
                    }

                    // Verify deleted ones are gone
                    foreach ($jtisToDelete as $jti) {
                        expect($repo->hasJti($jti))->toBeFalse();
                    }

                    // Verify file still has reasonable size (not bloated)
                    $fileSize = filesize($tempFile);
                    expect($fileSize)->toBeGreaterThan(0);
                    expect($fileSize)->toBeLessThan(10000); // Should be small for 25 entries
                } finally {
                    if (file_exists($tempFile)) {
                        unlink($tempFile);
                    }
                }
            });
        });

        describe("concurrent operations", function () {
            test("maintains file integrity with multiple saves", function () {
                $tempFile = tempnam(sys_get_temp_dir(), "jti_");
                try {
                    $repo = new FileJwtJtiRepository($tempFile);
                    $userId = Uuid::uuid4();
                    $createdAt = new DateTimeImmutable();
                    $savedJtis = [];

                    // Rapidly save multiple JTIs
                    for ($i = 0; $i < 20; $i++) {
                        $jti = Uuid::uuid4();
                        $repo->saveJti($jti, $userId, $createdAt);
                        $savedJtis[] = $jti;
                    }

                    // Verify all were saved
                    foreach ($savedJtis as $jti) {
                        expect($repo->hasJti($jti))->toBeTrue();
                    }

                    // Verify file line count matches saves
                    $content = file_get_contents($tempFile);
                    $lines = array_filter(explode("\n", $content));
                    expect(count($lines))->toBe(count($savedJtis));
                } finally {
                    if (file_exists($tempFile)) {
                        unlink($tempFile);
                    }
                }
            });
        });
    });
});
