<?php

use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Service\LcobucciJwtService;
use jschreuder\BookmarkBureau\Exception\InvalidTokenException;
use jschreuder\BookmarkBureau\Repository\JwtJtiRepositoryInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;

function createJwtConfigForJti(
    string $secret = "test-secret-key-32-bytes-long!!!",
): Configuration {
    return Configuration::forSymmetricSigner(
        new Sha256(),
        InMemory::plainText($secret),
    )->withValidationConstraints();
}

function createJwtServiceWithJti(
    JwtJtiRepositoryInterface $jtiRepository,
    ?Configuration $config = null,
    string $applicationName = "bookmark-bureau",
    int $sessionTtl = 86400,
    int $rememberMeTtl = 1209600,
    ?ClockInterface $clock = null,
): LcobucciJwtService {
    $config ??= createJwtConfigForJti();
    $clock ??= Mockery::mock(ClockInterface::class);

    return new LcobucciJwtService(
        $config,
        $applicationName,
        $sessionTtl,
        $rememberMeTtl,
        $clock,
        $jtiRepository,
    );
}

describe("LcobucciJwtService with JTI", function () {
    describe("generate method for CLI tokens", function () {
        test("generates CLI token with JTI claim", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jtiRepository->shouldReceive("saveJti")->once();

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = createJwtServiceWithJti($jtiRepository, clock: $clock);
            $token = $service->generate($user, TokenType::CLI_TOKEN);

            expect($token)->toBeInstanceOf(
                \jschreuder\BookmarkBureau\Entity\Value\JwtToken::class,
            );
            expect((string) $token)->toBeString();
        });

        test("saves JTI to repository when generating CLI token", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jtiRepository
                ->shouldReceive("saveJti")
                ->withArgs(function ($jti, $userId, $createdAt) use (
                    $user,
                    $now,
                ) {
                    return $userId->toString() === $user->userId->toString() &&
                        $createdAt->getTimestamp() === $now->getTimestamp();
                })
                ->once();

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = createJwtServiceWithJti($jtiRepository, clock: $clock);
            $token = $service->generate($user, TokenType::CLI_TOKEN);

            expect($token)->toBeInstanceOf(
                \jschreuder\BookmarkBureau\Entity\Value\JwtToken::class,
            );
        });

        test("does not save JTI for non-CLI tokens", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jtiRepository->shouldReceive("saveJti")->never();

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = createJwtServiceWithJti($jtiRepository, clock: $clock);
            $token = $service->generate($user, TokenType::SESSION_TOKEN);

            expect($token)->toBeInstanceOf(
                \jschreuder\BookmarkBureau\Entity\Value\JwtToken::class,
            );
        });
    });

    describe("verify method for CLI tokens", function () {
        test("verifies CLI token with valid JTI in whitelist", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jtiRepository->shouldReceive("saveJti")->once();
            $jtiRepository->shouldReceive("hasJti")->andReturn(true);

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = createJwtServiceWithJti($jtiRepository, clock: $clock);
            $token = $service->generate($user, TokenType::CLI_TOKEN);

            $clock->shouldReceive("now")->andReturn($now);
            $claims = $service->verify($token);

            expect($claims->tokenType)->toBe(TokenType::CLI_TOKEN);
            expect($claims->jti)->not()->toBeNull();
        });

        test(
            "fails to verify CLI token with JTI not in whitelist",
            function () {
                $user = TestEntityFactory::createUser();
                $now = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                    new DateTimeZone("UTC"),
                );

                $jtiRepository = Mockery::mock(
                    JwtJtiRepositoryInterface::class,
                );
                $jtiRepository->shouldReceive("saveJti")->once();
                $jtiRepository->shouldReceive("hasJti")->andReturn(false);

                $clock = Mockery::mock(ClockInterface::class);
                $clock->shouldReceive("now")->andReturn($now);

                $service = createJwtServiceWithJti(
                    $jtiRepository,
                    clock: $clock,
                );
                $token = $service->generate($user, TokenType::CLI_TOKEN);

                $clock->shouldReceive("now")->andReturn($now);

                expect(fn() => $service->verify($token))->toThrow(
                    InvalidTokenException::class,
                    "CLI token JTI not in whitelist (revoked or invalid)",
                );
            },
        );

        test("fails to verify CLI token without JTI claim", function () {
            $config = createJwtConfigForJti();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = createJwtServiceWithJti(
                $jtiRepository,
                $config,
                clock: $clock,
            );

            // Manually create a token without JTI for CLI type
            $user = TestEntityFactory::createUser();
            $builder = $config
                ->builder()
                ->issuedBy("bookmark-bureau")
                ->permittedFor("bookmark-bureau-api")
                ->relatedTo($user->userId->toString())
                ->withClaim("type", "cli")
                ->issuedAt($now)
                ->canOnlyBeUsedAfter($now);

            $tokenObj = $builder->getToken(
                $config->signer(),
                $config->signingKey(),
            );
            $token = new \jschreuder\BookmarkBureau\Entity\Value\JwtToken(
                $tokenObj->toString(),
            );

            $clock->shouldReceive("now")->andReturn($now);

            expect(fn() => $service->verify($token))->toThrow(
                InvalidTokenException::class,
                "CLI token missing required JTI claim",
            );
        });

        test(
            "returns TokenClaims with JTI when verifying valid CLI token",
            function () {
                $user = TestEntityFactory::createUser();
                $now = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                    new DateTimeZone("UTC"),
                );

                $jtiRepository = Mockery::mock(
                    JwtJtiRepositoryInterface::class,
                );
                $jtiRepository->shouldReceive("saveJti")->once();
                $jtiRepository->shouldReceive("hasJti")->andReturn(true);

                $clock = Mockery::mock(ClockInterface::class);
                $clock->shouldReceive("now")->andReturn($now);

                $service = createJwtServiceWithJti(
                    $jtiRepository,
                    clock: $clock,
                );
                $token = $service->generate($user, TokenType::CLI_TOKEN);

                $clock->shouldReceive("now")->andReturn($now);
                $claims = $service->verify($token);

                expect($claims->jti)->not()->toBeNull();
                expect($claims->jti)->toBeInstanceOf(
                    \Ramsey\Uuid\UuidInterface::class,
                );
            },
        );
    });

    describe("refresh method for CLI tokens", function () {
        test("preserves JTI when refreshing CLI token", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jtiRepository->shouldReceive("saveJti")->once();
            $jtiRepository->shouldReceive("hasJti")->andReturn(true);

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = createJwtServiceWithJti($jtiRepository, clock: $clock);
            $token = $service->generate($user, TokenType::CLI_TOKEN);

            $clock->shouldReceive("now")->andReturn($now);
            $claims = $service->verify($token);
            $originalJti = $claims->jti;

            $clock->shouldReceive("now")->andReturn($now);
            $jtiRepository->shouldReceive("hasJti")->andReturn(true);
            $refreshedToken = $service->refresh($claims);

            $clock->shouldReceive("now")->andReturn($now);
            $jtiRepository->shouldReceive("hasJti")->andReturn(true);
            $refreshedClaims = $service->verify($refreshedToken);

            expect($refreshedClaims->jti->toString())->toBe(
                $originalJti->toString(),
            );
        });

        test("fails to refresh CLI token without JTI", function () {
            $claims = new \jschreuder\BookmarkBureau\Entity\Value\TokenClaims(
                Uuid::uuid4(),
                TokenType::CLI_TOKEN,
                new DateTimeImmutable(),
                null,
                null, // No JTI
            );

            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn(new DateTimeImmutable());

            $service = createJwtServiceWithJti($jtiRepository, clock: $clock);

            expect(fn() => $service->refresh($claims))->toThrow(
                InvalidTokenException::class,
                "CLI token missing JTI for refresh",
            );
        });
    });

    describe("non-CLI tokens with JTI", function () {
        test("SESSION_TOKEN does not have JTI", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jtiRepository->shouldReceive("saveJti")->never();
            $jtiRepository->shouldReceive("hasJti")->never();

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = createJwtServiceWithJti($jtiRepository, clock: $clock);
            $token = $service->generate($user, TokenType::SESSION_TOKEN);

            $clock->shouldReceive("now")->andReturn($now);
            $claims = $service->verify($token);

            expect($claims->jti)->toBeNull();
        });

        test("REMEMBER_ME_TOKEN does not have JTI", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jtiRepository->shouldReceive("saveJti")->never();
            $jtiRepository->shouldReceive("hasJti")->never();

            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn($now);

            $service = createJwtServiceWithJti($jtiRepository, clock: $clock);
            $token = $service->generate($user, TokenType::REMEMBER_ME_TOKEN);

            $clock->shouldReceive("now")->andReturn($now);
            $claims = $service->verify($token);

            expect($claims->jti)->toBeNull();
        });
    });
});
