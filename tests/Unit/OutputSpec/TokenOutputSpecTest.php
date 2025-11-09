<?php

use jschreuder\BookmarkBureau\Entity\Value\JwtToken;
use jschreuder\BookmarkBureau\Entity\Value\TokenResponse;
use jschreuder\BookmarkBureau\OutputSpec\TokenOutputSpec;

describe("TokenOutputSpec", function () {
    describe("supports", function () {
        test("supports TokenResponse objects", function () {
            $spec = new TokenOutputSpec();
            $token = new JwtToken("test.token");
            $response = new TokenResponse($token, "session", null);

            expect($spec->supports($response))->toBeTrue();
        });

        test("does not support other objects", function () {
            $spec = new TokenOutputSpec();

            expect($spec->supports(new stdClass()))->toBeFalse();
            expect($spec->supports(new JwtToken("test.token")))->toBeFalse();
        });
    });

    describe("transform", function () {
        test(
            "transforms TokenResponse to array with token, type, expiresAt",
            function () {
                $spec = new TokenOutputSpec();
                $token = new JwtToken("my.jwt.token");
                $expiresAt = new DateTimeImmutable(
                    "2025-12-31 23:59:59",
                    new DateTimeZone("UTC"),
                );
                $response = new TokenResponse($token, "session", $expiresAt);

                $transformed = $spec->transform($response);

                expect($transformed)->toHaveKey("token");
                expect($transformed)->toHaveKey("type");
                expect($transformed)->toHaveKey("expires_at");
                expect($transformed["token"])->toBe("my.jwt.token");
                expect($transformed["type"])->toBe("session");
            },
        );

        test("formats expiresAt to ISO8601 date string", function () {
            $spec = new TokenOutputSpec();
            $token = new JwtToken("test.token");
            $expiresAt = new DateTimeImmutable(
                "2025-12-31 23:59:59",
                new DateTimeZone("UTC"),
            );
            $response = new TokenResponse($token, "session", $expiresAt);

            $transformed = $spec->transform($response);

            expect($transformed["expires_at"])->toBe(
                "2025-12-31T23:59:59+00:00",
            );
        });

        test("handles null expiresAt for CLI tokens", function () {
            $spec = new TokenOutputSpec();
            $token = new JwtToken("test.token");
            $response = new TokenResponse($token, "cli", null);

            $transformed = $spec->transform($response);

            expect($transformed["expires_at"])->toBeNull();
        });

        test(
            "throws InvalidArgumentException for unsupported types",
            function () {
                $spec = new TokenOutputSpec();

                expect(fn() => $spec->transform(new stdClass()))->toThrow(
                    \InvalidArgumentException::class,
                );
            },
        );

        test("includes all token types", function () {
            $spec = new TokenOutputSpec();
            $token = new JwtToken("test.token");

            foreach (["cli", "session", "remember_me"] as $type) {
                $response = new TokenResponse($token, $type, null);
                $transformed = $spec->transform($response);
                expect($transformed["type"])->toBe($type);
            }
        });
    });
});
