<?php

use jschreuder\BookmarkBureau\InputSpec\TagNameInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;

describe('TagNameInputSpec', function () {
    describe('getAvailableFields method', function () {
        test('returns array containing only id field', function () {
            $spec = new TagNameInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toBe(['id']);
            expect(count($fields))->toBe(1);
        });
    });

    describe('filter method', function () {
        test('filters id field and trims whitespace', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'id' => '  Test Tag  '
            ]);

            expect($filtered['id'])->toBe('Test Tag');
        });

        test('handles missing id key with empty string', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([]);

            expect($filtered['id'])->toBe('');
        });

        test('preserves valid id without modification', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'id' => 'Important'
            ]);

            expect($filtered['id'])->toBe('Important');
        });

        test('ignores additional fields in input', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'id' => 'Test Tag',
                'color' => 'Should be ignored',
                'extra_field' => 'ignored'
            ]);

            expect($filtered)->toHaveKey('id');
            expect($filtered)->not->toHaveKey('color');
            expect($filtered)->not->toHaveKey('extra_field');
        });

        test('handles empty string id', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'id' => ''
            ]);

            expect($filtered['id'])->toBe('');
        });

        test('converts null id to empty string', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'id' => null
            ]);

            expect($filtered['id'])->toBe('');
        });

        test('filters only specific fields when provided', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'id' => 'Test Tag',
                'color' => 'Should be ignored'
            ], ['id']);

            expect($filtered)->toHaveKey('id');
            expect($filtered['id'])->toBe('Test Tag');
            expect(count($filtered))->toBe(1);
        });

        test('throws exception for unknown field', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->filter(['id' => 'test'], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('validate method', function () {
        test('passes validation with valid id', function () {
            $spec = new TagNameInputSpec();

            try {
                $spec->validate(['id' => 'Test Tag']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for empty id', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['id' => '']);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing id key', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate([]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for whitespace-only id', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['id' => '   ']);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for null id', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['id' => null]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for id longer than 256 characters', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['id' => str_repeat('a', 257)]);
            })->toThrow(ValidationFailedException::class);
        });

        test('passes validation with id at maximum length', function () {
            $spec = new TagNameInputSpec();

            try {
                $spec->validate(['id' => str_repeat('a', 256)]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('validates standard tag names', function () {
            $spec = new TagNameInputSpec();
            $validNames = ['Important', 'Work', 'Personal', 'To-Read', 'Bug Fix'];

            foreach ($validNames as $name) {
                try {
                    $spec->validate(['id' => $name]);
                    expect(true)->toBeTrue();
                } catch (ValidationFailedException $e) {
                    throw $e;
                }
            }
        });

        test('validates only specified fields when provided', function () {
            $spec = new TagNameInputSpec();

            try {
                $spec->validate(['id' => 'Test Tag'], ['id']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws exception for unknown field in fields parameter', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['id' => 'test'], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter and validate with valid data', function () {
            $spec = new TagNameInputSpec();

            $rawData = ['id' => '  Test Tag  '];
            $filtered = $spec->filter($rawData);

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('full workflow: filter removes extra fields', function () {
            $spec = new TagNameInputSpec();

            $rawData = [
                'id' => 'Test Tag',
                'color' => 'Should be ignored',
                'extra' => 'data'
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered)->not->toHaveKey('color');
            expect($filtered)->not->toHaveKey('extra');

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('filter handles whitespace, validate ensures proper length', function () {
            $spec = new TagNameInputSpec();

            $rawData = ['id' => '  Important  '];
            $filtered = $spec->filter($rawData);

            expect($filtered['id'])->toBe('Important');

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('validation failure after filter with invalid length', function () {
            $spec = new TagNameInputSpec();

            $rawData = ['id' => str_repeat('a', 257)];
            $filtered = $spec->filter($rawData);

            expect($filtered['id'])->toBe(str_repeat('a', 257));

            expect(function() use ($spec, $filtered) {
                $spec->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });

        test('handles multiple filter and validate cycles', function () {
            $spec = new TagNameInputSpec();

            $filtered1 = $spec->filter(['id' => '  Important  ']);
            $spec->validate($filtered1);

            $filtered2 = $spec->filter(['id' => '  Work  ']);
            $spec->validate($filtered2);

            expect($filtered1['id'])->toBe('Important');
            expect($filtered2['id'])->toBe('Work');
        });
    });
});
