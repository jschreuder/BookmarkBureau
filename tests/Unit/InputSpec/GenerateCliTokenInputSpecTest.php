<?php

use jschreuder\BookmarkBureau\InputSpec\GenerateCliTokenInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;

describe("GenerateCliTokenInputSpec", function () {
    describe("getAvailableFields", function () {
        test("returns expected fields", function () {
            $spec = new GenerateCliTokenInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toContain("email");
            expect($fields)->toContain("password");
            expect(count($fields))->toBe(2);
        });
    });

    describe("filter", function () {
        test("filters email by lowercasing", function () {
            $spec = new GenerateCliTokenInputSpec();
            $rawData = ["email" => "Test@EXAMPLE.COM", "password" => "pass"];

            $filtered = $spec->filter($rawData);

            expect($filtered["email"])->toBe("test@example.com");
        });

        test("filters email by trimming whitespace", function () {
            $spec = new GenerateCliTokenInputSpec();
            $rawData = [
                "email" => "  test@example.com  ",
                "password" => "pass",
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered["email"])->toBe("test@example.com");
        });

        test("keeps password as-is", function () {
            $spec = new GenerateCliTokenInputSpec();
            $rawData = [
                "email" => "test@example.com",
                "password" => "MyPassword123!",
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered["password"])->toBe("MyPassword123!");
        });

        test("filters only requested fields", function () {
            $spec = new GenerateCliTokenInputSpec();
            $rawData = ["email" => "Test@Example.com", "password" => "pass"];

            $filtered = $spec->filter($rawData, ["email"]);

            expect($filtered)->toHaveKey("email");
            expect($filtered["email"])->toBe("test@example.com");
        });
    });

    describe("validate", function () {
        test("validates valid email and password", function () {
            $spec = new GenerateCliTokenInputSpec();
            $data = [
                "email" => "test@example.com",
                "password" => "password123",
            ];

            // Should not throw
            $spec->validate($data);
            expect(true)->toBeTrue();
        });

        test("throws on invalid email format", function () {
            $spec = new GenerateCliTokenInputSpec();
            $data = ["email" => "not-an-email", "password" => "password123"];

            expect(fn() => $spec->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws on empty email", function () {
            $spec = new GenerateCliTokenInputSpec();
            $data = ["email" => "", "password" => "password123"];

            expect(fn() => $spec->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws on empty password", function () {
            $spec = new GenerateCliTokenInputSpec();
            $data = ["email" => "test@example.com", "password" => ""];

            expect(fn() => $spec->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("validates only requested fields", function () {
            $spec = new GenerateCliTokenInputSpec();
            $data = ["email" => "test@example.com", "password" => "pass"];

            $spec->validate($data, ["email"]);
            expect(true)->toBeTrue();
        });
    });
});
