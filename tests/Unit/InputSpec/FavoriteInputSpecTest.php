<?php

use jschreuder\BookmarkBureau\InputSpec\FavoriteInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe('FavoriteInputSpec', function () {
    describe('getAvailableFields method', function () {
        test('returns array containing all fields', function () {
            $spec = new FavoriteInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toContain('dashboard_id');
            expect($fields)->toContain('link_id');
            expect($fields)->toContain('sort_order');
            expect(count($fields))->toBe(3);
        });
    });

    describe('filter method', function () {
        test('filters all fields with whitespace trimmed', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => "  {$dashboardId}  ",
                'link_id' => "  {$linkId}  ",
                'sort_order' => 1,
            ]);

            expect($filtered['dashboard_id'])->toBe($dashboardId);
            expect($filtered['link_id'])->toBe($linkId);
            expect($filtered['sort_order'])->toBe(1);
        });

        test('handles missing dashboard_id key with empty string', function () {
            $spec = new FavoriteInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'link_id' => $linkId,
                'sort_order' => 1,
            ]);

            expect($filtered['dashboard_id'])->toBe('');
        });

        test('handles missing link_id key with empty string', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'sort_order' => 1,
            ]);

            expect($filtered['link_id'])->toBe('');
        });

        test('handles missing sort_order key with null', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'link_id' => $linkId,
            ]);

            expect($filtered['sort_order'])->toBeNull();
        });

        test('ignores additional fields in input', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'link_id' => $linkId,
                'sort_order' => 1,
                'extra_field' => 'ignored',
            ]);

            expect($filtered)->toHaveKey('dashboard_id');
            expect($filtered)->toHaveKey('link_id');
            expect($filtered)->toHaveKey('sort_order');
            expect($filtered)->not->toHaveKey('extra_field');
        });

        test('filters only specific fields when provided', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'link_id' => $linkId,
                'sort_order' => 1,
            ], ['dashboard_id', 'link_id']);

            expect($filtered)->toHaveKey('dashboard_id');
            expect($filtered)->toHaveKey('link_id');
            expect($filtered)->not->toHaveKey('sort_order');
            expect(count($filtered))->toBe(2);
        });

        test('throws exception for unknown field', function () {
            $spec = new FavoriteInputSpec();

            expect(function() use ($spec) {
                $spec->filter([], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });

        test('converts string sort_order to int', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'link_id' => $linkId,
                'sort_order' => '5',
            ]);

            expect($filtered['sort_order'])->toBe(5);
            expect(is_int($filtered['sort_order']))->toBeTrue();
        });
    });

    describe('validate method', function () {
        test('passes validation with valid data', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'link_id' => $linkId,
                    'sort_order' => 1,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('passes validation with optional sort_order as null', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'link_id' => $linkId,
                    'sort_order' => null,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for invalid dashboard_id UUID', function () {
            $spec = new FavoriteInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'dashboard_id' => 'not-a-uuid',
                    'link_id' => $linkId,
                    'sort_order' => 1,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty dashboard_id', function () {
            $spec = new FavoriteInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'dashboard_id' => '',
                    'link_id' => $linkId,
                    'sort_order' => 1,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing dashboard_id', function () {
            $spec = new FavoriteInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'link_id' => $linkId,
                    'sort_order' => 1,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for invalid link_id UUID', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $dashboardId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'link_id' => 'not-a-uuid',
                    'sort_order' => 1,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty link_id', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $dashboardId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'link_id' => '',
                    'sort_order' => 1,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing link_id', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $dashboardId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'sort_order' => 1,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for non-integer sort_order', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $dashboardId, $linkId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'link_id' => $linkId,
                    'sort_order' => 'not-an-int',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('validates only specified fields when provided', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            try {
                $spec->validate(['dashboard_id' => $dashboardId], ['dashboard_id']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws exception for unknown field in fields parameter', function () {
            $spec = new FavoriteInputSpec();

            expect(function() use ($spec) {
                $spec->validate([], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter and validate with valid data', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $rawData = [
                'dashboard_id' => "  {$dashboardId}  ",
                'link_id' => "  {$linkId}  ",
                'sort_order' => 1,
                'extra' => 'ignored'
            ];

            $filtered = $spec->filter($rawData);

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('full workflow: filter removes extra fields', function () {
            $spec = new FavoriteInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $rawData = [
                'dashboard_id' => $dashboardId,
                'link_id' => $linkId,
                'sort_order' => 1,
                'extra_field' => 'ignored',
                'another_field' => 'data'
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered)->not->toHaveKey('extra_field');
            expect($filtered)->not->toHaveKey('another_field');

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });
});
