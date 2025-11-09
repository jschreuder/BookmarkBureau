<?php

use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Service\JwtService;
use jschreuder\BookmarkBureau\Exception\InvalidTokenException;
use Psr\Clock\ClockInterface;

describe("JwtService", function () {
    describe("generate method", function () {
        test("generates a SESSION_TOKEN with 24-hour expiry", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $sessionTtl = 86400;
            $rememberMeTtl = 1209600;

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = new JwtService(
                "secret-key",
                $sessionTtl,
                $rememberMeTtl,
                $clock,
            );
            $token = $service->generate($user, TokenType::SESSION_TOKEN);

            expect($token)->toBeInstanceOf(
                \jschreuder\BookmarkBureau\Entity\Value\JwtToken::class,
            );
            expect((string) $token)->toBeString();
            expect((string) $token)->not()->toBeEmpty();
        });

        test("generates a REMEMBER_ME_TOKEN with 2-week expiry", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $sessionTtl = 86400;
            $rememberMeTtl = 1209600;

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = new JwtService(
                "secret-key",
                $sessionTtl,
                $rememberMeTtl,
                $clock,
            );
            $token = $service->generate($user, TokenType::REMEMBER_ME_TOKEN);

            expect($token)->toBeInstanceOf(
                \jschreuder\BookmarkBureau\Entity\Value\JwtToken::class,
            );
            expect((string) $token)->toBeString();
        });

        test("generates a CLI_TOKEN without expiry", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $sessionTtl = 86400;
            $rememberMeTtl = 1209600;

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = new JwtService(
                "secret-key",
                $sessionTtl,
                $rememberMeTtl,
                $clock,
            );
            $token = $service->generate($user, TokenType::CLI_TOKEN);

            expect($token)->toBeInstanceOf(
                \jschreuder\BookmarkBureau\Entity\Value\JwtToken::class,
            );
            expect((string) $token)->toBeString();
        });
    });

    describe("verify method", function () {
        test("verifies a valid SESSION_TOKEN and returns claims", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $sessionTtl = 86400;
            $rememberMeTtl = 1209600;

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = new JwtService(
                "secret-key",
                $sessionTtl,
                $rememberMeTtl,
                $clock,
            );
            $token = $service->generate($user, TokenType::SESSION_TOKEN);

            $clock->shouldReceive("now")->andReturn($now);
            $claims = $service->verify($token);

            expect($claims)->toBeInstanceOf(
                \jschreuder\BookmarkBureau\Entity\Value\TokenClaims::class,
            );
            expect($claims->getUserId()->toString())->toBe(
                $user->userId->toString(),
            );
            expect($claims->getTokenType())->toBe(TokenType::SESSION_TOKEN);
            expect($claims->getIssuedAt()->getTimestamp())->toBe(
                $now->getTimestamp(),
            );
            expect($claims->getExpiresAt())->not()->toBeNull();
        });

        test("verifies a valid CLI_TOKEN without expiry", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $sessionTtl = 86400;
            $rememberMeTtl = 1209600;

            $clock = Mockery::mock(ClockInterface::class);
            $clock
                ->shouldReceive("now")
                ->andReturnValues([$now, $now->modify("+365 days")]);

            $service = new JwtService(
                "secret-key",
                $sessionTtl,
                $rememberMeTtl,
                $clock,
            );
            $token = $service->generate($user, TokenType::CLI_TOKEN);

            $claims = $service->verify($token);

            expect($claims)->toBeInstanceOf(
                \jschreuder\BookmarkBureau\Entity\Value\TokenClaims::class,
            );
            expect($claims->getUserId()->toString())->toBe(
                $user->userId->toString(),
            );
            expect($claims->getTokenType())->toBe(TokenType::CLI_TOKEN);
            expect($claims->getExpiresAt())->toBeNull();
        });

        test(
            "throws InvalidTokenException when SESSION_TOKEN is expired",
            function () {
                $user = TestEntityFactory::createUser();
                $now = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                    new DateTimeZone("UTC"),
                );
                $laterTime = $now->modify("+86401 seconds");
                $sessionTtl = 86400;
                $rememberMeTtl = 1209600;

                $clock = Mockery::mock(ClockInterface::class);
                $clock
                    ->shouldReceive("now")
                    ->andReturnValues([$now, $laterTime]);

                $service = new JwtService(
                    "secret-key",
                    $sessionTtl,
                    $rememberMeTtl,
                    $clock,
                );
                $token = $service->generate($user, TokenType::SESSION_TOKEN);

                expect(fn() => $service->verify($token))->toThrow(
                    InvalidTokenException::class,
                );
            },
        );

        test(
            "throws InvalidTokenException with invalid JWT signature",
            function () {
                $now = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                    new DateTimeZone("UTC"),
                );
                $sessionTtl = 86400;
                $rememberMeTtl = 1209600;

                $clock = Mockery::mock(ClockInterface::class);
                $clock->shouldReceive("now")->andReturn($now);

                $service = new JwtService(
                    "secret-key",
                    $sessionTtl,
                    $rememberMeTtl,
                    $clock,
                );

                $fakeToken = new \jschreuder\BookmarkBureau\Entity\Value\JwtToken(
                    "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.wrongsignature",
                );

                expect(fn() => $service->verify($fakeToken))->toThrow(
                    InvalidTokenException::class,
                );
            },
        );

        test("throws InvalidTokenException with malformed JWT", function () {
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $sessionTtl = 86400;
            $rememberMeTtl = 1209600;

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = new JwtService(
                "secret-key",
                $sessionTtl,
                $rememberMeTtl,
                $clock,
            );
            $fakeToken = new \jschreuder\BookmarkBureau\Entity\Value\JwtToken(
                "not.a.jwt",
            );

            expect(fn() => $service->verify($fakeToken))->toThrow(
                InvalidTokenException::class,
            );
        });
    });

    describe("refresh method", function () {
        test(
            "generates a new token with fresh expiry for SESSION_TOKEN",
            function () {
                $user = TestEntityFactory::createUser();
                $now = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                    new DateTimeZone("UTC"),
                );
                $laterTime = $now->modify("+3600 seconds");
                $sessionTtl = 86400;
                $rememberMeTtl = 1209600;

                $clock = Mockery::mock(ClockInterface::class);
                $clock
                    ->shouldReceive("now")
                    ->andReturnValues([$now, $now, $laterTime, $laterTime]);

                $service = new JwtService(
                    "secret-key",
                    $sessionTtl,
                    $rememberMeTtl,
                    $clock,
                );

                $originalToken = $service->generate(
                    $user,
                    TokenType::SESSION_TOKEN,
                );
                $claims = $service->verify($originalToken);
                $refreshedToken = $service->refresh($claims);
                $refreshedClaims = $service->verify($refreshedToken);

                expect($refreshedToken)->toBeInstanceOf(
                    \jschreuder\BookmarkBureau\Entity\Value\JwtToken::class,
                );
                expect((string) $refreshedToken)
                    ->not()
                    ->toBe((string) $originalToken);
                expect($refreshedClaims->getIssuedAt()->getTimestamp())->toBe(
                    $laterTime->getTimestamp(),
                );
            },
        );

        test(
            "generates a new token with fresh expiry for REMEMBER_ME_TOKEN",
            function () {
                $user = TestEntityFactory::createUser();
                $now = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                    new DateTimeZone("UTC"),
                );
                $laterTime = $now->modify("+604800 seconds");
                $sessionTtl = 86400;
                $rememberMeTtl = 1209600;

                $clock = Mockery::mock(ClockInterface::class);
                $clock
                    ->shouldReceive("now")
                    ->andReturnValues([$now, $now, $laterTime]);

                $service = new JwtService(
                    "secret-key",
                    $sessionTtl,
                    $rememberMeTtl,
                    $clock,
                );

                $originalToken = $service->generate(
                    $user,
                    TokenType::REMEMBER_ME_TOKEN,
                );
                $claims = $service->verify($originalToken);
                $refreshedToken = $service->refresh($claims);

                expect((string) $refreshedToken)
                    ->not()
                    ->toBe((string) $originalToken);
            },
        );

        test("preserves token type when refreshing", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $laterTime = $now->modify("+3600 seconds");
            $sessionTtl = 86400;
            $rememberMeTtl = 1209600;

            $clock = Mockery::mock(ClockInterface::class);
            $clock
                ->shouldReceive("now")
                ->andReturnValues([$now, $now, $laterTime, $laterTime]);

            $service = new JwtService(
                "secret-key",
                $sessionTtl,
                $rememberMeTtl,
                $clock,
            );

            $originalToken = $service->generate(
                $user,
                TokenType::SESSION_TOKEN,
            );
            $claims = $service->verify($originalToken);
            $refreshedToken = $service->refresh($claims);
            $refreshedClaims = $service->verify($refreshedToken);

            expect($refreshedClaims->getTokenType())->toBe(
                TokenType::SESSION_TOKEN,
            );
            expect($refreshedClaims->getUserId()->toString())->toBe(
                $user->userId->toString(),
            );
        });
    });

    describe("TokenClaims value object", function () {
        test("isExpired returns false for CLI_TOKEN", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $sessionTtl = 86400;
            $rememberMeTtl = 1209600;

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturnValues([$now, $now]);

            $service = new JwtService(
                "secret-key",
                $sessionTtl,
                $rememberMeTtl,
                $clock,
            );
            $token = $service->generate($user, TokenType::CLI_TOKEN);
            $claims = $service->verify($token);

            $farFuture = $now->modify("+10 years");
            expect($claims->isExpired($farFuture))->toBeFalse();
        });

        test("isExpired returns false for valid SESSION_TOKEN", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $sessionTtl = 86400;
            $rememberMeTtl = 1209600;

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturnValues([$now, $now]);

            $service = new JwtService(
                "secret-key",
                $sessionTtl,
                $rememberMeTtl,
                $clock,
            );
            $token = $service->generate($user, TokenType::SESSION_TOKEN);
            $claims = $service->verify($token);

            $almostExpired = $now->modify("+86399 seconds");
            expect($claims->isExpired($almostExpired))->toBeFalse();
        });

        test("isExpired returns true for expired SESSION_TOKEN", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $sessionTtl = 86400;
            $rememberMeTtl = 1209600;

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturnValues([$now, $now]);

            $service = new JwtService(
                "secret-key",
                $sessionTtl,
                $rememberMeTtl,
                $clock,
            );
            $token = $service->generate($user, TokenType::SESSION_TOKEN);
            $claims = $service->verify($token);

            $afterExpiry = $now->modify("+86401 seconds");
            expect($claims->isExpired($afterExpiry))->toBeTrue();
        });
    });
});
