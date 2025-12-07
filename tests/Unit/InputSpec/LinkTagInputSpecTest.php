<?php

use jschreuder\BookmarkBureau\InputSpec\LinkTagInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe('LinkTagInputSpec', function () {
    describe('getAvailableFields method', function () {
        test('returns array containing all fields', function () {
            $spec = new LinkTagInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toContain('link_id');
            expect($fields)->toContain('tag_name');
            expect(count($fields))->toBe(2);
        });
    });

    describe('filter method', function () {
        test('filters all fields with whitespace trimmed', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'link_id' => "  {$uuid}  ",
                'tag_name' => '  Test Tag  ',
            ]);

            expect($filtered['link_id'])->toBe($uuid);
            expect($filtered['tag_name'])->toBe('Test Tag');
        });

        test('handles missing id key with empty string', function () {
            $spec = new LinkTagInputSpec();

            $filtered = $spec->filter([
                'tag_name' => 'Test Tag',
            ]);

            expect($filtered['link_id'])->toBe('');
        });

        test('handles missing tag_name key with empty string', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'link_id' => $uuid,
            ]);

            expect($filtered['tag_name'])->toBe('');
        });

        test('ignores additional fields in input', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'link_id' => $uuid,
                'tag_name' => 'Test Tag',
                'extra_field' => 'ignored',
            ]);

            expect($filtered)->toHaveKey('link_id');
            expect($filtered)->toHaveKey('tag_name');
            expect($filtered)->not->toHaveKey('extra_field');
        });

        test('filters only specific fields when provided', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'link_id' => $uuid,
                'tag_name' => 'Test Tag',
            ], ['link_id']);

            expect($filtered)->toHaveKey('link_id');
            expect($filtered)->not->toHaveKey('tag_name');
            expect(count($filtered))->toBe(1);
        });

        test('throws exception for unknown field', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            expect(function() use ($spec, $uuid) {
                $spec->filter(['link_id' => $uuid], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('validate method', function () {
        test('passes validation with valid data', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'link_id' => $uuid,
                    'tag_name' => 'Test Tag',
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for invalid id UUID', function () {
            $spec = new LinkTagInputSpec();

            expect(function() use ($spec) {
                $spec->validate([
                    'link_id' => 'not-a-uuid',
                    'tag_name' => 'Test Tag',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty id', function () {
            $spec = new LinkTagInputSpec();

            expect(function() use ($spec) {
                $spec->validate([
                    'link_id' => '',
                    'tag_name' => 'Test Tag',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing id', function () {
            $spec = new LinkTagInputSpec();

            expect(function() use ($spec) {
                $spec->validate([
                    'tag_name' => 'Test Tag',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty tag_name', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            expect(function() use ($spec, $uuid) {
                $spec->validate([
                    'link_id' => $uuid,
                    'tag_name' => '',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing tag_name', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            expect(function() use ($spec, $uuid) {
                $spec->validate([
                    'link_id' => $uuid,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for tag_name longer than 256 characters', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            expect(function() use ($spec, $uuid) {
                $spec->validate([
                    'link_id' => $uuid,
                    'tag_name' => str_repeat('a', 257),
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('passes validation with tag_name at maximum length', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'link_id' => $uuid,
                    'tag_name' => str_repeat('a', 256),
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('validates only specified fields when provided', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            try {
                $spec->validate(['link_id' => $uuid], ['link_id']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws exception for unknown field in fields parameter', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            expect(function() use ($spec, $uuid) {
                $spec->validate(['link_id' => $uuid], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter and validate with valid data', function () {
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $rawData = [
                'link_id' => "  {$uuid}  ",
                'tag_name' => '  Test Tag  ',
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
            $spec = new LinkTagInputSpec();
            $uuid = Uuid::uuid4()->toString();

            $rawData = [
                'link_id' => $uuid,
                'tag_name' => 'Test Tag',
                'extra_field' => 'ignored',
            ];

            $filtered = $spec->filter($rawData);

            expect($filtered)->not->toHaveKey('extra_field');

            try {
                $spec->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('validation failure after filter with invalid UUID', function () {
            $spec = new LinkTagInputSpec();

            $rawData = [
                'link_id' => 'invalid-uuid',
                'tag_name' => 'Test Tag'
            ];
            $filtered = $spec->filter($rawData);

            expect($filtered['link_id'])->toBe('invalid-uuid');

            expect(function() use ($spec, $filtered) {
                $spec->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });

        test('handles multiple filter and validate cycles', function () {
            $spec = new LinkTagInputSpec();
            $uuid1 = Uuid::uuid4()->toString();
            $uuid2 = Uuid::uuid4()->toString();

            $filtered1 = $spec->filter([
                'link_id' => "  {$uuid1}  ",
                'tag_name' => '  Important  ',
            ]);
            $spec->validate($filtered1);

            $filtered2 = $spec->filter([
                'link_id' => "  {$uuid2}  ",
                'tag_name' => '  Work  ',
            ]);
            $spec->validate($filtered2);

            expect($filtered1['link_id'])->toBe($uuid1);
            expect($filtered2['link_id'])->toBe($uuid2);
        });
    });
});
