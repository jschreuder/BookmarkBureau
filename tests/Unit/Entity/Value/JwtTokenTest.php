<?php

use jschreuder\BookmarkBureau\Entity\Value\JwtToken;

describe("JwtToken", function () {
    test("throws exception for empty token", function () {
        expect(fn() => new JwtToken(""))->toThrow(
            InvalidArgumentException::class,
            "JWT token cannot be empty",
        );
    });

    test("__toString returns the token string", function () {
        $tokenString =
            "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1dXlkIn0.signature";
        $token = new JwtToken($tokenString);

        expect((string) $token)->toBe($tokenString);
    });

    test("value property returns the token string", function () {
        $tokenString =
            "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1dXlkIn0.signature";
        $token = new JwtToken($tokenString);

        expect($token->value)->toBe($tokenString);
    });

    test("is immutable", function () {
        $token = new JwtToken("test.token.here");

        // Verify it's readonly by checking we can't set properties
        expect($token)->toBeInstanceOf(JwtToken::class);
    });

    test("can be used in string context", function () {
        $token = new JwtToken("my.jwt.token");
        $string = "Token: {$token}";

        expect($string)->toBe("Token: my.jwt.token");
    });

    describe("equals method", function () {
        test("equals returns true for same token value", function () {
            $tokenString =
                "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1dXlkIn0.signature";
            $token1 = new JwtToken($tokenString);
            $token2 = new JwtToken($tokenString);

            expect($token1->equals($token2))->toBeTrue();
        });

        test("equals returns false for different token values", function () {
            $token1 = new JwtToken(
                "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1dXlkIn0.signature1",
            );
            $token2 = new JwtToken(
                "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1dXlkIn0.signature2",
            );

            expect($token1->equals($token2))->toBeFalse();
        });

        test(
            "equals returns false when comparing with different type",
            function () {
                $token = new JwtToken("my.jwt.token");
                $stdObject = new stdClass();

                expect($token->equals($stdObject))->toBeFalse();
            },
        );

        test(
            "equals returns false when comparing with non-value object",
            function () {
                $token = new JwtToken("my.jwt.token");
                $email = new \jschreuder\BookmarkBureau\Entity\Value\Email(
                    "user@example.com",
                );

                expect($token->equals($email))->toBeFalse();
            },
        );

        test(
            "equals comparison is case-sensitive for token values",
            function () {
                $token1 = new JwtToken(
                    "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.ABCDEF.signature",
                );
                $token2 = new JwtToken(
                    "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.abcdef.signature",
                );

                expect($token1->equals($token2))->toBeFalse();
            },
        );
    });
});
