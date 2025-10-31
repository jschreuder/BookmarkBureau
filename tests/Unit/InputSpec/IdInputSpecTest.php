<?php

use jschreuder\BookmarkBureau\InputSpec\IdInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe('IdInputSpec', function () {
    describe('getAvailableFields method', function () {
        test('returns array containing only id field', function () {
            $spec = new IdInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toBe(['id']);
            expect(count($fields))->toBe(1);
        });
    });

    describe('filter method', function () {
        test('filters id field and trims whitespace', function () {
            $spec = new IdInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => "  {$uuid}  "
            ]);

            expect($filtered['id'])->toBe($uuid);
        });

        test('handles missing id key with empty string', function () {
            $spec = new IdInputSpec();

            $filtered = $spec->filter([]);

            expect($filtered['id'])->toBe('');
        });

        test('preserves valid id without modification', function () {
            $spec = new IdInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $uuid
            ]);

            expect($filtered['id'])->toBe($uuid);
        });

        test('ignores additional fields in input', function () {
            $spec = new IdInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $uuid,
                'title' => 'Should be ignored',
                'color' => 'Should be ignored',
                'extra_field' => 'ignored'
            ]);

            expect($filtered)->toHaveKey('id');
            expect($filtered)->not->toHaveKey('title');
            expect($filtered)->not->toHaveKey('color');
            expect($filtered)->not->toHaveKey('extra_field');
        });

        test('handles empty string id', function () {
            $spec = new IdInputSpec();

            $filtered = $spec->filter([
                'id' => ''
            ]);

            expect($filtered['id'])->toBe('');
        });

        test('converts null id to empty string', function () {
            $spec = new IdInputSpec();

            $filtered = $spec->filter([
                'id' => null
            ]);

            expect($filtered['id'])->toBe('');
        });

        test('filters only specific fields when provided', function () {
            $spec = new IdInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $uuid,
                'title' => 'Should be ignored'
            ], ['id']);

            expect($filtered)->toHaveKey('id');
            expect($filtered['id'])->toBe($uuid);
            expect(count($filtered))->toBe(1);
        });

        test('throws exception for unknown field', function () {
            $spec = new IdInputSpec();

            expect(function() use ($spec) {
                $spec->filter(['id' => 'test'], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('validate method', function () {
        test('passes validation with valid UUID', function () {
            $spec = new IdInputSpec();
            $uuid = Uuid::uuid4()->toString();

            try {
                $spec->validate(['id' => $uuid]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for invalid UUID', function () {
            $spec = new IdInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['id' => 'not-a-uuid']);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty id', function () {
            $spec = new IdInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['id' => '']);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing id key', function () {
            $spec = new IdInputSpec();

            expect(function() use ($spec) {
                $spec->validate([]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for whitespace-only id', function () {
            $spec = new IdInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['id' => '   ']);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for null id', function () {
            $spec = new IdInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['id' => null]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for non-string id', function () {
            $spec = new IdInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['id' => 12345]);
            })->toThrow(ValidationFailedException::class);
        });

        test('validates standard UUID format', function () {
            $spec = new IdInputSpec();
            $uuid = Uuid::uuid4()->toString();

            try {
                $spec->validate(['id' => $uuid]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('validates multiple UUID formats', function () {
            $spec = new IdInputSpec();
            $uuid1 = Uuid::uuid4();
            $uuid2 = Uuid::uuid4();
            $uuid3 = Uuid::uuid4();

            try {
                $spec->validate(['id' => $uuid1->toString()]);
                $spec->validate(['id' => $uuid2->toString()]);
                $spec->validate(['id' => $uuid3->toString()]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('validates only specified fields when provided', function () {
            $spec = new IdInputSpec();
            $uuid = Uuid::uuid4()->toString();

            try {
                $spec->validate(['id' => $uuid], ['id']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws exception for unknown field in fields parameter', function () {
            $spec = new IdInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['id' => 'test'], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter and validate with valid data', function () {
            $spec = new IdInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $rawData = ['id' => "  {$uuid}  "];
            $filtered = $spec->filter($rawData);

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('full workflow: filter removes extra fields', function () {
            $spec = new IdInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $rawData = [
                'id' => $uuid,
                'title' => 'Should be ignored',
                'color' => 'Should be ignored',
                'extra' => 'data'
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered)->not->toHaveKey('title');
            expect($filtered)->not->toHaveKey('color');
            expect($filtered)->not->toHaveKey('extra');

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('filter handles whitespace, validate ensures UUID format', function () {
            $spec = new IdInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $rawData = ["  {$uuid}  "];
            $filtered = $spec->filter(['id' => "  {$uuid}  "]);

            expect($filtered['id'])->toBe($uuid);

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('validation failure after filter with invalid UUID', function () {
            $spec = new IdInputSpec();

            $rawData = ['id' => 'invalid-uuid'];
            $filtered = $spec->filter($rawData);

            expect($filtered['id'])->toBe('invalid-uuid');

            expect(function() use ($spec, $filtered) {
                $spec->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });

        test('handles multiple filter and validate cycles', function () {
            $spec = new IdInputSpec();
            $uuid1 = Uuid::uuid4()->toString();
            $uuid2 = Uuid::uuid4()->toString();

            $filtered1 = $spec->filter(['id' => "  {$uuid1}  "]);
            $spec->validate($filtered1);

            $filtered2 = $spec->filter(['id' => "  {$uuid2}  "]);
            $spec->validate($filtered2);

            expect($filtered1['id'])->toBe($uuid1);
            expect($filtered2['id'])->toBe($uuid2);
        });
    });
});
