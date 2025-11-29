<?php

use jschreuder\BookmarkBureau\Composite\UserCollection;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Exception\DuplicateEmailException;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;
use jschreuder\BookmarkBureau\Service\PasswordHasherInterface;
use jschreuder\BookmarkBureau\Service\UserService;
use jschreuder\BookmarkBureau\Service\UserServicePipelines;
use Ramsey\Uuid\Uuid;

describe("UserService", function () {
    describe("createUser method", function () {
        test(
            "creates a new user with email and plaintext password",
            function () {
                $email = new Email("test@example.com");
                $plainPassword = "SecurePassword123!";
                $hashedPassword = new HashedPassword(
                    password_hash($plainPassword, PASSWORD_ARGON2ID),
                );

                $userRepository = Mockery::mock(UserRepositoryInterface::class);
                $userRepository
                    ->shouldReceive("hasUserWithEmail")
                    ->with($email)
                    ->andReturn(false);
                $userRepository->shouldReceive("insert")->once();

                $passwordHasher = Mockery::mock(PasswordHasherInterface::class);
                $passwordHasher
                    ->shouldReceive("hash")
                    ->with($plainPassword)
                    ->andReturn($hashedPassword);

                $service = new UserService(
                    $userRepository,
                    $passwordHasher,
                    new UserServicePipelines(),
                );

                $result = $service->createUser($email, $plainPassword);

                expect($result)->toBeInstanceOf(User::class);
                expect($result->email)->toBe($email);
                expect($result->passwordHash)->toBe($hashedPassword);
                expect($result->totpSecret)->toBeNull();
            },
        );

        test(
            "throws DuplicateEmailException when email already exists",
            function () {
                $email = new Email("existing@example.com");
                $plainPassword = "SecurePassword123!";

                $userRepository = Mockery::mock(UserRepositoryInterface::class);
                $userRepository
                    ->shouldReceive("hasUserWithEmail")
                    ->with($email)
                    ->andReturn(true);

                $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

                $service = new UserService(
                    $userRepository,
                    $passwordHasher,
                    new UserServicePipelines(),
                );

                expect(
                    fn() => $service->createUser($email, $plainPassword),
                )->toThrow(DuplicateEmailException::class);
            },
        );

        test("hashes the password before storing", function () {
            $email = new Email("test@example.com");
            $plainPassword = "MyPassword123!";
            $hashedPassword = new HashedPassword(
                '$argon2id$v=19$m=65536,t=4,p=1$...',
            );

            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $userRepository
                ->shouldReceive("hasUserWithEmail")
                ->with($email)
                ->andReturn(false);
            $userRepository->shouldReceive("insert")->once();

            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);
            $passwordHasher
                ->shouldReceive("hash")
                ->with($plainPassword)
                ->andReturn($hashedPassword);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $result = $service->createUser($email, $plainPassword);

            expect($result->passwordHash)->toBe($hashedPassword);
        });
    });

    describe("getUser method", function () {
        test("retrieves a user by ID", function () {
            $userId = Uuid::uuid4();
            $user = TestEntityFactory::createUser(id: $userId);

            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $userRepository
                ->shouldReceive("findById")
                ->with($userId)
                ->andReturn($user);

            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $result = $service->getUser($userId);

            expect($result)->toBe($user);
            expect($result->userId)->toEqual($userId);
        });

        test(
            "throws UserNotFoundException when user does not exist",
            function () {
                $userId = Uuid::uuid4();

                $userRepository = Mockery::mock(UserRepositoryInterface::class);
                $userRepository
                    ->shouldReceive("findById")
                    ->with($userId)
                    ->andThrow(new UserNotFoundException("User not found"));

                $passwordHasher = Mockery::mock(PasswordHasherInterface::class);
                $service = new UserService(
                    $userRepository,
                    $passwordHasher,
                    new UserServicePipelines(),
                );

                expect(fn() => $service->getUser($userId))->toThrow(
                    UserNotFoundException::class,
                );
            },
        );
    });

    describe("getUserByEmail method", function () {
        test("retrieves a user by email", function () {
            $email = new Email("test@example.com");
            $user = TestEntityFactory::createUser(email: $email);

            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $userRepository
                ->shouldReceive("findByEmail")
                ->with($email)
                ->andReturn($user);

            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $result = $service->getUserByEmail($email);

            expect($result)->toBe($user);
            expect($result->email)->toBe($email);
        });

        test(
            "throws UserNotFoundException when user with email does not exist",
            function () {
                $email = new Email("nonexistent@example.com");

                $userRepository = Mockery::mock(UserRepositoryInterface::class);
                $userRepository
                    ->shouldReceive("findByEmail")
                    ->with($email)
                    ->andThrow(new UserNotFoundException("User not found"));

                $passwordHasher = Mockery::mock(PasswordHasherInterface::class);
                $service = new UserService(
                    $userRepository,
                    $passwordHasher,
                    new UserServicePipelines(),
                );

                expect(fn() => $service->getUserByEmail($email))->toThrow(
                    UserNotFoundException::class,
                );
            },
        );
    });

    describe("listAllUsers method", function () {
        test("returns empty collection when no users exist", function () {
            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $userRepository
                ->shouldReceive("listAll")
                ->andReturn(new UserCollection());

            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $result = $service->listAllUsers();

            expect($result)->toBeInstanceOf(UserCollection::class);
            expect($result->count())->toBe(0);
        });

        test("returns collection with all users", function () {
            $user1 = TestEntityFactory::createUser();
            $user2 = TestEntityFactory::createUser();
            $user3 = TestEntityFactory::createUser();
            $userCollection = new UserCollection($user1, $user2, $user3);

            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $userRepository
                ->shouldReceive("listAll")
                ->andReturn($userCollection);

            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $result = $service->listAllUsers();

            expect($result->count())->toBe(3);
            $users = $result->toArray();
            expect($users[0])->toBe($user1);
            expect($users[1])->toBe($user2);
            expect($users[2])->toBe($user3);
        });
    });

    describe("deleteUser method", function () {
        test("deletes an existing user", function () {
            $userId = Uuid::uuid4();
            $user = TestEntityFactory::createUser(id: $userId);

            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $userRepository
                ->shouldReceive("findById")
                ->with($userId)
                ->andReturn($user);
            $userRepository->shouldReceive("delete")->with($user)->once();

            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $service->deleteUser($userId);

            expect(true)->toBeTrue(); // Mockery validates delete was called
        });

        test(
            "throws UserNotFoundException when user does not exist",
            function () {
                $userId = Uuid::uuid4();

                $userRepository = Mockery::mock(UserRepositoryInterface::class);
                $userRepository
                    ->shouldReceive("findById")
                    ->with($userId)
                    ->andThrow(new UserNotFoundException("User not found"));

                $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

                $service = new UserService(
                    $userRepository,
                    $passwordHasher,
                    new UserServicePipelines(),
                );

                expect(fn() => $service->deleteUser($userId))->toThrow(
                    UserNotFoundException::class,
                );
            },
        );
    });

    describe("changePassword method", function () {
        test("updates user password", function () {
            $userId = Uuid::uuid4();
            $user = TestEntityFactory::createUser(id: $userId);
            $newPlainPassword = "NewPassword456!";
            $newHashedPassword = new HashedPassword(
                password_hash($newPlainPassword, PASSWORD_ARGON2ID),
            );

            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $userRepository
                ->shouldReceive("findById")
                ->with($userId)
                ->andReturn($user);
            $userRepository->shouldReceive("update")->with($user)->once();

            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);
            $passwordHasher
                ->shouldReceive("hash")
                ->with($newPlainPassword)
                ->andReturn($newHashedPassword);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $service->changePassword($userId, $newPlainPassword);

            expect($user->passwordHash)->toBe($newHashedPassword);
        });

        test(
            "throws UserNotFoundException when user does not exist",
            function () {
                $userId = Uuid::uuid4();
                $newPlainPassword = "NewPassword456!";

                $userRepository = Mockery::mock(UserRepositoryInterface::class);
                $userRepository
                    ->shouldReceive("findById")
                    ->with($userId)
                    ->andThrow(new UserNotFoundException("User not found"));

                $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

                $service = new UserService(
                    $userRepository,
                    $passwordHasher,
                    new UserServicePipelines(),
                );

                expect(
                    fn() => $service->changePassword(
                        $userId,
                        $newPlainPassword,
                    ),
                )->toThrow(UserNotFoundException::class);
            },
        );
    });

    describe("verifyPassword method", function () {
        test("returns true when password is correct", function () {
            $plainPassword = "CorrectPassword123!";
            $user = TestEntityFactory::createUser(
                passwordHash: new HashedPassword(
                    password_hash($plainPassword, PASSWORD_ARGON2ID),
                ),
            );

            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);
            $passwordHasher
                ->shouldReceive("verify")
                ->with($plainPassword, $user->passwordHash)
                ->andReturn(true);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $result = $service->verifyPassword($user, $plainPassword);

            expect($result)->toBeTrue();
        });

        test("returns false when password is incorrect", function () {
            $correctPassword = "CorrectPassword123!";
            $wrongPassword = "WrongPassword456!";
            $user = TestEntityFactory::createUser(
                passwordHash: new HashedPassword(
                    password_hash($correctPassword, PASSWORD_ARGON2ID),
                ),
            );

            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);
            $passwordHasher
                ->shouldReceive("verify")
                ->with($wrongPassword, $user->passwordHash)
                ->andReturn(false);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $result = $service->verifyPassword($user, $wrongPassword);

            expect($result)->toBeFalse();
        });
    });

    describe("enableTotp method", function () {
        test("generates and enables TOTP secret for user", function () {
            $userId = Uuid::uuid4();
            $user = TestEntityFactory::createUser(
                id: $userId,
                totpSecret: null,
            );

            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $userRepository
                ->shouldReceive("findById")
                ->with($userId)
                ->andReturn($user);
            $userRepository->shouldReceive("update")->with($user)->once();

            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $result = $service->enableTotp($userId);

            expect($result)->toBeInstanceOf(TotpSecret::class);
            expect($user->totpSecret)->toBe($result);
            expect($user->requiresTotp())->toBeTrue();
        });

        test("generates valid Base32 TOTP secret", function () {
            $userId = Uuid::uuid4();
            $user = TestEntityFactory::createUser(
                id: $userId,
                totpSecret: null,
            );

            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $userRepository
                ->shouldReceive("findById")
                ->with($userId)
                ->andReturn($user);
            $userRepository->shouldReceive("update")->once();

            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $result = $service->enableTotp($userId);

            // Secret should be 32 Base32 characters (160 bits)
            $secret = $result->value;
            expect(strlen($secret))->toBe(32);
            expect($secret)->toMatch('/^[A-Z2-7]{32}$/');
        });

        test(
            "throws UserNotFoundException when user does not exist",
            function () {
                $userId = Uuid::uuid4();

                $userRepository = Mockery::mock(UserRepositoryInterface::class);
                $userRepository
                    ->shouldReceive("findById")
                    ->with($userId)
                    ->andThrow(new UserNotFoundException("User not found"));

                $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

                $service = new UserService(
                    $userRepository,
                    $passwordHasher,
                    new UserServicePipelines(),
                );

                expect(fn() => $service->enableTotp($userId))->toThrow(
                    UserNotFoundException::class,
                );
            },
        );
    });

    describe("disableTotp method", function () {
        test("disables TOTP for user", function () {
            $userId = Uuid::uuid4();
            $totpSecret = new TotpSecret("JBSWY3DPEHPK3PXP");
            $user = TestEntityFactory::createUser(
                id: $userId,
                totpSecret: $totpSecret,
            );

            $userRepository = Mockery::mock(UserRepositoryInterface::class);
            $userRepository
                ->shouldReceive("findById")
                ->with($userId)
                ->andReturn($user);
            $userRepository->shouldReceive("update")->with($user)->once();

            $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

            $service = new UserService(
                $userRepository,
                $passwordHasher,
                new UserServicePipelines(),
            );

            $service->disableTotp($userId);

            expect($user->totpSecret)->toBeNull();
            expect($user->requiresTotp())->toBeFalse();
        });

        test(
            "throws UserNotFoundException when user does not exist",
            function () {
                $userId = Uuid::uuid4();

                $userRepository = Mockery::mock(UserRepositoryInterface::class);
                $userRepository
                    ->shouldReceive("findById")
                    ->with($userId)
                    ->andThrow(new UserNotFoundException("User not found"));

                $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

                $service = new UserService(
                    $userRepository,
                    $passwordHasher,
                    new UserServicePipelines(),
                );

                expect(fn() => $service->disableTotp($userId))->toThrow(
                    UserNotFoundException::class,
                );
            },
        );
    });
});
