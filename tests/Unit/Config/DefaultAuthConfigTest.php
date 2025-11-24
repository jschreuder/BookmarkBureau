<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Config\DefaultAuthConfig;
use jschreuder\BookmarkBureau\Exception\IncompleteConfigException;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\PasswordHasherInterface;
use jschreuder\BookmarkBureau\Service\TotpVerifierInterface;
use Lcobucci\JWT\Configuration;
use Psr\Clock\ClockInterface;

describe("DefaultAuthConfig", function () {
    describe("validation", function () {
        test("throws exception when JWT secret is empty", function () {
            expect(
                fn() => new DefaultAuthConfig(
                    jwtSecret: "",
                    applicationName: "test-app",
                    sessionTtl: 3600,
                    rememberMeTtl: 86400,
                ),
            )->toThrow(IncompleteConfigException::class);
        });

        test(
            "throws exception when JWT secret is less than 32 bytes",
            function () {
                expect(
                    fn() => new DefaultAuthConfig(
                        jwtSecret: "short",
                        applicationName: "test-app",
                        sessionTtl: 3600,
                        rememberMeTtl: 86400,
                    ),
                )->toThrow(
                    IncompleteConfigException::class,
                    "JWT secret must be at least 32 bytes",
                );
            },
        );

        test("throws exception with byte count in message", function () {
            expect(
                fn() => new DefaultAuthConfig(
                    jwtSecret: "12345",
                    applicationName: "test-app",
                    sessionTtl: 3600,
                    rememberMeTtl: 86400,
                ),
            )->toThrow(
                IncompleteConfigException::class,
                "Current length: 5 bytes",
            );
        });

        test("accepts JWT secret with exactly 32 bytes", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            expect($config->jwtSecret)->toBe($secret);
        });

        test("accepts JWT secret longer than 32 bytes", function () {
            $secret = str_repeat("a", 64);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            expect($config->jwtSecret)->toBe($secret);
        });
    });

    describe("constructor parameters", function () {
        test("stores required parameters", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "my-app",
                sessionTtl: 7200,
                rememberMeTtl: 604800,
            );

            expect($config->jwtSecret)->toBe($secret);
            expect($config->applicationName)->toBe("my-app");
            expect($config->sessionTtl)->toBe(7200);
            expect($config->rememberMeTtl)->toBe(604800);
        });

        test("stores optional password min length", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
                passwordMinLength: 16,
            );

            expect($config->passwordMinLength)->toBe(16);
        });

        test("defaults password min length to 12", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            expect($config->passwordMinLength)->toBe(12);
        });

        test("stores optional TOTP window", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
                totpWindow: 2,
            );

            expect($config->totpWindow)->toBe(2);
        });

        test("defaults TOTP window to 1", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            expect($config->totpWindow)->toBe(1);
        });
    });

    describe("JWT configuration", function () {
        test("provides JWT configuration", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            $jwtConfig = $config->getJwtConfiguration();
            expect($jwtConfig)->toBeInstanceOf(Configuration::class);
        });

        test("JWT configuration can be used to create tokens", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            $jwtConfig = $config->getJwtConfiguration();
            $token = $jwtConfig
                ->builder()
                ->withClaim("test", "value")
                ->getToken($jwtConfig->signer(), $jwtConfig->signingKey());

            expect($token->toString())->not->toBeEmpty();
        });
    });

    describe("password hasher", function () {
        test("provides password hasher", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            $hasher = $config->getPasswordHasher();
            expect($hasher)->toBeInstanceOf(PasswordHasherInterface::class);
        });

        test("password hasher uses configured min length", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
                passwordMinLength: 16,
            );

            $hasher = $config->getPasswordHasher();
            // Hasher should reject passwords shorter than 16 characters
            expect(fn() => $hasher->hash("short"))->toThrow(\Exception::class);
        });

        test("password hasher can hash valid passwords", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
                passwordMinLength: 8,
            );

            $hasher = $config->getPasswordHasher();
            $hashed = $hasher->hash("valid-password-here");

            expect($hashed)->not->toBeEmpty();
            expect($hashed)->not->toBe("valid-password-here");
        });
    });

    describe("TOTP verifier", function () {
        test("provides TOTP verifier", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            $verifier = $config->getTotpVerifier();
            expect($verifier)->toBeInstanceOf(TotpVerifierInterface::class);
        });
    });

    describe("clock", function () {
        test("provides clock instance", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            $clock = $config->getClock();
            expect($clock)->toBeInstanceOf(ClockInterface::class);
        });

        test("clock can return current time", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            $clock = $config->getClock();
            $now = $clock->now();

            expect($now)->toBeInstanceOf(DateTimeImmutable::class);
        });
    });

    describe("JWT service creation", function () {
        test("creates JWT service with repository", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            $repository = TestContainerHelper::createContainerInstance()->getJwtJtiRepository();

            $jwtService = $config->createJwtService($repository);
            expect($jwtService)->toBeInstanceOf(JwtServiceInterface::class);
        });
    });

    describe("readonly property", function () {
        test("config is readonly", function () {
            $secret = str_repeat("a", 32);
            $config = new DefaultAuthConfig(
                jwtSecret: $secret,
                applicationName: "test-app",
                sessionTtl: 3600,
                rememberMeTtl: 86400,
            );

            // Attempting to modify readonly properties should fail
            expect(fn() => ($config->jwtSecret = "new-value"))->toThrow(
                Error::class,
            );
        });
    });
});
