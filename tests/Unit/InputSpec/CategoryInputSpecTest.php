<?php

use jschreuder\BookmarkBureau\InputSpec\CategoryInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe("CategoryInputSpec", function () {
    describe("getAvailableFields method", function () {
        test("returns array containing all fields", function () {
            $spec = new CategoryInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toContain("category_id");
            expect($fields)->toContain("dashboard_id");
            expect($fields)->toContain("title");
            expect($fields)->toContain("color");
            expect($fields)->toContain("sort_order");
            expect(count($fields))->toBe(5);
        });
    });

    describe("filter method", function () {
        test("filters all fields with whitespace trimmed", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => "  {$categoryId}  ",
                "dashboard_id" => "  {$dashboardId}  ",
                "title" => "  Test Category  ",
                "color" => "  #ffffff  ",
                "sort_order" => 5,
            ]);

            expect($filtered["category_id"])->toBe($categoryId);
            expect($filtered["dashboard_id"])->toBe($dashboardId);
            expect($filtered["title"])->toBe("Test Category");
            expect($filtered["color"])->toBe("#ffffff");
            expect($filtered["sort_order"])->toBe(5);
        });

        test("strips HTML tags from title", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => $categoryId,
                "dashboard_id" => $dashboardId,
                "title" =>
                    '<script>alert("xss")</script>Test Category<strong>Bold</strong>',
            ]);

            expect($filtered["title"])->toBe('alert("xss")Test CategoryBold');
        });

        test("handles missing id key with empty string", function () {
            $spec = new CategoryInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "dashboard_id" => $dashboardId,
                "title" => "Test",
            ]);

            expect($filtered["category_id"])->toBe("");
        });

        test("handles missing dashboard_id key with empty string", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => $categoryId,
                "title" => "Test",
            ]);

            expect($filtered["dashboard_id"])->toBe("");
        });

        test("handles missing title key with empty string", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => $categoryId,
                "dashboard_id" => $dashboardId,
            ]);

            expect($filtered["title"])->toBe("");
        });

        test("handles missing color key with null", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => $categoryId,
                "dashboard_id" => $dashboardId,
                "title" => "Test",
            ]);

            expect($filtered["color"])->toBeNull();
        });

        test(
            "handles missing sort_order key with default value 1",
            function () {
                $spec = new CategoryInputSpec();
                $categoryId = Uuid::uuid4()->toString();
                $dashboardId = Uuid::uuid4()->toString();

                $filtered = $spec->filter([
                    "category_id" => $categoryId,
                    "dashboard_id" => $dashboardId,
                    "title" => "Test",
                ]);

                expect($filtered["sort_order"])->toBe(1);
            },
        );

        test("ignores additional fields in input", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => $categoryId,
                "dashboard_id" => $dashboardId,
                "title" => "Test",
                "extra_field" => "ignored",
                "another_field" => "also ignored",
            ]);

            expect($filtered)->toHaveKey("category_id");
            expect($filtered)->toHaveKey("dashboard_id");
            expect($filtered)->toHaveKey("title");
            expect($filtered)->not->toHaveKey("extra_field");
            expect($filtered)->not->toHaveKey("another_field");
        });

        test("converts null color to null", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => $categoryId,
                "dashboard_id" => $dashboardId,
                "title" => "Test",
                "color" => null,
            ]);

            expect($filtered["color"])->toBeNull();
        });

        test("filters only specific fields when provided", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter(
                [
                    "category_id" => $categoryId,
                    "dashboard_id" => $dashboardId,
                    "title" => "Test",
                    "color" => "#fff",
                    "sort_order" => 3,
                ],
                ["category_id", "title"],
            );

            expect($filtered)->toHaveKey("category_id");
            expect($filtered)->toHaveKey("title");
            expect($filtered)->not->toHaveKey("dashboard_id");
            expect($filtered)->not->toHaveKey("color");
            expect($filtered)->not->toHaveKey("sort_order");
            expect(count($filtered))->toBe(2);
        });

        test("throws exception for unknown field", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId) {
                $spec->filter(["category_id" => $categoryId], ["unknown_field"]);
            })->toThrow(InvalidArgumentException::class);
        });

        test("converts sort_order to integer", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => $categoryId,
                "dashboard_id" => $dashboardId,
                "title" => "Test",
                "sort_order" => "42",
            ]);

            expect($filtered["sort_order"])->toBeInt();
            expect($filtered["sort_order"])->toBe(42);
        });
    });

    describe("validate method", function () {
        test("passes validation with valid data", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    "category_id" => $categoryId,
                    "dashboard_id" => $dashboardId,
                    "title" => "Test Category",
                    "color" => null,
                    "sort_order" => 1,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("passes validation with valid hex color", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    "category_id" => $categoryId,
                    "dashboard_id" => $dashboardId,
                    "title" => "Test Category",
                    "color" => "#ffffff",
                    "sort_order" => 1,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("passes validation with various hex color formats", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            $validColors = [
                "#000",
                "#000000",
                "#fff",
                "#ffffff",
                "#123456",
                "#abc",
            ];

            foreach ($validColors as $color) {
                try {
                    $spec->validate([
                        "category_id" => $categoryId,
                        "dashboard_id" => $dashboardId,
                        "title" => "Test Category",
                        "color" => $color,
                        "sort_order" => 1,
                    ]);
                    expect(true)->toBeTrue();
                } catch (ValidationFailedException $e) {
                    throw $e;
                }
            }
        });

        test("throws validation error for invalid id UUID", function () {
            $spec = new CategoryInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $dashboardId) {
                $spec->validate([
                    "category_id" => "not-a-uuid",
                    "dashboard_id" => $dashboardId,
                    "title" => "Test",
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for empty id", function () {
            $spec = new CategoryInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $dashboardId) {
                $spec->validate([
                    "category_id" => "",
                    "dashboard_id" => $dashboardId,
                    "title" => "Test",
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for missing id", function () {
            $spec = new CategoryInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $dashboardId) {
                $spec->validate([
                    "dashboard_id" => $dashboardId,
                    "title" => "Test",
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test(
            "throws validation error for invalid dashboard_id UUID",
            function () {
                $spec = new CategoryInputSpec();
                $categoryId = Uuid::uuid4()->toString();

                expect(function () use ($spec, $categoryId) {
                    $spec->validate([
                        "category_id" => $categoryId,
                        "dashboard_id" => "not-a-uuid",
                        "title" => "Test",
                    ]);
                })->toThrow(ValidationFailedException::class);
            },
        );

        test("throws validation error for empty dashboard_id", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "dashboard_id" => "",
                    "title" => "Test",
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for missing dashboard_id", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "title" => "Test",
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for empty title", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId, $dashboardId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "dashboard_id" => $dashboardId,
                    "title" => "",
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for missing title", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId, $dashboardId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "dashboard_id" => $dashboardId,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test(
            "throws validation error for title longer than 256 characters",
            function () {
                $spec = new CategoryInputSpec();
                $categoryId = Uuid::uuid4()->toString();
                $dashboardId = Uuid::uuid4()->toString();

                expect(function () use ($spec, $categoryId, $dashboardId) {
                    $spec->validate([
                        "category_id" => $categoryId,
                        "dashboard_id" => $dashboardId,
                        "title" => str_repeat("a", 257),
                    ]);
                })->toThrow(ValidationFailedException::class);
            },
        );

        test("passes validation with title at maximum length", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    "category_id" => $categoryId,
                    "dashboard_id" => $dashboardId,
                    "title" => "A title at max length",
                    "color" => null,
                    "sort_order" => 1,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for invalid hex color", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId, $dashboardId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "dashboard_id" => $dashboardId,
                    "title" => "Test",
                    "color" => "not-a-color",
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for non-integer sort_order", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId, $dashboardId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "dashboard_id" => $dashboardId,
                    "title" => "Test",
                    "sort_order" => "not-an-int",
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for non-string id", function () {
            $spec = new CategoryInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $dashboardId) {
                $spec->validate([
                    "category_id" => 12345,
                    "dashboard_id" => $dashboardId,
                    "title" => "Test",
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("validates only specified fields when provided", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            try {
                $spec->validate(["category_id" => $categoryId], ["category_id"]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test(
            "throws exception for unknown field in fields parameter",
            function () {
                $spec = new CategoryInputSpec();
                $categoryId = Uuid::uuid4()->toString();

                expect(function () use ($spec, $categoryId) {
                    $spec->validate(["category_id" => $categoryId], ["unknown_field"]);
                })->toThrow(InvalidArgumentException::class);
            },
        );
    });

    describe("integration scenarios", function () {
        test("full workflow: filter and validate with valid data", function () {
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            $rawData = [
                "category_id" => "  {$categoryId}  ",
                "dashboard_id" => "  {$dashboardId}  ",
                "title" => "  Test Category  ",
                "color" => "  #ffffff  ",
                "sort_order" => 5,
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
            $spec = new CategoryInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $dashboardId = Uuid::uuid4()->toString();

            $rawData = [
                "category_id" => $categoryId,
                "dashboard_id" => $dashboardId,
                "title" => "Test",
                "color" => "#fff",
                "sort_order" => 1,
                "extra_field" => "ignored",
                "another_field" => "data",
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered)->not->toHaveKey("extra_field");
            expect($filtered)->not->toHaveKey("another_field");

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test(
            "filter applies html stripping, validate ensures proper format",
            function () {
                $spec = new CategoryInputSpec();
                $categoryId = Uuid::uuid4()->toString();
                $dashboardId = Uuid::uuid4()->toString();

                $rawData = [
                    "category_id" => $categoryId,
                    "dashboard_id" => $dashboardId,
                    "title" => "<script>Test</script>Category",
                ];

                $filtered = $spec->filter($rawData);

                expect($filtered["title"])->not->toContain("<script>");
                expect($filtered["title"])->toContain("Test");
                expect($filtered["title"])->toContain("Category");

                try {
                    $spec->validate($filtered);
                    expect(true)->toBeTrue();
                } catch (ValidationFailedException $e) {
                    throw $e;
                }
            },
        );

        test("validation failure after filter with invalid UUID", function () {
            $spec = new CategoryInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $rawData = [
                "category_id" => "invalid-uuid",
                "dashboard_id" => $dashboardId,
                "title" => "Test",
            ];
            $filtered = $spec->filter($rawData);

            expect($filtered["category_id"])->toBe("invalid-uuid");

            expect(function () use ($spec, $filtered) {
                $spec->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });

        test("handles multiple filter and validate cycles", function () {
            $spec = new CategoryInputSpec();
            $categoryId1 = Uuid::uuid4()->toString();
            $dashboardId1 = Uuid::uuid4()->toString();
            $categoryId2 = Uuid::uuid4()->toString();
            $dashboardId2 = Uuid::uuid4()->toString();

            $filtered1 = $spec->filter([
                "category_id" => "  {$categoryId1}  ",
                "dashboard_id" => "  {$dashboardId1}  ",
                "title" => "Category 1",
            ]);
            $spec->validate($filtered1);

            $filtered2 = $spec->filter([
                "category_id" => "  {$categoryId2}  ",
                "dashboard_id" => "  {$dashboardId2}  ",
                "title" => "Category 2",
            ]);
            $spec->validate($filtered2);

            expect($filtered1["category_id"])->toBe($categoryId1);
            expect($filtered2["category_id"])->toBe($categoryId2);
        });
    });
});
