<?php

use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use Ramsey\Uuid\Uuid;

describe("User Entity", function () {
    describe("construction", function () {
        test("creates a user with all properties", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );
            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");
            $createdAt = new DateTimeImmutable("2024-01-01 12:00:00");
            $updatedAt = new DateTimeImmutable("2024-01-01 12:00:00");

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                $totpSecret,
                $createdAt,
                $updatedAt,
            );

            expect($user->userId)->toBe($userId);
            expect($user->email)->toBe($email);
            expect($user->passwordHash->getHash())->toBe(
                $passwordHash->getHash(),
            );
            expect($user->totpSecret)->toBe($totpSecret);
            expect($user->createdAt)->toBe($createdAt);
            expect($user->updatedAt)->toBe($updatedAt);
        });

        test("creates a user without TOTP", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );
            $createdAt = new DateTimeImmutable("2024-01-01 12:00:00");
            $updatedAt = new DateTimeImmutable("2024-01-01 12:00:00");

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                null,
                $createdAt,
                $updatedAt,
            );

            expect($user->userId)->toBe($userId);
            expect($user->email)->toBe($email);
            expect($user->passwordHash->getHash())->toBe(
                $passwordHash->getHash(),
            );
            expect($user->totpSecret)->toBeNull();
        });
    });

    describe("changePassword method", function () {
        test("changes the password hash", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $oldPasswordHash = new HashedPassword(
                password_hash("old-password", PASSWORD_ARGON2ID),
            );

            $user = new User(
                $userId,
                $email,
                $oldPasswordHash,
                null,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );

            $newPasswordHash = new HashedPassword(
                password_hash("new-password", PASSWORD_ARGON2ID),
            );
            $user->changePassword($newPasswordHash);

            expect($user->passwordHash)->toBe($newPasswordHash);
            expect($user->passwordHash->getHash())->not->toBe(
                $oldPasswordHash->getHash(),
            );
        });

        test("password change updates the updatedAt timestamp", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );
            $originalUpdatedAt = new DateTimeImmutable("2024-01-01 12:00:00");

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                null,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                $originalUpdatedAt,
            );

            // Directly assigning via property hook should trigger markAsUpdated
            $newPassword = new HashedPassword(
                password_hash("new-password", PASSWORD_ARGON2ID),
            );
            $user->passwordHash = $newPassword;

            expect($user->passwordHash)->toBe($newPassword);
            expect($user->updatedAt)->not->toBe($originalUpdatedAt);
        });
    });

    describe("changeTotpSecret method", function () {
        test("enables TOTP when previously disabled", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                null,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );
            expect($user->totpSecret)->toBeNull();

            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");
            $user->changeTotpSecret($totpSecret);

            expect($user->totpSecret)->toBe($totpSecret);
            expect($user->requiresTotp())->toBeTrue();
        });

        test("changes TOTP secret when already enabled", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );
            $oldSecret = new TotpSecret("JBSWY3DPEHPK3PXP");

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                $oldSecret,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );

            $newSecret = new TotpSecret("ABCDEFGHIJKLMNOP");
            $user->changeTotpSecret($newSecret);

            expect($user->totpSecret)->toBe($newSecret);
            expect($user->totpSecret->getSecret())->not->toBe(
                $oldSecret->getSecret(),
            );
        });

        test("TOTP secret change updates the updatedAt timestamp", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );
            $originalUpdatedAt = new DateTimeImmutable("2024-01-01 12:00:00");

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                null,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                $originalUpdatedAt,
            );

            // Directly assigning via property hook should trigger markAsUpdated
            $newSecret = new TotpSecret("JBSWY3DPEHPK3PXP");
            $user->totpSecret = $newSecret;

            expect($user->totpSecret)->toBe($newSecret);
            expect($user->updatedAt)->not->toBe($originalUpdatedAt);
        });
    });

    describe("disableTotp method", function () {
        test("disables TOTP authentication", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );
            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                $totpSecret,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );
            expect($user->requiresTotp())->toBeTrue();

            $user->disableTotp();

            expect($user->totpSecret)->toBeNull();
            expect($user->requiresTotp())->toBeFalse();
        });

        test("disabling TOTP is safe when already disabled", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                null,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );
            expect($user->requiresTotp())->toBeFalse();

            $user->disableTotp();

            expect($user->totpSecret)->toBeNull();
            expect($user->requiresTotp())->toBeFalse();
        });

        test("TOTP disable is explicit and intentional", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );
            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                $totpSecret,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );

            // Can set to null via property hook, which will trigger markAsUpdated
            $originalUpdatedAt = $user->updatedAt;
            $user->totpSecret = null;

            expect($user->totpSecret)->toBeNull();
            expect($user->updatedAt)->not->toBe($originalUpdatedAt);
        });
    });

    describe("requiresTotp method", function () {
        test("returns true when TOTP is enabled", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );
            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                $totpSecret,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );

            expect($user->requiresTotp())->toBeTrue();
        });

        test("returns false when TOTP is disabled", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                null,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );

            expect($user->requiresTotp())->toBeFalse();
        });

        test("returns false after disabling TOTP", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );
            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                $totpSecret,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );
            $user->disableTotp();

            expect($user->requiresTotp())->toBeFalse();
        });
    });

    describe("email property", function () {
        test("email is publicly readable", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                null,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );

            expect($user->email)->toBe($email);
            expect($user->email->value)->toBe("user@example.com");
        });

        test(
            "email can be changed via property hook and updates timestamp",
            function () {
                $userId = Uuid::uuid4();
                $email = new Email("user@example.com");
                $passwordHash = new HashedPassword(
                    password_hash("password", PASSWORD_ARGON2ID),
                );
                $originalUpdatedAt = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                );

                $user = new User(
                    $userId,
                    $email,
                    $passwordHash,
                    null,
                    new DateTimeImmutable("2024-01-01 12:00:00"),
                    $originalUpdatedAt,
                );

                // Can change email via property hook, which will trigger markAsUpdated
                $newEmail = new Email("other@example.com");
                $user->email = $newEmail;

                expect($user->email)->toBe($newEmail);
                expect($user->updatedAt)->not->toBe($originalUpdatedAt);
            },
        );
    });

    describe("userId property", function () {
        test("userId is publicly readable", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                null,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );

            expect($user->userId)->toBe($userId);
        });

        test("userId is immutable", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                null,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );

            // Cannot change userId due to readonly
            expect(fn() => ($user->userId = Uuid::uuid4()))->toThrow(
                Error::class,
            );
        });
    });

    describe("security properties", function () {
        test("passwordHash is publicly readable", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                null,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );

            expect($user->passwordHash)->toBe($passwordHash);
        });

        test(
            "passwordHash can be changed via property hook and updates timestamp",
            function () {
                $userId = Uuid::uuid4();
                $email = new Email("user@example.com");
                $passwordHash = new HashedPassword(
                    password_hash("password", PASSWORD_ARGON2ID),
                );
                $originalUpdatedAt = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                );

                $user = new User(
                    $userId,
                    $email,
                    $passwordHash,
                    null,
                    new DateTimeImmutable("2024-01-01 12:00:00"),
                    $originalUpdatedAt,
                );

                // Direct assignment via property hook should trigger markAsUpdated
                $newPassword = new HashedPassword(
                    password_hash("new-hash", PASSWORD_ARGON2ID),
                );
                $user->passwordHash = $newPassword;

                expect($user->passwordHash)->toBe($newPassword);
                expect($user->updatedAt)->not->toBe($originalUpdatedAt);
            },
        );

        test("totpSecret is publicly readable", function () {
            $userId = Uuid::uuid4();
            $email = new Email("user@example.com");
            $passwordHash = new HashedPassword(
                password_hash("password", PASSWORD_ARGON2ID),
            );
            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");

            $user = new User(
                $userId,
                $email,
                $passwordHash,
                $totpSecret,
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
            );

            expect($user->totpSecret)->toBe($totpSecret);
        });

        test(
            "totpSecret can be changed via property hook and updates timestamp",
            function () {
                $userId = Uuid::uuid4();
                $email = new Email("user@example.com");
                $passwordHash = new HashedPassword(
                    password_hash("password", PASSWORD_ARGON2ID),
                );
                $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");
                $originalUpdatedAt = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                );

                $user = new User(
                    $userId,
                    $email,
                    $passwordHash,
                    $totpSecret,
                    new DateTimeImmutable("2024-01-01 12:00:00"),
                    $originalUpdatedAt,
                );

                // Direct assignment via property hook should trigger markAsUpdated
                $newSecret = new TotpSecret("ABCDEFGHIJKLMNOP");
                $user->totpSecret = $newSecret;

                expect($user->totpSecret)->toBe($newSecret);
                expect($user->updatedAt)->not->toBe($originalUpdatedAt);
            },
        );
    });
});
