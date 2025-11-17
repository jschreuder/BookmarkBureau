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

    describe("equals method", function () {
        test("equals returns true for identical claims", function () {
            $userId = Uuid::uuid4();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $expiresAt = $now->modify("+24 hours");

            $claims1 = new TokenClaims(
                $userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );
            $claims2 = new TokenClaims(
                $userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );

            expect($claims1->equals($claims2))->toBeTrue();
        });

        test("equals returns false for different user IDs", function () {
            $userId1 = Uuid::uuid4();
            $userId2 = Uuid::uuid4();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $expiresAt = $now->modify("+24 hours");

            $claims1 = new TokenClaims(
                $userId1,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );
            $claims2 = new TokenClaims(
                $userId2,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );

            expect($claims1->equals($claims2))->toBeFalse();
        });

        test("equals returns false for different token types", function () {
            $userId = Uuid::uuid4();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $expiresAt = $now->modify("+24 hours");

            $claims1 = new TokenClaims(
                $userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );
            $claims2 = new TokenClaims(
                $userId,
                TokenType::REMEMBER_ME_TOKEN,
                $now,
                $expiresAt,
            );

            expect($claims1->equals($claims2))->toBeFalse();
        });

        test("equals returns false for different issuedAt times", function () {
            $userId = Uuid::uuid4();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $now2 = new DateTimeImmutable(
                "2024-01-01 13:00:00",
                new DateTimeZone("UTC"),
            );
            $expiresAt = $now->modify("+24 hours");

            $claims1 = new TokenClaims(
                $userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );
            $claims2 = new TokenClaims(
                $userId,
                TokenType::SESSION_TOKEN,
                $now2,
                $expiresAt,
            );

            expect($claims1->equals($claims2))->toBeFalse();
        });

        test("equals returns false for different expiresAt times", function () {
            $userId = Uuid::uuid4();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $expiresAt1 = $now->modify("+24 hours");
            $expiresAt2 = $now->modify("+48 hours");

            $claims1 = new TokenClaims(
                $userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt1,
            );
            $claims2 = new TokenClaims(
                $userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt2,
            );

            expect($claims1->equals($claims2))->toBeFalse();
        });

        test(
            "equals returns true when one has null expiresAt and other has null expiresAt",
            function () {
                $userId = Uuid::uuid4();
                $now = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                    new DateTimeZone("UTC"),
                );

                $claims1 = new TokenClaims(
                    $userId,
                    TokenType::CLI_TOKEN,
                    $now,
                    null,
                );
                $claims2 = new TokenClaims(
                    $userId,
                    TokenType::CLI_TOKEN,
                    $now,
                    null,
                );

                expect($claims1->equals($claims2))->toBeTrue();
            },
        );

        test(
            "equals returns false when one has expiresAt and other does not",
            function () {
                $userId = Uuid::uuid4();
                $now = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                    new DateTimeZone("UTC"),
                );
                $expiresAt = $now->modify("+24 hours");

                $claims1 = new TokenClaims(
                    $userId,
                    TokenType::CLI_TOKEN,
                    $now,
                    null,
                );
                $claims2 = new TokenClaims(
                    $userId,
                    TokenType::SESSION_TOKEN,
                    $now,
                    $expiresAt,
                );

                expect($claims1->equals($claims2))->toBeFalse();
            },
        );

        test("equals returns false for different jti values", function () {
            $userId = Uuid::uuid4();
            $jti1 = Uuid::uuid4();
            $jti2 = Uuid::uuid4();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $claims1 = new TokenClaims(
                $userId,
                TokenType::CLI_TOKEN,
                $now,
                null,
                $jti1,
            );
            $claims2 = new TokenClaims(
                $userId,
                TokenType::CLI_TOKEN,
                $now,
                null,
                $jti2,
            );

            expect($claims1->equals($claims2))->toBeFalse();
        });

        test("equals returns true when both have same jti", function () {
            $userId = Uuid::uuid4();
            $jti = Uuid::uuid4();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $claims1 = new TokenClaims(
                $userId,
                TokenType::CLI_TOKEN,
                $now,
                null,
                $jti,
            );
            $claims2 = new TokenClaims(
                $userId,
                TokenType::CLI_TOKEN,
                $now,
                null,
                $jti,
            );

            expect($claims1->equals($claims2))->toBeTrue();
        });

        test("equals returns true when both have null jti", function () {
            $userId = Uuid::uuid4();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );

            $claims1 = new TokenClaims(
                $userId,
                TokenType::SESSION_TOKEN,
                $now,
                null,
                null,
            );
            $claims2 = new TokenClaims(
                $userId,
                TokenType::SESSION_TOKEN,
                $now,
                null,
                null,
            );

            expect($claims1->equals($claims2))->toBeTrue();
        });

        test(
            "equals returns false when comparing with different type",
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

                expect($claims->equals(new stdClass()))->toBeFalse();
            },
        );

        test(
            "equals returns false when comparing with non-value object",
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
                $token = new \jschreuder\BookmarkBureau\Entity\Value\JwtToken(
                    "test.token",
                );

                expect($claims->equals($token))->toBeFalse();
            },
        );
    });
});
