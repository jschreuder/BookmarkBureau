<?php

use jschreuder\BookmarkBureau\Entity\Value\JwtToken;
use jschreuder\BookmarkBureau\Entity\Value\TokenResponse;

describe("TokenResponse", function () {
    test("stores and returns token", function () {
        $tokenStr = "test.token.here";
        $token = new JwtToken($tokenStr);
        $response = new TokenResponse($token, "session", null);

        expect($response->token)->toBe($token);
        expect((string) $response->token)->toBe($tokenStr);
    });

    test("stores and returns token type as string", function () {
        $token = new JwtToken("test.token");
        $response = new TokenResponse($token, "session", null);

        expect($response->type)->toBe("session");
    });

    test("stores and returns expiresAt as DateTimeInterface", function () {
        $token = new JwtToken("test.token");
        $expiresAt = new DateTimeImmutable(
            "2025-12-31 23:59:59",
            new DateTimeZone("UTC"),
        );
        $response = new TokenResponse($token, "session", $expiresAt);

        expect($response->expiresAt)->toBe($expiresAt);
    });

    test("allows null expiresAt for CLI tokens", function () {
        $token = new JwtToken("test.token");
        $response = new TokenResponse($token, "cli", null);

        expect($response->expiresAt)->toBeNull();
    });

    test("is immutable (readonly)", function () {
        $token = new JwtToken("test.token");
        $response = new TokenResponse($token, "session", null);

        expect($response)->toBeInstanceOf(TokenResponse::class);
    });
});
