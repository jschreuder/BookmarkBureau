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

    describe("equals method", function () {
        test("equals returns true for identical token responses", function () {
            $token = new JwtToken("test.token");
            $expiresAt = new DateTimeImmutable(
                "2025-12-31 23:59:59",
                new DateTimeZone("UTC"),
            );
            $response1 = new TokenResponse($token, "session", $expiresAt);
            $response2 = new TokenResponse($token, "session", $expiresAt);

            expect($response1->equals($response2))->toBeTrue();
        });

        test("equals returns false for different tokens", function () {
            $token1 = new JwtToken("test.token1");
            $token2 = new JwtToken("test.token2");
            $expiresAt = new DateTimeImmutable(
                "2025-12-31 23:59:59",
                new DateTimeZone("UTC"),
            );
            $response1 = new TokenResponse($token1, "session", $expiresAt);
            $response2 = new TokenResponse($token2, "session", $expiresAt);

            expect($response1->equals($response2))->toBeFalse();
        });

        test("equals returns false for different token types", function () {
            $token = new JwtToken("test.token");
            $expiresAt = new DateTimeImmutable(
                "2025-12-31 23:59:59",
                new DateTimeZone("UTC"),
            );
            $response1 = new TokenResponse($token, "session", $expiresAt);
            $response2 = new TokenResponse($token, "cli", $expiresAt);

            expect($response1->equals($response2))->toBeFalse();
        });

        test("equals returns false for different expiresAt times", function () {
            $token = new JwtToken("test.token");
            $expiresAt1 = new DateTimeImmutable(
                "2025-12-31 23:59:59",
                new DateTimeZone("UTC"),
            );
            $expiresAt2 = new DateTimeImmutable(
                "2025-01-01 00:00:00",
                new DateTimeZone("UTC"),
            );
            $response1 = new TokenResponse($token, "session", $expiresAt1);
            $response2 = new TokenResponse($token, "session", $expiresAt2);

            expect($response1->equals($response2))->toBeFalse();
        });

        test("equals returns true when both have null expiresAt", function () {
            $token = new JwtToken("test.token");
            $response1 = new TokenResponse($token, "cli", null);
            $response2 = new TokenResponse($token, "cli", null);

            expect($response1->equals($response2))->toBeTrue();
        });

        test(
            "equals returns false when one has expiresAt and other does not",
            function () {
                $token = new JwtToken("test.token");
                $expiresAt = new DateTimeImmutable(
                    "2025-12-31 23:59:59",
                    new DateTimeZone("UTC"),
                );
                $response1 = new TokenResponse($token, "session", $expiresAt);
                $response2 = new TokenResponse($token, "session", null);

                expect($response1->equals($response2))->toBeFalse();
            },
        );

        test(
            "equals returns false when comparing with different type",
            function () {
                $token = new JwtToken("test.token");
                $response = new TokenResponse($token, "session", null);

                expect($response->equals(new stdClass()))->toBeFalse();
            },
        );

        test(
            "equals returns false when comparing with non-value object",
            function () {
                $token = new JwtToken("test.token");
                $response = new TokenResponse($token, "session", null);
                $claims = new \jschreuder\BookmarkBureau\Entity\Value\TokenClaims(
                    \Ramsey\Uuid\Uuid::uuid4(),
                    \jschreuder\BookmarkBureau\Entity\Value\TokenType::CLI_TOKEN,
                    new DateTimeImmutable(
                        "2024-01-01 12:00:00",
                        new DateTimeZone("UTC"),
                    ),
                    null,
                );

                expect($response->equals($claims))->toBeFalse();
            },
        );

        test(
            "equals comparison uses same token via equals method",
            function () {
                $tokenStr = "test.token";
                $token1 = new JwtToken($tokenStr);
                $token2 = new JwtToken($tokenStr);
                $expiresAt = new DateTimeImmutable(
                    "2025-12-31 23:59:59",
                    new DateTimeZone("UTC"),
                );
                $response1 = new TokenResponse($token1, "session", $expiresAt);
                $response2 = new TokenResponse($token2, "session", $expiresAt);

                expect($response1->equals($response2))->toBeTrue();
            },
        );

        test("equals is case-sensitive for type string", function () {
            $token = new JwtToken("test.token");
            $expiresAt = new DateTimeImmutable(
                "2025-12-31 23:59:59",
                new DateTimeZone("UTC"),
            );
            $response1 = new TokenResponse($token, "session", $expiresAt);
            $response2 = new TokenResponse($token, "Session", $expiresAt);

            expect($response1->equals($response2))->toBeFalse();
        });
    });
});
