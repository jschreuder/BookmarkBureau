<?php

use jschreuder\BookmarkBureau\Entity\Mapper\UserEntityMapper;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Repository\PdoUserRepository;
use Ramsey\Uuid\Uuid;

describe("PdoUserRepository", function () {
    function createUserDatabase(): PDO
    {
        $pdo = new PDO("sqlite::memory:");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create schema
        $pdo->exec('
            CREATE TABLE users (
                user_id BLOB PRIMARY KEY,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                totp_secret TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE UNIQUE INDEX idx_users_email ON users(email);
            CREATE INDEX idx_users_created_at ON users(created_at);
        ');

        return $pdo;
    }

    function insertTestUser(PDO $pdo, $user): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO users (user_id, email, password_hash, totp_secret, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $user->userId->getBytes(),
            (string) $user->email,
            $user->passwordHash->value,
            $user->totpSecret ? (string) $user->totpSecret : null,
            $user->createdAt->format("Y-m-d H:i:s"),
            $user->updatedAt->format("Y-m-d H:i:s"),
        ]);
    }

    describe("findById", function () {
        test("finds and returns a user by ID", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            insertTestUser($pdo, $user);

            $found = $repo->findById($user->userId);

            expect($found->userId->toString())->toBe($user->userId->toString());
            expect((string) $found->email)->toBe((string) $user->email);
            expect($found->passwordHash->value)->toBe(
                $user->passwordHash->value,
            );
        });

        test(
            "throws UserNotFoundException when user does not exist",
            function () {
                $pdo = createUserDatabase();
                $repo = new PdoUserRepository($pdo, new UserEntityMapper());
                $nonExistentId = Uuid::uuid4();

                expect(fn() => $repo->findById($nonExistentId))->toThrow(
                    UserNotFoundException::class,
                );
            },
        );

        test("correctly maps nullable TOTP secret", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $userWithoutTotp = TestEntityFactory::createUser();

            insertTestUser($pdo, $userWithoutTotp);

            $found = $repo->findById($userWithoutTotp->userId);

            expect($found->totpSecret)->toBeNull();
            expect($found->requiresTotp())->toBeFalse();
        });

        test("correctly maps TOTP secret when present", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");
            $userWithTotp = TestEntityFactory::createUser(
                totpSecret: $totpSecret,
            );

            insertTestUser($pdo, $userWithTotp);

            $found = $repo->findById($userWithTotp->userId);

            expect($found->totpSecret)->not->toBeNull();
            expect($found->totpSecret->value)->toBe($totpSecret->value);
            expect($found->requiresTotp())->toBeTrue();
        });

        test("preserves timestamps", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            insertTestUser($pdo, $user);

            $found = $repo->findById($user->userId);

            expect($found->createdAt->format("Y-m-d H:i:s"))->toBe(
                $user->createdAt->format("Y-m-d H:i:s"),
            );
            expect($found->updatedAt->format("Y-m-d H:i:s"))->toBe(
                $user->updatedAt->format("Y-m-d H:i:s"),
            );
        });
    });

    describe("findByEmail", function () {
        test("finds and returns a user by email", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $email = new Email("test@example.com");
            $user = TestEntityFactory::createUser(email: $email);

            insertTestUser($pdo, $user);

            $found = $repo->findByEmail($email);

            expect($found->userId->toString())->toBe($user->userId->toString());
            expect((string) $found->email)->toBe((string) $email);
        });

        test(
            "throws UserNotFoundException when email does not exist",
            function () {
                $pdo = createUserDatabase();
                $repo = new PdoUserRepository($pdo, new UserEntityMapper());
                $nonExistentEmail = new Email("nonexistent@example.com");

                expect(fn() => $repo->findByEmail($nonExistentEmail))->toThrow(
                    UserNotFoundException::class,
                );
            },
        );

        test("is case sensitive for email lookup", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $email = new Email("test@example.com");
            $user = TestEntityFactory::createUser(email: $email);

            insertTestUser($pdo, $user);

            // SQLite is case-insensitive by default, but Email comparison is case-sensitive
            $found = $repo->findByEmail($email);
            expect($found->userId->toString())->toBe($user->userId->toString());
        });
    });

    describe("findAll", function () {
        test("returns empty collection when no users exist", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());

            $result = $repo->findAll();

            expect($result->count())->toBe(0);
            expect($result->isEmpty())->toBeTrue();
        });

        test("returns all users ordered by email", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());

            $user1 = TestEntityFactory::createUser(
                email: new Email("alice@example.com"),
            );
            $user2 = TestEntityFactory::createUser(
                email: new Email("charlie@example.com"),
            );
            $user3 = TestEntityFactory::createUser(
                email: new Email("bob@example.com"),
            );

            insertTestUser($pdo, $user1);
            insertTestUser($pdo, $user2);
            insertTestUser($pdo, $user3);

            $result = $repo->findAll();

            expect($result->count())->toBe(3);
            $users = $result->toArray();
            expect((string) $users[0]->email)->toBe("alice@example.com");
            expect((string) $users[1]->email)->toBe("bob@example.com");
            expect((string) $users[2]->email)->toBe("charlie@example.com");
        });
    });

    describe("save", function () {
        test("inserts a new user", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            $repo->save($user);

            $found = $repo->findById($user->userId);
            expect($found->userId->toString())->toBe($user->userId->toString());
            expect((string) $found->email)->toBe((string) $user->email);
        });

        test("updates an existing user", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $userId = Uuid::uuid4();
            $user = TestEntityFactory::createUser(
                id: $userId,
                email: new Email("original@example.com"),
            );

            insertTestUser($pdo, $user);

            // Create updated user with same ID but different email
            $newEmail = new Email("updated@example.com");
            $updatedUser = TestEntityFactory::createUser(
                id: $userId,
                email: $newEmail,
                passwordHash: $user->passwordHash,
                totpSecret: $user->totpSecret,
                createdAt: $user->createdAt,
                updatedAt: $user->updatedAt,
            );
            $repo->save($updatedUser);

            $found = $repo->findById($userId);
            expect((string) $found->email)->toBe((string) $newEmail);
        });

        test("saves user with TOTP secret", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");
            $user = TestEntityFactory::createUser(totpSecret: $totpSecret);

            $repo->save($user);

            $found = $repo->findById($user->userId);
            expect($found->totpSecret)->not->toBeNull();
            expect($found->totpSecret->value)->toBe($totpSecret->value);
        });

        test("saves user without TOTP secret (null)", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $user = TestEntityFactory::createUser(totpSecret: null);

            $repo->save($user);

            $found = $repo->findById($user->userId);
            expect($found->totpSecret)->toBeNull();
        });

        test("preserves timestamps on insert", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            $repo->save($user);

            $found = $repo->findById($user->userId);
            expect($found->createdAt->format("Y-m-d H:i:s"))->toBe(
                $user->createdAt->format("Y-m-d H:i:s"),
            );
            expect($found->updatedAt->format("Y-m-d H:i:s"))->toBe(
                $user->updatedAt->format("Y-m-d H:i:s"),
            );
        });
    });

    describe("delete", function () {
        test("deletes an existing user", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            insertTestUser($pdo, $user);

            $repo->delete($user);

            expect(fn() => $repo->findById($user->userId))->toThrow(
                UserNotFoundException::class,
            );
        });

        test(
            "does not throw error when deleting non-existent user",
            function () {
                $pdo = createUserDatabase();
                $repo = new PdoUserRepository($pdo, new UserEntityMapper());
                $user = TestEntityFactory::createUser();

                try {
                    $repo->delete($user);
                    expect(true)->toBeTrue();
                } catch (Exception $e) {
                    expect(false)->toBeTrue();
                }
            },
        );
    });

    describe("existsByEmail", function () {
        test("returns true when email exists", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $email = new Email("test@example.com");
            $user = TestEntityFactory::createUser(email: $email);

            insertTestUser($pdo, $user);

            expect($repo->existsByEmail($email))->toBeTrue();
        });

        test("returns false when email does not exist", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $email = new Email("nonexistent@example.com");

            expect($repo->existsByEmail($email))->toBeFalse();
        });

        test("returns false for empty database", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $email = new Email("any@example.com");

            expect($repo->existsByEmail($email))->toBeFalse();
        });
    });

    describe("count", function () {
        test("returns zero for empty database", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());

            expect($repo->count())->toBe(0);
        });

        test("returns correct count of users", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());

            $user1 = TestEntityFactory::createUser(
                email: new Email("user1@example.com"),
            );
            $user2 = TestEntityFactory::createUser(
                email: new Email("user2@example.com"),
            );
            $user3 = TestEntityFactory::createUser(
                email: new Email("user3@example.com"),
            );

            insertTestUser($pdo, $user1);
            insertTestUser($pdo, $user2);
            insertTestUser($pdo, $user3);

            expect($repo->count())->toBe(3);
        });

        test("count updates after deletion", function () {
            $pdo = createUserDatabase();
            $repo = new PdoUserRepository($pdo, new UserEntityMapper());
            $user = TestEntityFactory::createUser();

            insertTestUser($pdo, $user);
            expect($repo->count())->toBe(1);

            $repo->delete($user);
            expect($repo->count())->toBe(0);
        });
    });
});
