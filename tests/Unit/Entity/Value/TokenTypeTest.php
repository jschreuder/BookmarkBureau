<?php

use jschreuder\BookmarkBureau\Entity\Value\TokenType;

describe("TokenType", function () {
    test("CLI_TOKEN enum has correct value", function () {
        expect(TokenType::CLI_TOKEN->value)->toBe("cli");
    });

    test("SESSION_TOKEN enum has correct value", function () {
        expect(TokenType::SESSION_TOKEN->value)->toBe("session");
    });

    test("REMEMBER_ME_TOKEN enum has correct value", function () {
        expect(TokenType::REMEMBER_ME_TOKEN->value)->toBe("remember_me");
    });

    test("can create from string value", function () {
        $type = TokenType::from("cli");
        expect($type)->toBe(TokenType::CLI_TOKEN);

        $type = TokenType::from("session");
        expect($type)->toBe(TokenType::SESSION_TOKEN);

        $type = TokenType::from("remember_me");
        expect($type)->toBe(TokenType::REMEMBER_ME_TOKEN);
    });

    test("throws ValueError for invalid value", function () {
        expect(fn() => TokenType::from("invalid"))->toThrow(\ValueError::class);
    });

    test("can iterate all cases", function () {
        $cases = TokenType::cases();
        expect(count($cases))->toBe(3);
        expect($cases)->toContain(TokenType::CLI_TOKEN);
        expect($cases)->toContain(TokenType::SESSION_TOKEN);
        expect($cases)->toContain(TokenType::REMEMBER_ME_TOKEN);
    });
});
