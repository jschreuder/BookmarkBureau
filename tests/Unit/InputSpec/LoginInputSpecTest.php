<?php

use jschreuder\BookmarkBureau\InputSpec\LoginInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;

describe("LoginInputSpec", function () {
    describe("getAvailableFields", function () {
        test("returns expected fields", function () {
            $spec = new LoginInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toContain("email");
            expect($fields)->toContain("password");
            expect($fields)->toContain("remember_me");
            expect($fields)->toContain("totp_code");
        });
    });

    describe("filter", function () {
        test("filters email by lowercasing", function () {
            $spec = new LoginInputSpec();
            $rawData = [
                "email" => "Test@EXAMPLE.COM",
                "password" => "pass",
                "remember_me" => false,
                "totp_code" => "",
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered["email"])->toBe("test@example.com");
        });

        test("filters email by trimming whitespace", function () {
            $spec = new LoginInputSpec();
            $rawData = [
                "email" => "  test@example.com  ",
                "password" => "pass",
                "remember_me" => false,
                "totp_code" => "",
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered["email"])->toBe("test@example.com");
        });

        test("keeps password as-is", function () {
            $spec = new LoginInputSpec();
            $rawData = [
                "email" => "test@example.com",
                "password" => "MyPassword123!",
                "remember_me" => false,
                "totp_code" => "",
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered["password"])->toBe("MyPassword123!");
        });

        test("converts rememberMe to boolean", function () {
            $spec = new LoginInputSpec();

            $filtered1 = $spec->filter([
                "email" => "test@example.com",
                "password" => "pass",
                "remember_me" => 1,
            ]);
            expect($filtered1["remember_me"])->toBeTrue();

            $filtered2 = $spec->filter([
                "email" => "test@example.com",
                "password" => "pass",
                "remember_me" => 0,
            ]);
            expect($filtered2["remember_me"])->toBeFalse();

            $filtered3 = $spec->filter([
                "email" => "test@example.com",
                "password" => "pass",
                "remember_me" => true,
            ]);
            expect($filtered3["remember_me"])->toBeTrue();
        });

        test("defaults rememberMe to false when missing", function () {
            $spec = new LoginInputSpec();
            $rawData = ["email" => "test@example.com", "password" => "pass"];

            $filtered = $spec->filter($rawData);

            expect($filtered["remember_me"])->toBeFalse();
            expect($filtered["totp_code"])->toBe("");
        });

        test("filters only requested fields", function () {
            $spec = new LoginInputSpec();
            $rawData = [
                "email" => "Test@Example.com",
                "password" => "pass",
                "remember_me" => false,
            ];

            $filtered = $spec->filter($rawData, ["email"]);

            expect($filtered)->toHaveKey("email");
            expect($filtered["email"])->toBe("test@example.com");
        });
    });

    describe("validate", function () {
        test("validates valid email and password", function () {
            $spec = new LoginInputSpec();
            $data = [
                "email" => "test@example.com",
                "password" => "password123",
                "remember_me" => false,
                "totp_code" => "",
            ];

            // Should not throw
            $spec->validate($data);
            expect(true)->toBeTrue();
        });

        test("throws on invalid email format", function () {
            $spec = new LoginInputSpec();
            $data = [
                "email" => "not-an-email",
                "password" => "password123",
                "totp_code" => "",
            ];

            expect(fn() => $spec->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws on empty email", function () {
            $spec = new LoginInputSpec();
            $data = [
                "email" => "",
                "password" => "password123",
                "totp_code" => "",
            ];

            expect(fn() => $spec->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws on empty password", function () {
            $spec = new LoginInputSpec();
            $data = [
                "email" => "test@example.com",
                "password" => "",
                "totp_code" => "",
            ];

            expect(fn() => $spec->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("validates boolean rememberMe", function () {
            $spec = new LoginInputSpec();
            $data = [
                "email" => "test@example.com",
                "password" => "pass",
                "remember_me" => true,
                "totp_code" => "",
            ];

            $spec->validate($data);
            expect(true)->toBeTrue();
        });

        test("validates only requested fields", function () {
            $spec = new LoginInputSpec();
            $data = [
                "email" => "test@example.com",
                "password" => "pass",
                "totp_code" => "",
            ];

            $spec->validate($data, ["email"]);
            expect(true)->toBeTrue();
        });
    });
});
