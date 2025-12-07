<?php

use jschreuder\BookmarkBureau\InputSpec\TagInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;

describe("TagInputSpec", function () {
    describe("getAvailableFields method", function () {
        test("returns array containing all fields", function () {
            $spec = new TagInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toContain("tag_name");
            expect($fields)->toContain("color");
            expect(count($fields))->toBe(2);
        });
    });

    describe("filter method", function () {
        test("filters all fields with whitespace trimmed", function () {
            $spec = new TagInputSpec();

            $filtered = $spec->filter([
                "tag_name" => "  Test Tag  ",
                "color" => "  #FF0000  ",
            ]);

            expect($filtered["tag_name"])->toBe("Test Tag");
            expect($filtered["color"])->toBe("#FF0000");
        });

        test("handles missing tag_name key with empty string", function () {
            $spec = new TagInputSpec();

            $filtered = $spec->filter([
                "color" => "#FF0000",
            ]);

            expect($filtered["tag_name"])->toBe("");
        });

        test("handles missing color key with null", function () {
            $spec = new TagInputSpec();

            $filtered = $spec->filter([
                "tag_name" => "Test Tag",
            ]);

            expect($filtered["color"])->toBeNull();
        });

        test("converts null color to null", function () {
            $spec = new TagInputSpec();

            $filtered = $spec->filter([
                "tag_name" => "Test Tag",
                "color" => null,
            ]);

            expect($filtered["color"])->toBeNull();
        });

        test("ignores additional fields in input", function () {
            $spec = new TagInputSpec();

            $filtered = $spec->filter([
                "tag_name" => "Test Tag",
                "color" => "#FF0000",
                "extra_field" => "ignored",
            ]);

            expect($filtered)->toHaveKey("tag_name");
            expect($filtered)->toHaveKey("color");
            expect($filtered)->not->toHaveKey("extra_field");
        });

        test("filters only specific fields when provided", function () {
            $spec = new TagInputSpec();

            $filtered = $spec->filter(
                [
                    "tag_name" => "Test Tag",
                    "color" => "#FF0000",
                ],
                ["tag_name"],
            );

            expect($filtered)->toHaveKey("tag_name");
            expect($filtered)->not->toHaveKey("color");
            expect(count($filtered))->toBe(1);
        });

        test("throws exception for unknown field", function () {
            $spec = new TagInputSpec();

            expect(function () use ($spec) {
                $spec->filter(["tag_name" => "Test"], ["unknown_field"]);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe("validate method", function () {
        test("passes validation with valid data", function () {
            $spec = new TagInputSpec();

            try {
                $spec->validate([
                    "tag_name" => "Test Tag",
                    "color" => "#FF0000",
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("passes validation with color as null", function () {
            $spec = new TagInputSpec();

            try {
                $spec->validate([
                    "tag_name" => "Test Tag",
                    "color" => null,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for empty tag_name", function () {
            $spec = new TagInputSpec();

            expect(function () use ($spec) {
                $spec->validate([
                    "tag_name" => "",
                    "color" => "#FF0000",
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for missing tag_name", function () {
            $spec = new TagInputSpec();

            expect(function () use ($spec) {
                $spec->validate([
                    "color" => "#FF0000",
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test(
            "throws validation error for tag_name longer than 256 characters",
            function () {
                $spec = new TagInputSpec();

                expect(function () use ($spec) {
                    $spec->validate([
                        "tag_name" => str_repeat("a", 257),
                        "color" => "#FF0000",
                    ]);
                })->toThrow(ValidationFailedException::class);
            },
        );

        test("passes validation with tag_name at maximum length", function () {
            $spec = new TagInputSpec();

            try {
                $spec->validate([
                    "tag_name" => str_repeat("a", 256),
                    "color" => "#FF0000",
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test(
            "passes validation with integer-like tag_name due to length validation",
            function () {
                $spec = new TagInputSpec();

                try {
                    $spec->validate([
                        "tag_name" => 123,
                        "color" => "#FF0000",
                    ]);
                    expect(true)->toBeTrue();
                } catch (ValidationFailedException $e) {
                    throw $e;
                }
            },
        );

        test("throws validation error for non-string color", function () {
            $spec = new TagInputSpec();

            expect(function () use ($spec) {
                $spec->validate([
                    "tag_name" => "Test Tag",
                    "color" => 123,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("validates only specified fields when provided", function () {
            $spec = new TagInputSpec();

            try {
                $spec->validate(["tag_name" => "Test Tag"], ["tag_name"]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test(
            "throws exception for unknown field in fields parameter",
            function () {
                $spec = new TagInputSpec();

                expect(function () use ($spec) {
                    $spec->validate(["tag_name" => "Test"], ["unknown_field"]);
                })->toThrow(InvalidArgumentException::class);
            },
        );
    });

    describe("integration scenarios", function () {
        test("full workflow: filter and validate with valid data", function () {
            $spec = new TagInputSpec();

            $rawData = [
                "tag_name" => "  Test Tag  ",
                "color" => "  #FF0000  ",
                "extra" => "ignored",
            ];

            $filtered = $spec->filter($rawData);

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("full workflow: filter removes extra fields", function () {
            $spec = new TagInputSpec();

            $rawData = [
                "tag_name" => "Test Tag",
                "color" => "#FF0000",
                "extra_field" => "ignored",
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered)->not->toHaveKey("extra_field");

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("handles multiple filter and validate cycles", function () {
            $spec = new TagInputSpec();

            $filtered1 = $spec->filter([
                "tag_name" => "  Important  ",
                "color" => "  #FF0000  ",
            ]);
            $spec->validate($filtered1);

            $filtered2 = $spec->filter([
                "tag_name" => "  Work  ",
                "color" => "  #0000FF  ",
            ]);
            $spec->validate($filtered2);

            expect($filtered1["tag_name"])->toBe("Important");
            expect($filtered2["tag_name"])->toBe("Work");
        });
    });
});
