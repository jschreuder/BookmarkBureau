<?php

use jschreuder\BookmarkBureau\InputSpec\TagNameInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;

describe('TagNameInputSpec', function () {
    describe('getAvailableFields method', function () {
        test('returns array containing only tag_name field', function () {
            $spec = new TagNameInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toBe(['tag_name']);
            expect(count($fields))->toBe(1);
        });
    });

    describe('filter method', function () {
        test('filters tag_name field and trims whitespace', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'tag_name' => '  Test Tag  '
            ]);

            expect($filtered['tag_name'])->toBe('Test Tag');
        });

        test('handles missing tag_name key with empty string', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([]);

            expect($filtered['tag_name'])->toBe('');
        });

        test('preserves valid tag_name without modification', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'tag_name' => 'Important'
            ]);

            expect($filtered['tag_name'])->toBe('Important');
        });

        test('ignores additional fields in input', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'tag_name' => 'Test Tag',
                'color' => 'Should be ignored',
                'extra_field' => 'ignored'
            ]);

            expect($filtered)->toHaveKey('tag_name');
            expect($filtered)->not->toHaveKey('color');
            expect($filtered)->not->toHaveKey('extra_field');
        });

        test('handles empty string tag_name', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'tag_name' => ''
            ]);

            expect($filtered['tag_name'])->toBe('');
        });

        test('converts null tag_name to empty string', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'tag_name' => null
            ]);

            expect($filtered['tag_name'])->toBe('');
        });

        test('filters only specific fields when provided', function () {
            $spec = new TagNameInputSpec();

            $filtered = $spec->filter([
                'tag_name' => 'Test Tag',
                'color' => 'Should be ignored'
            ], ['tag_name']);

            expect($filtered)->toHaveKey('tag_name');
            expect($filtered['tag_name'])->toBe('Test Tag');
            expect(count($filtered))->toBe(1);
        });

        test('throws exception for unknown field', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->filter(['tag_name' => 'test'], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('validate method', function () {
        test('passes validation with valid tag_name', function () {
            $spec = new TagNameInputSpec();

            try {
                $spec->validate(['tag_name' => 'Test Tag']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for empty tag_name', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['tag_name' => '']);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing tag_name key', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate([]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for whitespace-only tag_name', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['tag_name' => '   ']);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for null tag_name', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['tag_name' => null]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for tag_name longer than 256 characters', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['tag_name' => str_repeat('a', 257)]);
            })->toThrow(ValidationFailedException::class);
        });

        test('passes validation with tag_name at maximum length', function () {
            $spec = new TagNameInputSpec();

            try {
                $spec->validate(['tag_name' => str_repeat('a', 256)]);
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
                    $spec->validate(['tag_name' => $name]);
                    expect(true)->toBeTrue();
                } catch (ValidationFailedException $e) {
                    throw $e;
                }
            }
        });

        test('validates only specified fields when provided', function () {
            $spec = new TagNameInputSpec();

            try {
                $spec->validate(['tag_name' => 'Test Tag'], ['tag_name']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws exception for unknown field in fields parameter', function () {
            $spec = new TagNameInputSpec();

            expect(function() use ($spec) {
                $spec->validate(['tag_name' => 'test'], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter and validate with valid data', function () {
            $spec = new TagNameInputSpec();

            $rawData = ['tag_name' => '  Test Tag  '];
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
                'tag_name' => 'Test Tag',
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

            $rawData = ['tag_name' => '  Important  '];
            $filtered = $spec->filter($rawData);

            expect($filtered['tag_name'])->toBe('Important');

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('validation failure after filter with invalid length', function () {
            $spec = new TagNameInputSpec();

            $rawData = ['tag_name' => str_repeat('a', 257)];
            $filtered = $spec->filter($rawData);

            expect($filtered['tag_name'])->toBe(str_repeat('a', 257));

            expect(function() use ($spec, $filtered) {
                $spec->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });

        test('handles multiple filter and validate cycles', function () {
            $spec = new TagNameInputSpec();

            $filtered1 = $spec->filter(['tag_name' => '  Important  ']);
            $spec->validate($filtered1);

            $filtered2 = $spec->filter(['tag_name' => '  Work  ']);
            $spec->validate($filtered2);

            expect($filtered1['tag_name'])->toBe('Important');
            expect($filtered2['tag_name'])->toBe('Work');
        });
    });
});
