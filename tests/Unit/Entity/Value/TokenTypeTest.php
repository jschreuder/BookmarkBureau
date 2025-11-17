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

    describe("equals method", function () {
        test("equals returns true for same enum case", function () {
            $type1 = TokenType::CLI_TOKEN;
            $type2 = TokenType::CLI_TOKEN;

            expect($type1->equals($type2))->toBeTrue();
        });

        test("equals returns false for different enum cases", function () {
            expect(
                TokenType::CLI_TOKEN->equals(TokenType::SESSION_TOKEN),
            )->toBeFalse();
        });

        test("equals returns false for different enum cases 2", function () {
            expect(
                TokenType::SESSION_TOKEN->equals(TokenType::REMEMBER_ME_TOKEN),
            )->toBeFalse();
        });

        test("equals returns false for different enum cases 3", function () {
            expect(
                TokenType::REMEMBER_ME_TOKEN->equals(TokenType::CLI_TOKEN),
            )->toBeFalse();
        });

        test(
            "equals returns false when comparing with different type",
            function () {
                expect(
                    TokenType::CLI_TOKEN->equals(new stdClass()),
                )->toBeFalse();
            },
        );

        test(
            "equals returns false when comparing with non-enum object",
            function () {
                $token = new \jschreuder\BookmarkBureau\Entity\Value\JwtToken(
                    "test.token",
                );

                expect(TokenType::CLI_TOKEN->equals($token))->toBeFalse();
            },
        );

        test("equals returns true for all CLI_TOKEN references", function () {
            expect(
                TokenType::CLI_TOKEN->equals(TokenType::from("cli")),
            )->toBeTrue();
        });

        test(
            "equals returns true for all SESSION_TOKEN references",
            function () {
                expect(
                    TokenType::SESSION_TOKEN->equals(
                        TokenType::from("session"),
                    ),
                )->toBeTrue();
            },
        );

        test(
            "equals returns true for all REMEMBER_ME_TOKEN references",
            function () {
                expect(
                    TokenType::REMEMBER_ME_TOKEN->equals(
                        TokenType::from("remember_me"),
                    ),
                )->toBeTrue();
            },
        );
    });
});
