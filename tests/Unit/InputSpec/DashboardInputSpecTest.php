<?php

use jschreuder\BookmarkBureau\InputSpec\DashboardInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe('DashboardInputSpec', function () {
    describe('getAvailableFields method', function () {
        test('returns array containing all fields', function () {
            $spec = new DashboardInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toContain('dashboard_id');
            expect($fields)->toContain('title');
            expect($fields)->toContain('description');
            expect($fields)->toContain('icon');
            expect(count($fields))->toBe(4);
        });
    });

    describe('filter method', function () {
        test('filters all fields with whitespace trimmed', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => "  {$dashboardId}  ",
                'title' => '  Test Dashboard  ',
                'description' => '  A test dashboard  ',
                'icon' => '  home  ',
            ]);

            expect($filtered['dashboard_id'])->toBe($dashboardId);
            expect($filtered['title'])->toBe('Test Dashboard');
            expect($filtered['description'])->toBe('A test dashboard');
            expect($filtered['icon'])->toBe('home');
        });

        test('strips HTML tags from title', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'title' => '<script>alert("xss")</script>Test<strong>Bold</strong>',
                'description' => 'A test',
            ]);

            expect($filtered['title'])->toBe('alert("xss")TestBold');
        });

        test('strips HTML tags from description', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'title' => 'Test',
                'description' => '<p>A test</p> <script>bad</script>description',
            ]);

            expect($filtered['description'])->toBe('A test baddescription');
        });

        test('handles missing id key with empty string', function () {
            $spec = new DashboardInputSpec();

            $filtered = $spec->filter([
                'title' => 'Test',
                'description' => 'A test',
            ]);

            expect($filtered['dashboard_id'])->toBe('');
        });

        test('handles missing title key with empty string', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'description' => 'A test',
            ]);

            expect($filtered['title'])->toBe('');
        });

        test('handles missing description key with empty string', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'title' => 'Test',
            ]);

            expect($filtered['description'])->toBe('');
        });

        test('handles missing icon key with null', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'title' => 'Test',
                'description' => 'A test',
            ]);

            expect($filtered['icon'])->toBeNull();
        });

        test('ignores additional fields in input', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'title' => 'Test',
                'description' => 'A test',
                'icon' => 'home',
                'extra_field' => 'ignored',
                'another_field' => 'also ignored'
            ]);

            expect($filtered)->toHaveKey('dashboard_id');
            expect($filtered)->toHaveKey('title');
            expect($filtered)->toHaveKey('description');
            expect($filtered)->toHaveKey('icon');
            expect($filtered)->not->toHaveKey('extra_field');
            expect($filtered)->not->toHaveKey('another_field');
        });

        test('converts null icon to null', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'title' => 'Test',
                'description' => 'A test',
                'icon' => null,
            ]);

            expect($filtered['icon'])->toBeNull();
        });

        test('filters only specific fields when provided', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'dashboard_id' => $dashboardId,
                'title' => 'Test',
                'description' => 'A test',
                'icon' => 'home',
            ], ['dashboard_id', 'title']);

            expect($filtered)->toHaveKey('dashboard_id');
            expect($filtered)->toHaveKey('title');
            expect($filtered)->not->toHaveKey('description');
            expect($filtered)->not->toHaveKey('icon');
            expect(count($filtered))->toBe(2);
        });

        test('throws exception for unknown field', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $dashboardId) {
                $spec->filter(['dashboard_id' => $dashboardId], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('validate method', function () {
        test('passes validation with valid data', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'title' => 'Test Dashboard',
                    'description' => 'A test dashboard',
                    'icon' => 'home',
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('passes validation with optional description and icon as null', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'title' => 'Test Dashboard',
                    'description' => null,
                    'icon' => null,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('passes validation with empty description string', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'title' => 'Test Dashboard',
                    'description' => '',
                    'icon' => null,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for invalid id UUID', function () {
            $spec = new DashboardInputSpec();

            expect(function() use ($spec) {
                $spec->validate([
                    'dashboard_id' => 'not-a-uuid',
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty id', function () {
            $spec = new DashboardInputSpec();

            expect(function() use ($spec) {
                $spec->validate([
                    'dashboard_id' => '',
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing id', function () {
            $spec = new DashboardInputSpec();

            expect(function() use ($spec) {
                $spec->validate([
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty title', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $dashboardId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'title' => '',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing title', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $dashboardId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for title longer than 256 characters', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $dashboardId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'title' => str_repeat('a', 257),
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('passes validation with title at maximum length', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'title' => 'A title at max length',
                    'description' => 'A test',
                    'icon' => null,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for non-string description', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $dashboardId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'title' => 'Test',
                    'description' => 123,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for non-string icon', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $dashboardId) {
                $spec->validate([
                    'dashboard_id' => $dashboardId,
                    'title' => 'Test',
                    'description' => 'A test',
                    'icon' => 123,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for non-string id', function () {
            $spec = new DashboardInputSpec();

            expect(function() use ($spec) {
                $spec->validate([
                    'dashboard_id' => 12345,
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('validates only specified fields when provided', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            try {
                $spec->validate(['dashboard_id' => $dashboardId], ['dashboard_id']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws exception for unknown field in fields parameter', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $dashboardId) {
                $spec->validate(['dashboard_id' => $dashboardId], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter and validate with valid data', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $rawData = [
                'dashboard_id' => "  {$dashboardId}  ",
                'title' => '  Test Dashboard  ',
                'description' => '  A test dashboard  ',
                'icon' => '  home  ',
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
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $rawData = [
                'dashboard_id' => $dashboardId,
                'title' => 'Test',
                'description' => 'A test',
                'icon' => 'home',
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

        test('filter applies html stripping, validate ensures proper format', function () {
            $spec = new DashboardInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $rawData = [
                'dashboard_id' => $dashboardId,
                'title' => '<script>Test</script>Dashboard',
                'description' => '<p>A test</p> dashboard',
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered['title'])->not->toContain('<script>');
            expect($filtered['title'])->toContain('Test');
            expect($filtered['description'])->not->toContain('<p>');
            expect($filtered['description'])->toContain('A test');

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('validation failure after filter with invalid UUID', function () {
            $spec = new DashboardInputSpec();

            $rawData = ['dashboard_id' => 'invalid-uuid', 'title' => 'Test', 'description' => 'A test'];
            $filtered = $spec->filter($rawData);

            expect($filtered['dashboard_id'])->toBe('invalid-uuid');

            expect(function() use ($spec, $filtered) {
                $spec->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });

        test('handles multiple filter and validate cycles', function () {
            $spec = new DashboardInputSpec();
            $dashboardId1 = Uuid::uuid4()->toString();
            $dashboardId2 = Uuid::uuid4()->toString();

            $filtered1 = $spec->filter([
                'dashboard_id' => "  {$dashboardId1}  ",
                'title' => 'Dashboard 1',
                'description' => 'First dashboard',
            ]);
            $spec->validate($filtered1);

            $filtered2 = $spec->filter([
                'dashboard_id' => "  {$dashboardId2}  ",
                'title' => 'Dashboard 2',
                'description' => 'Second dashboard',
            ]);
            $spec->validate($filtered2);

            expect($filtered1['dashboard_id'])->toBe($dashboardId1);
            expect($filtered2['dashboard_id'])->toBe($dashboardId2);
        });
    });
});
