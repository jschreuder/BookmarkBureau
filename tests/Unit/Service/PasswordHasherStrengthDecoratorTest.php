<?php

use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Exception\WeakPasswordException;
use jschreuder\BookmarkBureau\Service\PasswordHasherInterface;
use jschreuder\BookmarkBureau\Service\PasswordHasherStrengthDecorator;

describe("PasswordHasherStrengthDecorator", function () {
    describe("hash method", function () {
        test(
            "delegates hash to wrapped hasher when password passes validation",
            function () {
                $plaintext = "ValidPassword123";
                $expectedHash = new HashedPassword(
                    password_hash($plaintext, PASSWORD_ARGON2ID),
                );

                $mockHasher = Mockery::mock(PasswordHasherInterface::class);
                $mockHasher
                    ->shouldReceive("hash")
                    ->with($plaintext)
                    ->once()
                    ->andReturn($expectedHash);

                $decorator = new PasswordHasherStrengthDecorator($mockHasher);
                $result = $decorator->hash($plaintext);

                expect($result)->toBe($expectedHash);
            },
        );

        test(
            "throws WeakPasswordException when password is shorter than minLength",
            function () {
                $plaintext = "short";
                $mockHasher = Mockery::mock(PasswordHasherInterface::class);
                $mockHasher->shouldNotReceive("hash");

                $decorator = new PasswordHasherStrengthDecorator(
                    $mockHasher,
                    minLength: 10,
                );

                expect(fn() => $decorator->hash($plaintext))->toThrow(
                    WeakPasswordException::class,
                    "Password is too short",
                );
            },
        );

        test("allows password exactly at minLength boundary", function () {
            $plaintext = "exactlyten!";
            assert(\strlen($plaintext) === 11);
            $expectedHash = new HashedPassword(
                password_hash($plaintext, PASSWORD_ARGON2ID),
            );

            $mockHasher = Mockery::mock(PasswordHasherInterface::class);
            $mockHasher
                ->shouldReceive("hash")
                ->with($plaintext)
                ->once()
                ->andReturn($expectedHash);

            $decorator = new PasswordHasherStrengthDecorator(
                $mockHasher,
                minLength: 11,
            );
            $result = $decorator->hash($plaintext);

            expect($result)->toBe($expectedHash);
        });

        test(
            "throws WeakPasswordException when password fails custom validator",
            function () {
                $plaintext = "weakpass";
                $mockHasher = Mockery::mock(PasswordHasherInterface::class);
                $mockHasher->shouldNotReceive("hash");

                // Validator that requires at least one uppercase letter
                $strengthValidator = static fn(string $password) => \preg_match(
                    "/[A-Z]/",
                    $password,
                ) === 1;

                $decorator = new PasswordHasherStrengthDecorator(
                    $mockHasher,
                    strengthValidator: $strengthValidator,
                );

                expect(fn() => $decorator->hash($plaintext))->toThrow(
                    WeakPasswordException::class,
                    "Password is too weak",
                );
            },
        );

        test(
            "delegates hash when password passes custom validator",
            function () {
                $plaintext = "StrongPass123";
                $expectedHash = new HashedPassword(
                    password_hash($plaintext, PASSWORD_ARGON2ID),
                );

                $mockHasher = Mockery::mock(PasswordHasherInterface::class);
                $mockHasher
                    ->shouldReceive("hash")
                    ->with($plaintext)
                    ->once()
                    ->andReturn($expectedHash);

                // Validator that requires at least one uppercase letter
                $strengthValidator = static fn(string $password) => \preg_match(
                    "/[A-Z]/",
                    $password,
                ) === 1;

                $decorator = new PasswordHasherStrengthDecorator(
                    $mockHasher,
                    strengthValidator: $strengthValidator,
                );
                $result = $decorator->hash($plaintext);

                expect($result)->toBe($expectedHash);
            },
        );

        test(
            "enforces minLength before checking custom validator",
            function () {
                $plaintext = "short";
                $mockHasher = Mockery::mock(PasswordHasherInterface::class);
                $mockHasher->shouldNotReceive("hash");

                $validatorCalled = false;
                $strengthValidator = static function (string $password) use (
                    &$validatorCalled,
                ) {
                    $validatorCalled = true;
                    return true;
                };

                $decorator = new PasswordHasherStrengthDecorator(
                    $mockHasher,
                    minLength: 10,
                    strengthValidator: $strengthValidator,
                );

                expect(fn() => $decorator->hash($plaintext))->toThrow(
                    WeakPasswordException::class,
                );

                // Validator should not be called since length check failed first
                expect($validatorCalled)->toBeFalse();
            },
        );

        test("works without minLength or strength validator", function () {
            $plaintext = "AnyPassword";
            $expectedHash = new HashedPassword(
                password_hash($plaintext, PASSWORD_ARGON2ID),
            );

            $mockHasher = Mockery::mock(PasswordHasherInterface::class);
            $mockHasher
                ->shouldReceive("hash")
                ->with($plaintext)
                ->once()
                ->andReturn($expectedHash);

            $decorator = new PasswordHasherStrengthDecorator($mockHasher);
            $result = $decorator->hash($plaintext);

            expect($result)->toBe($expectedHash);
        });

        test("works with only minLength configured", function () {
            $plaintext = "LongPassword";
            $expectedHash = new HashedPassword(
                password_hash($plaintext, PASSWORD_ARGON2ID),
            );

            $mockHasher = Mockery::mock(PasswordHasherInterface::class);
            $mockHasher
                ->shouldReceive("hash")
                ->with($plaintext)
                ->once()
                ->andReturn($expectedHash);

            $decorator = new PasswordHasherStrengthDecorator(
                $mockHasher,
                minLength: 8,
            );
            $result = $decorator->hash($plaintext);

            expect($result)->toBe($expectedHash);
        });

        test("works with only strengthValidator configured", function () {
            $plaintext = "Password";
            $expectedHash = new HashedPassword(
                password_hash($plaintext, PASSWORD_ARGON2ID),
            );

            $mockHasher = Mockery::mock(PasswordHasherInterface::class);
            $mockHasher
                ->shouldReceive("hash")
                ->with($plaintext)
                ->once()
                ->andReturn($expectedHash);

            $strengthValidator = static fn(string $password) => \strlen(
                $password,
            ) >= 8;

            $decorator = new PasswordHasherStrengthDecorator(
                $mockHasher,
                strengthValidator: $strengthValidator,
            );
            $result = $decorator->hash($plaintext);

            expect($result)->toBe($expectedHash);
        });

        test(
            "enforces both minLength and strengthValidator when both configured",
            function () {
                $plaintext = "Pass1";
                $mockHasher = Mockery::mock(PasswordHasherInterface::class);
                $mockHasher->shouldNotReceive("hash");

                $strengthValidator = static fn(string $password) => \preg_match(
                    "/[0-9]/",
                    $password,
                ) === 1;

                $decorator = new PasswordHasherStrengthDecorator(
                    $mockHasher,
                    minLength: 8,
                    strengthValidator: $strengthValidator,
                );

                // Should fail minLength check first
                expect(fn() => $decorator->hash($plaintext))->toThrow(
                    WeakPasswordException::class,
                    "Password is too short",
                );
            },
        );

        test(
            "allows empty password with no validation configured",
            function () {
                $plaintext = "";
                $expectedHash = new HashedPassword("dummy-hash");

                $mockHasher = Mockery::mock(PasswordHasherInterface::class);
                $mockHasher
                    ->shouldReceive("hash")
                    ->with($plaintext)
                    ->once()
                    ->andReturn($expectedHash);

                $decorator = new PasswordHasherStrengthDecorator($mockHasher);
                $result = $decorator->hash($plaintext);

                expect($result)->toBe($expectedHash);
            },
        );
    });

    describe("verify method", function () {
        test("delegates verify call to wrapped hasher", function () {
            $plaintext = "password123";
            $hash = new HashedPassword(
                password_hash($plaintext, PASSWORD_ARGON2ID),
            );

            $mockHasher = Mockery::mock(PasswordHasherInterface::class);
            $mockHasher
                ->shouldReceive("verify")
                ->with($plaintext, $hash)
                ->once()
                ->andReturn(true);

            $decorator = new PasswordHasherStrengthDecorator($mockHasher);
            $result = $decorator->verify($plaintext, $hash);

            expect($result)->toBeTrue();
        });

        test("delegates failed verification to wrapped hasher", function () {
            $plaintext = "password123";
            $wrongPlaintext = "wrongpassword";
            $hash = new HashedPassword(
                password_hash($plaintext, PASSWORD_ARGON2ID),
            );

            $mockHasher = Mockery::mock(PasswordHasherInterface::class);
            $mockHasher
                ->shouldReceive("verify")
                ->with($wrongPlaintext, $hash)
                ->once()
                ->andReturn(false);

            $decorator = new PasswordHasherStrengthDecorator($mockHasher);
            $result = $decorator->verify($wrongPlaintext, $hash);

            expect($result)->toBeFalse();
        });

        test(
            "verify does not apply strength validation (strength is only for hash)",
            function () {
                $plaintext = "x";
                $hash = new HashedPassword("any-hash");

                $mockHasher = Mockery::mock(PasswordHasherInterface::class);
                $mockHasher
                    ->shouldReceive("verify")
                    ->with($plaintext, $hash)
                    ->once()
                    ->andReturn(true);

                // Even with minLength configured, verify should not validate it
                $decorator = new PasswordHasherStrengthDecorator(
                    $mockHasher,
                    minLength: 10,
                );
                $result = $decorator->verify($plaintext, $hash);

                expect($result)->toBeTrue();
            },
        );
    });
});
