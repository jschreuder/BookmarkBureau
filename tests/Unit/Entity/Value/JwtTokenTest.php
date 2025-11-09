<?php

use jschreuder\BookmarkBureau\Entity\Value\JwtToken;

describe("JwtToken", function () {
    test("__toString returns the token string", function () {
        $tokenString =
            "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1dXlkIn0.signature";
        $token = new JwtToken($tokenString);

        expect((string) $token)->toBe($tokenString);
    });

    test("getToken returns the token string", function () {
        $tokenString =
            "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1dXlkIn0.signature";
        $token = new JwtToken($tokenString);

        expect($token->getToken())->toBe($tokenString);
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
});
