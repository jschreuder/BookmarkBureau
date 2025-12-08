<?php

use jschreuder\BookmarkBureau\InputSpec\ReorderCategoriesInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe('ReorderCategoriesInputSpec', function () {
    describe('getAvailableFields method', function () {
        test('returns array containing dashboard_id and categories', function () {
            $spec = new ReorderCategoriesInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toContain('dashboard_id');
            expect($fields)->toContain('categories');
            expect(count($fields))->toBe(2);
        });
    });

    describe('filter method', function () {
        test('filters dashboard_id and categories array', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $categoryId1 = Uuid::uuid4()->toString();
            $categoryId2 = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => "  {$dashboardId}  ",
                'categories' => [
                    ['category_id' => "  {$categoryId1}  ", 'sort_order' => 1],
                    ['category_id' => "  {$categoryId2}  ", 'sort_order' => 2],
                ],
            ]);

            expect($filtered['dashboard_id'])->toBe($dashboardId);
            expect($filtered['categories'])->toHaveCount(2);
            expect($filtered['categories'][0]['category_id'])->toBe($categoryId1);
            expect($filtered['categories'][0]['sort_order'])->toBe(1);
            expect($filtered['categories'][1]['category_id'])->toBe($categoryId2);
            expect($filtered['categories'][1]['sort_order'])->toBe(2);
        });

        test('handles empty categories array', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'categories' => [],
            ]);

            expect($filtered['categories'])->toBe([]);
        });

        test('ignores non-array items in categories', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $categoryId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'categories' => [
                    ['category_id' => $categoryId, 'sort_order' => 1],
                    'invalid_item',
                    ['category_id' => Uuid::uuid4()->toString(), 'sort_order' => 2],
                ],
            ]);

            expect($filtered['categories'])->toHaveCount(2);
        });

        test('converts sort_order to int', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $categoryId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'categories' => [
                    ['category_id' => $categoryId, 'sort_order' => '5'],
                ],
            ]);

            expect($filtered['categories'][0]['sort_order'])->toBe(5);
            expect(is_int($filtered['categories'][0]['sort_order']))->toBeTrue();
        });

        test('ignores additional fields in input', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'categories' => [],
                'extra_field' => 'ignored',
            ]);

            expect($filtered)->not->toHaveKey('extra_field');
        });
    });

    describe('validate method', function () {
        test('passes validation with valid data', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $categoryId1 = Uuid::uuid4()->toString();
            $categoryId2 = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'categories' => [
                        ['category_id' => $categoryId1, 'sort_order' => 1],
                        ['category_id' => $categoryId2, 'sort_order' => 2],
                    ],
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for empty categories array', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $dashboardId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'categories' => [],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing dashboard_id', function () {
            $spec = new ReorderCategoriesInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId) {
                $spec->validate([
                    'categories' => [
                        ['category_id' => $categoryId, 'sort_order' => 1],
                    ],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for invalid dashboard_id', function () {
            $spec = new ReorderCategoriesInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $categoryId) {
                $spec->validate([
                    'dashboard_id' => 'invalid-uuid',
                    'categories' => [
                        ['category_id' => $categoryId, 'sort_order' => 1],
                    ],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for invalid category_id in categories', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $dashboardId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'categories' => [
                        ['category_id' => 'invalid-uuid', 'sort_order' => 1],
                    ],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for non-integer sort_order', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $dashboardId, $categoryId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'categories' => [
                        ['category_id' => $categoryId, 'sort_order' => 'not-an-int'],
                    ],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for negative sort_order', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $dashboardId, $categoryId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'categories' => [
                        ['category_id' => $categoryId, 'sort_order' => -1],
                    ],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing category_id', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $dashboardId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'categories' => [
                        ['sort_order' => 1],
                    ],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing sort_order', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($spec, $dashboardId, $categoryId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'categories' => [
                        ['category_id' => $categoryId],
                    ],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('passes validation with multiple categories', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $categoryIds = [
                Uuid::uuid4()->toString(),
                Uuid::uuid4()->toString(),
                Uuid::uuid4()->toString(),
            ];

            try {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'categories' => [
                        ['category_id' => $categoryIds[0], 'sort_order' => 1],
                        ['category_id' => $categoryIds[1], 'sort_order' => 2],
                        ['category_id' => $categoryIds[2], 'sort_order' => 3],
                    ],
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter and validate with valid data', function () {
            $spec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $categoryId1 = Uuid::uuid4()->toString();
            $categoryId2 = Uuid::uuid4()->toString();

            $rawData = [
                'dashboard_id' => "  {$dashboardId}  ",
                'categories' => [
                    ['category_id' => "  {$categoryId1}  ", 'sort_order' => 1],
                    ['category_id' => "  {$categoryId2}  ", 'sort_order' => 2],
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
