<?php

use jschreuder\BookmarkBureau\Repository\PdoJwtJtiRepository;
use Ramsey\Uuid\Uuid;

describe("PdoJwtJtiRepository", function () {
    function createJtiDatabase(): PDO
    {
        $pdo = new PDO("sqlite::memory:");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create schema
        $pdo->exec('
            CREATE TABLE jwt_jti (
                jti BLOB PRIMARY KEY,
                user_id BLOB NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE INDEX idx_jwt_jti_user_id ON jwt_jti(user_id);
        ');

        return $pdo;
    }

    describe("saveJti", function () {
        test("saves a new JTI to the whitelist", function () {
            $pdo = createJtiDatabase();
            $repo = new PdoJwtJtiRepository($pdo);
            $jti = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti, $userId, $createdAt);

            // Verify it was saved
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM jwt_jti WHERE jti = ?",
            );
            $stmt->execute([$jti->getBytes()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            expect($result["count"])->toBe(1);
        });

        test("saves JTI with correct user_id", function () {
            $pdo = createJtiDatabase();
            $repo = new PdoJwtJtiRepository($pdo);
            $jti = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti, $userId, $createdAt);

            $stmt = $pdo->prepare("SELECT user_id FROM jwt_jti WHERE jti = ?");
            $stmt->execute([$jti->getBytes()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            expect($result["user_id"])->toBe($userId->getBytes());
        });

        test(
            "throws RepositoryStorageException on database error",
            function () {
                $pdo = Mockery::mock(PDO::class);
                $pdo->shouldReceive("prepare")->andThrow(
                    new PDOException("Database error"),
                );

                $repo = new PdoJwtJtiRepository($pdo);
                $jti = Uuid::uuid4();
                $userId = Uuid::uuid4();
                $createdAt = new DateTimeImmutable();

                expect(
                    fn() => $repo->saveJti($jti, $userId, $createdAt),
                )->toThrow(
                    jschreuder\BookmarkBureau\Exception\RepositoryStorageException::class,
                );
            },
        );
    });

    describe("hasJti", function () {
        test("returns true when JTI exists in whitelist", function () {
            $pdo = createJtiDatabase();
            $repo = new PdoJwtJtiRepository($pdo);
            $jti = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti, $userId, $createdAt);

            expect($repo->hasJti($jti))->toBeTrue();
        });

        test("returns false when JTI does not exist", function () {
            $pdo = createJtiDatabase();
            $repo = new PdoJwtJtiRepository($pdo);
            $jti = Uuid::uuid4();

            expect($repo->hasJti($jti))->toBeFalse();
        });

        test(
            "throws RepositoryStorageException on database error",
            function () {
                $pdo = Mockery::mock(PDO::class);
                $pdo->shouldReceive("prepare")->andThrow(
                    new PDOException("Database error"),
                );

                $repo = new PdoJwtJtiRepository($pdo);
                $jti = Uuid::uuid4();

                expect(fn() => $repo->hasJti($jti))->toThrow(
                    jschreuder\BookmarkBureau\Exception\RepositoryStorageException::class,
                );
            },
        );
    });

    describe("deleteJti", function () {
        test("deletes an existing JTI from the whitelist", function () {
            $pdo = createJtiDatabase();
            $repo = new PdoJwtJtiRepository($pdo);
            $jti = Uuid::uuid4();
            $userId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti, $userId, $createdAt);
            expect($repo->hasJti($jti))->toBeTrue();

            $repo->deleteJti($jti);

            expect($repo->hasJti($jti))->toBeFalse();
        });

        test(
            "does not throw error when deleting non-existent JTI",
            function () {
                $pdo = createJtiDatabase();
                $repo = new PdoJwtJtiRepository($pdo);
                $jti = Uuid::uuid4();

                $repo->deleteJti($jti); // Should not throw
                expect(true)->toBeTrue();
            },
        );

        test(
            "throws RepositoryStorageException on database error",
            function () {
                $pdo = Mockery::mock(PDO::class);
                $pdo->shouldReceive("prepare")->andThrow(
                    new PDOException("Database error"),
                );

                $repo = new PdoJwtJtiRepository($pdo);
                $jti = Uuid::uuid4();

                expect(fn() => $repo->deleteJti($jti))->toThrow(
                    jschreuder\BookmarkBureau\Exception\RepositoryStorageException::class,
                );
            },
        );
    });

    describe("multiple JTIs per user", function () {
        test("allows multiple JTIs for the same user", function () {
            $pdo = createJtiDatabase();
            $repo = new PdoJwtJtiRepository($pdo);
            $userId = Uuid::uuid4();
            $jti1 = Uuid::uuid4();
            $jti2 = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti1, $userId, $createdAt);
            $repo->saveJti($jti2, $userId, $createdAt);

            expect($repo->hasJti($jti1))->toBeTrue();
            expect($repo->hasJti($jti2))->toBeTrue();
        });

        test("can delete one JTI without affecting others", function () {
            $pdo = createJtiDatabase();
            $repo = new PdoJwtJtiRepository($pdo);
            $userId = Uuid::uuid4();
            $jti1 = Uuid::uuid4();
            $jti2 = Uuid::uuid4();
            $createdAt = new DateTimeImmutable();

            $repo->saveJti($jti1, $userId, $createdAt);
            $repo->saveJti($jti2, $userId, $createdAt);

            $repo->deleteJti($jti1);

            expect($repo->hasJti($jti1))->toBeFalse();
            expect($repo->hasJti($jti2))->toBeTrue();
        });
    });
});
