<?php

use jschreuder\BookmarkBureau\InputSpec\ReorderCategoryLinksInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe("ReorderCategoryLinksInputSpec", function () {
    describe("getAvailableFields method", function () {
        test("returns array containing category_id and links", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toContain("category_id");
            expect($fields)->toContain("links");
            expect(count($fields))->toBe(2);
        });
    });

    describe("filter method", function () {
        test("filters category_id and links array", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $linkId1 = Uuid::uuid4()->toString();
            $linkId2 = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => "  {$categoryId}  ",
                "links" => [
                    ["link_id" => "  {$linkId1}  ", "sort_order" => 1],
                    ["link_id" => "  {$linkId2}  ", "sort_order" => 2],
                ],
            ]);

            expect($filtered["category_id"])->toBe($categoryId);
            expect($filtered["links"])->toHaveCount(2);
            expect($filtered["links"][0]["link_id"])->toBe($linkId1);
            expect($filtered["links"][0]["sort_order"])->toBe(1);
            expect($filtered["links"][1]["link_id"])->toBe($linkId2);
            expect($filtered["links"][1]["sort_order"])->toBe(2);
        });

        test("handles empty links array", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => $categoryId,
                "links" => [],
            ]);

            expect($filtered["links"])->toBe([]);
        });

        test("ignores non-array items in links", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => $categoryId,
                "links" => [
                    ["link_id" => $linkId, "sort_order" => 1],
                    "invalid_item",
                    ["link_id" => Uuid::uuid4()->toString(), "sort_order" => 2],
                ],
            ]);

            expect($filtered["links"])->toHaveCount(2);
        });

        test("converts sort_order to int", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => $categoryId,
                "links" => [["link_id" => $linkId, "sort_order" => "5"]],
            ]);

            expect($filtered["links"][0]["sort_order"])->toBe(5);
            expect(is_int($filtered["links"][0]["sort_order"]))->toBeTrue();
        });

        test("ignores additional fields in input", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                "category_id" => $categoryId,
                "links" => [],
                "extra_field" => "ignored",
            ]);

            expect($filtered)->not->toHaveKey("extra_field");
        });
    });

    describe("validate method", function () {
        test("passes validation with valid data", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $linkId1 = Uuid::uuid4()->toString();
            $linkId2 = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    "category_id" => $categoryId,
                    "links" => [
                        ["link_id" => $linkId1, "sort_order" => 1],
                        ["link_id" => $linkId2, "sort_order" => 2],
                    ],
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for empty links array", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "links" => [],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for missing category_id", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $linkId) {
                $spec->validate([
                    "links" => [["link_id" => $linkId, "sort_order" => 1]],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for invalid category_id", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $linkId) {
                $spec->validate([
                    "category_id" => "invalid-uuid",
                    "links" => [["link_id" => $linkId, "sort_order" => 1]],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for invalid link_id in links", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "links" => [["link_id" => "invalid-uuid", "sort_order" => 1]],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for non-integer sort_order", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId, $linkId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "links" => [["link_id" => $linkId, "sort_order" => "not-an-int"]],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for negative sort_order", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId, $linkId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "links" => [["link_id" => $linkId, "sort_order" => -1]],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for missing link_id", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "links" => [["sort_order" => 1]],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for missing sort_order", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId, $linkId) {
                $spec->validate([
                    "category_id" => $categoryId,
                    "links" => [["link_id" => $linkId]],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("passes validation with multiple links", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $linkIds = [
                Uuid::uuid4()->toString(),
                Uuid::uuid4()->toString(),
                Uuid::uuid4()->toString(),
            ];

            try {
                $spec->validate([
                    "category_id" => $categoryId,
                    "links" => [
                        ["link_id" => $linkIds[0], "sort_order" => 1],
                        ["link_id" => $linkIds[1], "sort_order" => 2],
                        ["link_id" => $linkIds[2], "sort_order" => 3],
                    ],
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });

    describe("integration scenarios", function () {
        test("full workflow: filter and validate with valid data", function () {
            $spec = new ReorderCategoryLinksInputSpec();
            $categoryId = Uuid::uuid4()->toString();
            $linkId1 = Uuid::uuid4()->toString();
            $linkId2 = Uuid::uuid4()->toString();

            $rawData = [
                "category_id" => "  {$categoryId}  ",
                "links" => [
                    ["link_id" => "  {$linkId1}  ", "sort_order" => 1],
                    ["link_id" => "  {$linkId2}  ", "sort_order" => 2],
                ],
            ];

            $filtered = $spec->filter($rawData);

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });
});
