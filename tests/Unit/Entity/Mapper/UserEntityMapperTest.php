<?php

use jschreuder\BookmarkBureau\Entity\Mapper\UserEntityMapper;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use Ramsey\Uuid\Uuid;

describe("UserEntityMapper", function () {
    describe("getFields", function () {
        test("returns all user field names", function () {
            $mapper = new UserEntityMapper();
            $fields = $mapper->getFields();

            expect($fields)->toBe([
                "user_id",
                "email",
                "password_hash",
                "totp_secret",
                "created_at",
                "updated_at",
            ]);
        });
    });

    describe("supports", function () {
        test("returns true for User entities", function () {
            $mapper = new UserEntityMapper();
            $user = TestEntityFactory::createUser();

            expect($mapper->supports($user))->toBeTrue();
        });

        test("returns false for non-User entities", function () {
            $mapper = new UserEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();

            expect($mapper->supports($dashboard))->toBeFalse();
        });
    });

    describe("mapToEntity", function () {
        test("maps row data to User entity", function () {
            $mapper = new UserEntityMapper();
            $userId = Uuid::uuid4();
            $passwordHash = password_hash("test123", PASSWORD_BCRYPT);

            $data = [
                "user_id" => $userId->getBytes(),
                "email" => "user@example.com",
                "password_hash" => $passwordHash,
                "totp_secret" => null,
                "created_at" => "2024-03-01 08:00:00",
                "updated_at" => "2024-03-05 10:30:00",
            ];

            $user = $mapper->mapToEntity($data);

            expect($user)->toBeInstanceOf(User::class);
            expect($user->userId->equals($userId))->toBeTrue();
            expect((string) $user->email)->toBe("user@example.com");
            expect($user->passwordHash->getHash())->toBe($passwordHash);
            expect($user->totpSecret)->toBeNull();
            expect($user->createdAt->format("Y-m-d H:i:s"))->toBe(
                "2024-03-01 08:00:00",
            );
            expect($user->updatedAt->format("Y-m-d H:i:s"))->toBe(
                "2024-03-05 10:30:00",
            );
        });

        test("maps row data with totp_secret to User entity", function () {
            $mapper = new UserEntityMapper();
            $userId = Uuid::uuid4();
            $passwordHash = password_hash("test123", PASSWORD_BCRYPT);
            $totpSecret = "JBSWY3DPEBLW64TMMQQQQQQQQQQQQQQ";

            $data = [
                "user_id" => $userId->getBytes(),
                "email" => "totp@example.com",
                "password_hash" => $passwordHash,
                "totp_secret" => $totpSecret,
                "created_at" => "2024-03-01 08:00:00",
                "updated_at" => "2024-03-01 08:00:00",
            ];

            $user = $mapper->mapToEntity($data);

            expect($user->totpSecret)->toBeInstanceOf(TotpSecret::class);
            expect((string) $user->totpSecret)->toBe($totpSecret);
        });

        test(
            "throws InvalidArgumentException when required fields are missing",
            function () {
                $mapper = new UserEntityMapper();

                $data = [
                    "user_id" => Uuid::uuid4()->getBytes(),
                    "email" => "incomplete@example.com",
                    // Missing: password_hash, totp_secret, created_at, updated_at
                ];

                expect(fn() => $mapper->mapToEntity($data))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("mapToRow", function () {
        test("maps User entity to row array", function () {
            $mapper = new UserEntityMapper();
            $passwordHash = password_hash("password123", PASSWORD_BCRYPT);
            $user = TestEntityFactory::createUser(
                email: new Email("test@example.com"),
                passwordHash: new HashedPassword($passwordHash),
                totpSecret: null,
            );

            $row = $mapper->mapToRow($user);

            expect($row)->toHaveKey("user_id");
            expect($row)->toHaveKey("email");
            expect($row)->toHaveKey("password_hash");
            expect($row)->toHaveKey("totp_secret");
            expect($row)->toHaveKey("created_at");
            expect($row)->toHaveKey("updated_at");

            expect($row["user_id"])->toBe($user->userId->getBytes());
            expect($row["email"])->toBe("test@example.com");
            expect($row["password_hash"])->toBe($passwordHash);
            expect($row["totp_secret"])->toBeNull();
        });

        test("maps User entity with totp_secret to row array", function () {
            $mapper = new UserEntityMapper();
            $totpSecret = new TotpSecret("JBSWY3DPEBLW64TMMQQQQQQQQQQQQQQ");
            $user = TestEntityFactory::createUser(
                email: new Email("totp@example.com"),
                totpSecret: $totpSecret,
            );

            $row = $mapper->mapToRow($user);

            expect($row["totp_secret"])->toBe(
                "JBSWY3DPEBLW64TMMQQQQQQQQQQQQQQ",
            );
        });

        test("formats timestamps correctly", function () {
            $mapper = new UserEntityMapper();
            $createdAt = new DateTimeImmutable("2024-03-01 08:00:45");
            $updatedAt = new DateTimeImmutable("2024-03-05 10:30:20");
            $user = TestEntityFactory::createUser(
                createdAt: $createdAt,
                updatedAt: $updatedAt,
            );

            $row = $mapper->mapToRow($user);

            expect($row["created_at"])->toBe(
                $createdAt->format(SqlFormat::TIMESTAMP),
            );
            expect($row["updated_at"])->toBe(
                $updatedAt->format(SqlFormat::TIMESTAMP),
            );
        });

        test(
            "throws InvalidArgumentException when entity is not supported",
            function () {
                $mapper = new UserEntityMapper();
                $dashboard = TestEntityFactory::createDashboard();

                expect(fn() => $mapper->mapToRow($dashboard))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("round-trip mapping", function () {
        test("maps entity to row and back preserves all data", function () {
            $mapper = new UserEntityMapper();
            $passwordHash = password_hash("roundtrip123", PASSWORD_BCRYPT);
            $originalUser = TestEntityFactory::createUser(
                email: new Email("roundtrip@example.com"),
                passwordHash: new HashedPassword($passwordHash),
                totpSecret: null,
            );

            $row = $mapper->mapToRow($originalUser);
            $restoredUser = $mapper->mapToEntity($row);

            expect(
                $restoredUser->userId->equals($originalUser->userId),
            )->toBeTrue();
            expect((string) $restoredUser->email)->toBe(
                (string) $originalUser->email,
            );
            expect($restoredUser->passwordHash->getHash())->toBe(
                $originalUser->passwordHash->getHash(),
            );
            expect($restoredUser->totpSecret)->toBeNull();
        });

        test(
            "round-trip mapping with totp_secret preserves secret",
            function () {
                $mapper = new UserEntityMapper();
                $totpSecret = new TotpSecret("JBSWY3DPEBLW64TMMQQQQQQQQQQQQQQ");
                $originalUser = TestEntityFactory::createUser(
                    email: new Email("totp@example.com"),
                    totpSecret: $totpSecret,
                );

                $row = $mapper->mapToRow($originalUser);
                $restoredUser = $mapper->mapToEntity($row);

                expect($restoredUser->totpSecret)->toBeInstanceOf(
                    TotpSecret::class,
                );
                expect((string) $restoredUser->totpSecret)->toBe(
                    "JBSWY3DPEBLW64TMMQQQQQQQQQQQQQQ",
                );
            },
        );

        test("preserves email and password through round-trip", function () {
            $mapper = new UserEntityMapper();
            $email = "preserve@example.com";
            $passwordHash = password_hash("preserve123", PASSWORD_BCRYPT);
            $originalUser = TestEntityFactory::createUser(
                email: new Email($email),
                passwordHash: new HashedPassword($passwordHash),
            );

            $row = $mapper->mapToRow($originalUser);
            $restoredUser = $mapper->mapToEntity($row);

            expect((string) $restoredUser->email)->toBe($email);
            expect($restoredUser->passwordHash->getHash())->toBe($passwordHash);
        });
    });
});
