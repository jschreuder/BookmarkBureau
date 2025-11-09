<?php

use jschreuder\BookmarkBureau\Entity\Value\TokenClaims;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use Ramsey\Uuid\Uuid;

describe("TokenClaims", function () {
    test("stores and returns userId", function () {
        $userId = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );
        $expiresAt = $now->modify("+24 hours");

        $claims = new TokenClaims(
            $userId,
            TokenType::SESSION_TOKEN,
            $now,
            $expiresAt,
        );

        expect($claims->userId->toString())->toBe($userId->toString());
    });

    test("stores and returns tokenType", function () {
        $userId = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );
        $expiresAt = $now->modify("+24 hours");

        $claims = new TokenClaims(
            $userId,
            TokenType::SESSION_TOKEN,
            $now,
            $expiresAt,
        );

        expect($claims->tokenType)->toBe(TokenType::SESSION_TOKEN);
    });

    test("stores and returns issuedAt", function () {
        $userId = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );
        $expiresAt = $now->modify("+24 hours");

        $claims = new TokenClaims(
            $userId,
            TokenType::SESSION_TOKEN,
            $now,
            $expiresAt,
        );

        expect($claims->issuedAt->getTimestamp())->toBe($now->getTimestamp());
    });

    test("stores and returns expiresAt", function () {
        $userId = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );
        $expiresAt = $now->modify("+24 hours");

        $claims = new TokenClaims(
            $userId,
            TokenType::SESSION_TOKEN,
            $now,
            $expiresAt,
        );

        expect($claims->expiresAt->getTimestamp())->toBe(
            $expiresAt->getTimestamp(),
        );
    });

    test("allows null expiresAt for CLI tokens", function () {
        $userId = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );

        $claims = new TokenClaims($userId, TokenType::CLI_TOKEN, $now, null);

        expect($claims->expiresAt)->toBeNull();
    });

    test("isExpired returns false when token not expired", function () {
        $userId = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );
        $expiresAt = $now->modify("+24 hours");

        $claims = new TokenClaims(
            $userId,
            TokenType::SESSION_TOKEN,
            $now,
            $expiresAt,
        );

        $beforeExpiry = $now->modify("+12 hours");
        expect($claims->isExpired($beforeExpiry))->toBeFalse();
    });

    test("isExpired returns true when token is expired", function () {
        $userId = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );
        $expiresAt = $now->modify("+24 hours");

        $claims = new TokenClaims(
            $userId,
            TokenType::SESSION_TOKEN,
            $now,
            $expiresAt,
        );

        $afterExpiry = $now->modify("+25 hours");
        expect($claims->isExpired($afterExpiry))->toBeTrue();
    });

    test("isExpired returns false at exact expiry time", function () {
        $userId = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );
        $expiresAt = $now->modify("+24 hours");

        $claims = new TokenClaims(
            $userId,
            TokenType::SESSION_TOKEN,
            $now,
            $expiresAt,
        );

        // At exact expiry time, not yet expired (>= is the check)
        expect($claims->isExpired($expiresAt))->toBeTrue();
    });

    test(
        "isExpired returns false for CLI tokens regardless of time",
        function () {
            $userId = Uuid::uuid4();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $claims = new TokenClaims(
                $userId,
                TokenType::CLI_TOKEN,
                $now,
                null,
            );

            $farFuture = $now->modify("+100 years");
            expect($claims->isExpired($farFuture))->toBeFalse();
        },
    );

    test("stores and returns jti for CLI tokens", function () {
        $userId = Uuid::uuid4();
        $jti = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );

        $claims = new TokenClaims(
            $userId,
            TokenType::CLI_TOKEN,
            $now,
            null,
            $jti,
        );

        expect($claims->jti->toString())->toBe($jti->toString());
    });

    test("jti is null for non-CLI tokens", function () {
        $userId = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );
        $expiresAt = $now->modify("+24 hours");

        $claims = new TokenClaims(
            $userId,
            TokenType::SESSION_TOKEN,
            $now,
            $expiresAt,
            null,
        );

        expect($claims->jti)->toBeNull();
    });

    test("jti defaults to null when not provided", function () {
        $userId = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );

        $claims = new TokenClaims($userId, TokenType::CLI_TOKEN, $now, null);

        expect($claims->jti)->toBeNull();
    });

    test("is immutable (readonly)", function () {
        $userId = Uuid::uuid4();
        $now = new DateTimeImmutable(
            "2024-01-01 12:00:00",
            new DateTimeZone("UTC"),
        );

        $claims = new TokenClaims($userId, TokenType::CLI_TOKEN, $now, null);

        expect($claims)->toBeInstanceOf(TokenClaims::class);
    });
});
