<?php

use jschreuder\BookmarkBureau\Entity\Mapper\UserEntityMapper;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Exception\DuplicateUserException;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Repository\FileUserRepository;
use Ramsey\Uuid\Uuid;

describe("FileUserRepository", function () {
    function getTestFilePath(): string
    {
        return sys_get_temp_dir() . "/test_users_" . uniqid() . ".json";
    }

    function cleanupTestFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    describe("findById", function () {
        test("finds and returns a user by ID", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            $repo->insert($user);
            $found = $repo->findById($user->userId);

            expect($found->userId->toString())->toBe($user->userId->toString());
            expect((string) $found->email)->toBe((string) $user->email);
            expect($found->passwordHash->value)->toBe(
                $user->passwordHash->value,
            );

            cleanupTestFile($filePath);
        });

        test(
            "throws UserNotFoundException when user does not exist",
            function () {
                $filePath = getTestFilePath();
                $repo = new FileUserRepository(
                    $filePath,
                    new UserEntityMapper(),
                );
                $nonExistentId = Uuid::uuid4();

                expect(fn() => $repo->findById($nonExistentId))->toThrow(
                    UserNotFoundException::class,
                );

                cleanupTestFile($filePath);
            },
        );

        test(
            "throws UserNotFoundException when file does not exist",
            function () {
                $filePath = getTestFilePath();
                $repo = new FileUserRepository(
                    $filePath,
                    new UserEntityMapper(),
                );
                $userId = Uuid::uuid4();

                expect(fn() => $repo->findById($userId))->toThrow(
                    UserNotFoundException::class,
                );
            },
        );

        test("correctly maps nullable TOTP secret", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $userWithoutTotp = TestEntityFactory::createUser();

            $repo->insert($userWithoutTotp);
            $found = $repo->findById($userWithoutTotp->userId);

            expect($found->totpSecret)->toBeNull();
            expect($found->requiresTotp())->toBeFalse();

            cleanupTestFile($filePath);
        });

        test("correctly maps TOTP secret when present", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");
            $userWithTotp = TestEntityFactory::createUser(
                totpSecret: $totpSecret,
            );

            $repo->insert($userWithTotp);
            $found = $repo->findById($userWithTotp->userId);

            expect($found->totpSecret)->not->toBeNull();
            expect($found->totpSecret->value)->toBe($totpSecret->value);
            expect($found->requiresTotp())->toBeTrue();

            cleanupTestFile($filePath);
        });

        test("preserves timestamps", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            $repo->insert($user);
            $found = $repo->findById($user->userId);

            expect($found->createdAt->format("Y-m-d H:i:s"))->toBe(
                $user->createdAt->format("Y-m-d H:i:s"),
            );
            expect($found->updatedAt->format("Y-m-d H:i:s"))->toBe(
                $user->updatedAt->format("Y-m-d H:i:s"),
            );

            cleanupTestFile($filePath);
        });
    });

    describe("findByEmail", function () {
        test("finds and returns a user by email", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $email = new Email("test@example.com");
            $user = TestEntityFactory::createUser(email: $email);

            $repo->insert($user);
            $found = $repo->findByEmail($email);

            expect($found->userId->toString())->toBe($user->userId->toString());
            expect((string) $found->email)->toBe((string) $email);

            cleanupTestFile($filePath);
        });

        test(
            "throws UserNotFoundException when email does not exist",
            function () {
                $filePath = getTestFilePath();
                $repo = new FileUserRepository(
                    $filePath,
                    new UserEntityMapper(),
                );
                $nonExistentEmail = new Email("nonexistent@example.com");

                expect(fn() => $repo->findByEmail($nonExistentEmail))->toThrow(
                    UserNotFoundException::class,
                );

                cleanupTestFile($filePath);
            },
        );

        test("finds user from existing JSON file", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $email = new Email("stored@example.com");
            $user = TestEntityFactory::createUser(email: $email);

            $repo->insert($user);

            // Create new repo instance to load from file
            $repo2 = new FileUserRepository($filePath, new UserEntityMapper());
            $found = $repo2->findByEmail($email);

            expect($found->userId->toString())->toBe($user->userId->toString());

            cleanupTestFile($filePath);
        });
    });

    describe("findAll", function () {
        test("returns empty collection when no users exist", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());

            $result = $repo->findAll();

            expect($result->count())->toBe(0);
            expect($result->isEmpty())->toBeTrue();

            cleanupTestFile($filePath);
        });

        test("returns all users ordered by email", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());

            $user1 = TestEntityFactory::createUser(
                email: new Email("alice@example.com"),
            );
            $user2 = TestEntityFactory::createUser(
                email: new Email("charlie@example.com"),
            );
            $user3 = TestEntityFactory::createUser(
                email: new Email("bob@example.com"),
            );

            $repo->insert($user1);
            $repo->insert($user2);
            $repo->insert($user3);

            $result = $repo->findAll();

            expect($result->count())->toBe(3);
            $users = $result->toArray();
            expect((string) $users[0]->email)->toBe("alice@example.com");
            expect((string) $users[1]->email)->toBe("bob@example.com");
            expect((string) $users[2]->email)->toBe("charlie@example.com");

            cleanupTestFile($filePath);
        });

        test("loads users from existing JSON file", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());

            $user1 = TestEntityFactory::createUser(
                email: new Email("first@example.com"),
            );
            $user2 = TestEntityFactory::createUser(
                email: new Email("second@example.com"),
            );

            $repo->insert($user1);
            $repo->insert($user2);

            // Create new repo instance to load from file
            $repo2 = new FileUserRepository($filePath, new UserEntityMapper());
            $result = $repo2->findAll();

            expect($result->count())->toBe(2);

            cleanupTestFile($filePath);
        });
    });

    describe("insert", function () {
        test("inserts a new user", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            $repo->insert($user);

            $found = $repo->findById($user->userId);
            expect($found->userId->toString())->toBe($user->userId->toString());
            expect((string) $found->email)->toBe((string) $user->email);

            cleanupTestFile($filePath);
        });

        test("saves user with TOTP secret", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");
            $user = TestEntityFactory::createUser(totpSecret: $totpSecret);

            $repo->insert($user);

            $found = $repo->findById($user->userId);
            expect($found->totpSecret)->not->toBeNull();
            expect($found->totpSecret->value)->toBe($totpSecret->value);

            cleanupTestFile($filePath);
        });

        test("saves user without TOTP secret (null)", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $user = TestEntityFactory::createUser(totpSecret: null);

            $repo->insert($user);

            $found = $repo->findById($user->userId);
            expect($found->totpSecret)->toBeNull();

            cleanupTestFile($filePath);
        });

        test("creates file if it does not exist", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            expect(file_exists($filePath))->toBeFalse();

            $repo->insert($user);

            expect(file_exists($filePath))->toBeTrue();

            cleanupTestFile($filePath);
        });

        test("creates directory if it does not exist", function () {
            $tempDir = sys_get_temp_dir() . "/test_users_" . uniqid();
            $filePath = $tempDir . "/users.json";
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            expect(is_dir($tempDir))->toBeFalse();

            $repo->insert($user);

            expect(is_dir($tempDir))->toBeTrue();
            expect(file_exists($filePath))->toBeTrue();

            unlink($filePath);
            rmdir($tempDir);
        });

        test("persists data to file", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $user = TestEntityFactory::createUser(
                email: new Email("test@example.com"),
            );

            $repo->insert($user);

            // Verify file contains valid JSON
            $content = file_get_contents($filePath);
            $data = json_decode($content, true);

            expect($data)->toBeArray();
            expect(count($data))->toBe(1);
            expect($data[0]["email"])->toBe("test@example.com");

            cleanupTestFile($filePath);
        });

        test(
            "throws DuplicateUserException when user already exists",
            function () {
                $filePath = getTestFilePath();
                $repo = new FileUserRepository(
                    $filePath,
                    new UserEntityMapper(),
                );
                $email = new Email("duplicate@example.com");
                $user1 = TestEntityFactory::createUser(email: $email);
                $user2 = TestEntityFactory::createUser(email: $email);

                $repo->insert($user1);

                expect(fn() => $repo->insert($user2))->toThrow(
                    DuplicateUserException::class,
                );

                cleanupTestFile($filePath);
            },
        );

        test(
            "throws DuplicateUserException when inserting user with same email from file",
            function () {
                $filePath = getTestFilePath();
                $repo1 = new FileUserRepository(
                    $filePath,
                    new UserEntityMapper(),
                );
                $email = new Email("stored@example.com");
                $user1 = TestEntityFactory::createUser(email: $email);

                $repo1->insert($user1);

                // Create new repo instance that loads from file
                $repo2 = new FileUserRepository(
                    $filePath,
                    new UserEntityMapper(),
                );
                $user2 = TestEntityFactory::createUser(email: $email);

                expect(fn() => $repo2->insert($user2))->toThrow(
                    DuplicateUserException::class,
                );

                cleanupTestFile($filePath);
            },
        );
    });

    describe("update", function () {
        test("updates user password", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $email = new Email("test@example.com");
            $user = TestEntityFactory::createUser(email: $email);

            $repo->insert($user);

            // Create updated user with same ID and email but different password
            $updatedUser = TestEntityFactory::createUser(
                id: $user->userId,
                email: $email,
                totpSecret: $user->totpSecret,
                createdAt: $user->createdAt,
                updatedAt: $user->updatedAt,
            );
            $repo->update($updatedUser);

            $found = $repo->findByEmail($email);
            expect($found->userId->toString())->toBe($user->userId->toString());

            cleanupTestFile($filePath);
        });

        test("updates user email address", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $originalEmail = new Email("original@example.com");
            $user = TestEntityFactory::createUser(email: $originalEmail);

            $repo->insert($user);

            $newEmail = new Email("updated@example.com");
            $updatedUser = TestEntityFactory::createUser(
                id: $user->userId,
                email: $newEmail,
                passwordHash: $user->passwordHash,
                totpSecret: $user->totpSecret,
                createdAt: $user->createdAt,
                updatedAt: $user->updatedAt,
            );
            $repo->update($updatedUser);

            $found = $repo->findByEmail($newEmail);
            expect($found->userId->toString())->toBe($user->userId->toString());
            expect((string) $found->email)->toBe((string) $newEmail);

            cleanupTestFile($filePath);
        });

        test("updates user TOTP secret", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $email = new Email("test@example.com");
            $user = TestEntityFactory::createUser(
                email: $email,
                totpSecret: null,
            );

            $repo->insert($user);

            $newTotpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");
            $updatedUser = TestEntityFactory::createUser(
                id: $user->userId,
                email: $email,
                totpSecret: $newTotpSecret,
                passwordHash: $user->passwordHash,
                createdAt: $user->createdAt,
                updatedAt: $user->updatedAt,
            );
            $repo->update($updatedUser);

            $found = $repo->findByEmail($email);
            expect($found->totpSecret)->not->toBeNull();
            expect($found->totpSecret->value)->toBe($newTotpSecret->value);

            cleanupTestFile($filePath);
        });

        test("persists updates to file", function () {
            $filePath = getTestFilePath();
            $repo1 = new FileUserRepository($filePath, new UserEntityMapper());
            $originalEmail = new Email("original@example.com");
            $user = TestEntityFactory::createUser(email: $originalEmail);

            $repo1->insert($user);

            $newEmail = new Email("updated@example.com");
            $updatedUser = TestEntityFactory::createUser(
                id: $user->userId,
                email: $newEmail,
                passwordHash: $user->passwordHash,
                totpSecret: $user->totpSecret,
                createdAt: $user->createdAt,
                updatedAt: $user->updatedAt,
            );
            $repo1->update($updatedUser);

            // Create new repo instance to load from file
            $repo2 = new FileUserRepository($filePath, new UserEntityMapper());
            $found = $repo2->findByEmail($newEmail);

            expect((string) $found->email)->toBe((string) $newEmail);

            cleanupTestFile($filePath);
        });

        test(
            "throws UserNotFoundException when user does not exist",
            function () {
                $filePath = getTestFilePath();
                $repo = new FileUserRepository(
                    $filePath,
                    new UserEntityMapper(),
                );
                $user = TestEntityFactory::createUser();

                expect(fn() => $repo->update($user))->toThrow(
                    UserNotFoundException::class,
                );

                cleanupTestFile($filePath);
            },
        );

        test(
            "throws UserNotFoundException when user was deleted from file",
            function () {
                $filePath = getTestFilePath();
                $repo1 = new FileUserRepository(
                    $filePath,
                    new UserEntityMapper(),
                );
                $email = new Email("temporary@example.com");
                $user = TestEntityFactory::createUser(email: $email);

                $repo1->insert($user);

                // Create new repo instance that loads from file
                $repo2 = new FileUserRepository(
                    $filePath,
                    new UserEntityMapper(),
                );

                // Delete from first repo
                $repo1->delete($user);

                // Try to update from second repo - should fail since user was deleted
                $updatedUser = TestEntityFactory::createUser(
                    id: $user->userId,
                    email: $email,
                    passwordHash: $user->passwordHash,
                    totpSecret: $user->totpSecret,
                    createdAt: $user->createdAt,
                    updatedAt: $user->updatedAt,
                );

                expect(fn() => $repo2->update($updatedUser))->toThrow(
                    UserNotFoundException::class,
                );

                cleanupTestFile($filePath);
            },
        );

        test(
            "throws DuplicateUserException when updating to email that already exists",
            function () {
                $filePath = getTestFilePath();
                $repo = new FileUserRepository(
                    $filePath,
                    new UserEntityMapper(),
                );
                $email1 = new Email("user1@example.com");
                $email2 = new Email("user2@example.com");
                $user1 = TestEntityFactory::createUser(email: $email1);
                $user2 = TestEntityFactory::createUser(email: $email2);

                $repo->insert($user1);
                $repo->insert($user2);

                // Try to update user1 to use user2's email
                $updatedUser1 = TestEntityFactory::createUser(
                    id: $user1->userId,
                    email: $email2,
                    passwordHash: $user1->passwordHash,
                    totpSecret: $user1->totpSecret,
                    createdAt: $user1->createdAt,
                    updatedAt: $user1->updatedAt,
                );

                expect(fn() => $repo->update($updatedUser1))->toThrow(
                    DuplicateUserException::class,
                );

                cleanupTestFile($filePath);
            },
        );
    });

    describe("delete", function () {
        test("deletes an existing user", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            $repo->insert($user);
            $repo->delete($user);

            expect(fn() => $repo->findById($user->userId))->toThrow(
                UserNotFoundException::class,
            );

            cleanupTestFile($filePath);
        });

        test(
            "does not throw error when deleting non-existent user",
            function () {
                $filePath = getTestFilePath();
                $repo = new FileUserRepository(
                    $filePath,
                    new UserEntityMapper(),
                );
                $user = TestEntityFactory::createUser();

                try {
                    $repo->delete($user);
                    expect(true)->toBeTrue();
                } catch (Exception $e) {
                    expect(false)->toBeTrue();
                }

                cleanupTestFile($filePath);
            },
        );

        test("persists deletion to file", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            $repo->insert($user);
            $repo->delete($user);

            // Create new repo instance to load from file
            $repo2 = new FileUserRepository($filePath, new UserEntityMapper());

            expect(fn() => $repo2->findById($user->userId))->toThrow(
                UserNotFoundException::class,
            );

            cleanupTestFile($filePath);
        });
    });

    describe("existsByEmail", function () {
        test("returns true when email exists", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $email = new Email("test@example.com");
            $user = TestEntityFactory::createUser(email: $email);

            $repo->insert($user);

            expect($repo->existsByEmail($email))->toBeTrue();

            cleanupTestFile($filePath);
        });

        test("returns false when email does not exist", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $email = new Email("nonexistent@example.com");

            expect($repo->existsByEmail($email))->toBeFalse();

            cleanupTestFile($filePath);
        });

        test("returns false for empty database", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $email = new Email("any@example.com");

            expect($repo->existsByEmail($email))->toBeFalse();

            cleanupTestFile($filePath);
        });

        test("loads from file to check existence", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $email = new Email("stored@example.com");
            $user = TestEntityFactory::createUser(email: $email);

            $repo->insert($user);

            // Create new repo instance to load from file
            $repo2 = new FileUserRepository($filePath, new UserEntityMapper());

            expect($repo2->existsByEmail($email))->toBeTrue();

            cleanupTestFile($filePath);
        });
    });

    describe("count", function () {
        test("returns zero for empty database", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());

            expect($repo->count())->toBe(0);

            cleanupTestFile($filePath);
        });

        test("returns correct count of users", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());

            $user1 = TestEntityFactory::createUser(
                email: new Email("user1@example.com"),
            );
            $user2 = TestEntityFactory::createUser(
                email: new Email("user2@example.com"),
            );
            $user3 = TestEntityFactory::createUser(
                email: new Email("user3@example.com"),
            );

            $repo->insert($user1);
            $repo->insert($user2);
            $repo->insert($user3);

            expect($repo->count())->toBe(3);

            cleanupTestFile($filePath);
        });

        test("count updates after deletion", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            $repo->insert($user);
            expect($repo->count())->toBe(1);

            $repo->delete($user);
            expect($repo->count())->toBe(0);

            cleanupTestFile($filePath);
        });

        test("loads from file to get count", function () {
            $filePath = getTestFilePath();
            $repo = new FileUserRepository($filePath, new UserEntityMapper());

            $user1 = TestEntityFactory::createUser(
                email: new Email("user1@example.com"),
            );
            $user2 = TestEntityFactory::createUser(
                email: new Email("user2@example.com"),
            );

            $repo->insert($user1);
            $repo->insert($user2);

            // Create new repo instance to load from file
            $repo2 = new FileUserRepository($filePath, new UserEntityMapper());

            expect($repo2->count())->toBe(2);

            cleanupTestFile($filePath);
        });
    });

    describe("file persistence", function () {
        test("maintains data across repository instances", function () {
            $filePath = getTestFilePath();

            // First instance: save users
            $repo1 = new FileUserRepository($filePath, new UserEntityMapper());
            $user1 = TestEntityFactory::createUser(
                email: new Email("user1@example.com"),
            );
            $user2 = TestEntityFactory::createUser(
                email: new Email("user2@example.com"),
            );

            $repo1->insert($user1);
            $repo1->insert($user2);

            // Second instance: load and verify
            $repo2 = new FileUserRepository($filePath, new UserEntityMapper());
            expect($repo2->count())->toBe(2);
            expect(
                (string) $repo2->findByEmail(new Email("user1@example.com"))
                    ->email,
            )->toBe("user1@example.com");
            expect(
                (string) $repo2->findByEmail(new Email("user2@example.com"))
                    ->email,
            )->toBe("user2@example.com");

            cleanupTestFile($filePath);
        });

        test("handles concurrent access by reloading file", function () {
            $filePath = getTestFilePath();

            // First instance: save a user
            $repo1 = new FileUserRepository($filePath, new UserEntityMapper());
            $user1 = TestEntityFactory::createUser(
                email: new Email("user1@example.com"),
            );
            $repo1->insert($user1);

            // Second instance loads from file
            $repo2 = new FileUserRepository($filePath, new UserEntityMapper());

            // First instance saves another user
            $user2 = TestEntityFactory::createUser(
                email: new Email("user2@example.com"),
            );
            $repo1->insert($user2);

            // Second instance should see both users when calling methods
            // (Note: this tests that repo2 reloads the file)
            expect($repo2->count())->toBe(2);

            cleanupTestFile($filePath);
        });
    });
});
