<?php

use jschreuder\BookmarkBureau\InputSpec\LinkInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe('LinkInputSpec', function () {
    describe('getAvailableFields method', function () {
        test('returns array containing all fields', function () {
            $spec = new LinkInputSpec();
            $fields = $spec->getAvailableFields();

            expect($fields)->toContain('id');
            expect($fields)->toContain('url');
            expect($fields)->toContain('title');
            expect($fields)->toContain('description');
            expect($fields)->toContain('icon');
            expect(count($fields))->toBe(5);
        });
    });

    describe('filter method', function () {
        test('filters all fields with whitespace trimmed', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => "  {$linkId}  ",
                'url' => '  https://example.com  ',
                'title' => '  Test Link  ',
                'description' => '  A test link  ',
                'icon' => '  link  ',
            ]);

            expect($filtered['id'])->toBe($linkId);
            expect($filtered['url'])->toBe('https://example.com');
            expect($filtered['title'])->toBe('Test Link');
            expect($filtered['description'])->toBe('A test link');
            expect($filtered['icon'])->toBe('link');
        });

        test('strips HTML tags from title', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $linkId,
                'url' => 'https://example.com',
                'title' => '<script>alert("xss")</script>Test<strong>Bold</strong>',
                'description' => 'A test',
            ]);

            expect($filtered['title'])->toBe('alert("xss")TestBold');
        });

        test('strips HTML tags from description', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $linkId,
                'url' => 'https://example.com',
                'title' => 'Test',
                'description' => '<p>A test</p> <script>bad</script>description',
            ]);

            expect($filtered['description'])->toBe('A test baddescription');
        });

        test('does not strip HTML tags from url', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $linkId,
                'url' => 'https://example.com/path?query=value',
                'title' => 'Test',
                'description' => 'A test',
            ]);

            expect($filtered['url'])->toBe('https://example.com/path?query=value');
        });

        test('handles missing id key with empty string', function () {
            $spec = new LinkInputSpec();

            $filtered = $spec->filter([
                'url' => 'https://example.com',
                'title' => 'Test',
                'description' => 'A test',
            ]);

            expect($filtered['id'])->toBe('');
        });

        test('handles missing url key with empty string', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $linkId,
                'title' => 'Test',
                'description' => 'A test',
            ]);

            expect($filtered['url'])->toBe('');
        });

        test('handles missing title key with empty string', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $linkId,
                'url' => 'https://example.com',
                'description' => 'A test',
            ]);

            expect($filtered['title'])->toBe('');
        });

        test('handles missing description key with empty string', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $linkId,
                'url' => 'https://example.com',
                'title' => 'Test',
            ]);

            expect($filtered['description'])->toBe('');
        });

        test('handles missing icon key with null', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $linkId,
                'url' => 'https://example.com',
                'title' => 'Test',
                'description' => 'A test',
            ]);

            expect($filtered['icon'])->toBeNull();
        });

        test('ignores additional fields in input', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $linkId,
                'url' => 'https://example.com',
                'title' => 'Test',
                'description' => 'A test',
                'icon' => 'link',
                'extra_field' => 'ignored',
                'another_field' => 'also ignored'
            ]);

            expect($filtered)->toHaveKey('id');
            expect($filtered)->toHaveKey('url');
            expect($filtered)->toHaveKey('title');
            expect($filtered)->toHaveKey('description');
            expect($filtered)->toHaveKey('icon');
            expect($filtered)->not->toHaveKey('extra_field');
            expect($filtered)->not->toHaveKey('another_field');
        });

        test('converts null icon to null', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $linkId,
                'url' => 'https://example.com',
                'title' => 'Test',
                'description' => 'A test',
                'icon' => null,
            ]);

            expect($filtered['icon'])->toBeNull();
        });

        test('filters only specific fields when provided', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $spec->filter([
                'id' => $linkId,
                'url' => 'https://example.com',
                'title' => 'Test',
                'description' => 'A test',
                'icon' => 'link',
            ], ['id', 'url', 'title']);

            expect($filtered)->toHaveKey('id');
            expect($filtered)->toHaveKey('url');
            expect($filtered)->toHaveKey('title');
            expect($filtered)->not->toHaveKey('description');
            expect($filtered)->not->toHaveKey('icon');
            expect(count($filtered))->toBe(3);
        });

        test('throws exception for unknown field', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->filter(['id' => $linkId], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('validate method', function () {
        test('passes validation with valid data', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'id' => $linkId,
                    'url' => 'https://example.com',
                    'title' => 'Test Link',
                    'description' => 'A test link',
                    'icon' => 'link',
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('passes validation with optional description and icon as null', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'id' => $linkId,
                    'url' => 'https://example.com',
                    'title' => 'Test Link',
                    'description' => null,
                    'icon' => null,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('passes validation with empty description string', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'id' => $linkId,
                    'url' => 'https://example.com',
                    'title' => 'Test Link',
                    'description' => '',
                    'icon' => null,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('passes validation with various URL formats', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $validUrls = [
                'https://example.com',
                'http://example.com',
                'https://example.com/path',
                'https://example.com/path?query=value',
                'https://example.com:8080',
                'https://sub.example.com',
            ];

            foreach ($validUrls as $url) {
                try {
                    $spec->validate([
                        'id' => $linkId,
                        'url' => $url,
                        'title' => 'Test',
                        'description' => 'A test',
                        'icon' => null,
                    ]);
                    expect(true)->toBeTrue();
                } catch (ValidationFailedException $e) {
                    throw $e;
                }
            }
        });

        test('throws validation error for invalid id UUID', function () {
            $spec = new LinkInputSpec();

            expect(function() use ($spec) {
                $spec->validate([
                    'id' => 'not-a-uuid',
                    'url' => 'https://example.com',
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty id', function () {
            $spec = new LinkInputSpec();

            expect(function() use ($spec) {
                $spec->validate([
                    'id' => '',
                    'url' => 'https://example.com',
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing id', function () {
            $spec = new LinkInputSpec();

            expect(function() use ($spec) {
                $spec->validate([
                    'url' => 'https://example.com',
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for invalid URL format', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'id' => $linkId,
                    'url' => 'not-a-valid-url',
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty url', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'id' => $linkId,
                    'url' => '',
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing url', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'id' => $linkId,
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty title', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'id' => $linkId,
                    'url' => 'https://example.com',
                    'title' => '',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing title', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'id' => $linkId,
                    'url' => 'https://example.com',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for title longer than 256 characters', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'id' => $linkId,
                    'url' => 'https://example.com',
                    'title' => str_repeat('a', 257),
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('passes validation with title at maximum length', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            try {
                $spec->validate([
                    'id' => $linkId,
                    'url' => 'https://example.com',
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
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'id' => $linkId,
                    'url' => 'https://example.com',
                    'title' => 'Test',
                    'description' => 123,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for non-string icon', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'id' => $linkId,
                    'url' => 'https://example.com',
                    'title' => 'Test',
                    'description' => 'A test',
                    'icon' => 123,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for non-string id', function () {
            $spec = new LinkInputSpec();

            expect(function() use ($spec) {
                $spec->validate([
                    'id' => 12345,
                    'url' => 'https://example.com',
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for non-string url', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate([
                    'id' => $linkId,
                    'url' => 12345,
                    'title' => 'Test',
                    'description' => 'A test',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('validates only specified fields when provided', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            try {
                $spec->validate(['id' => $linkId], ['id']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws exception for unknown field in fields parameter', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($spec, $linkId) {
                $spec->validate(['id' => $linkId], ['unknown_field']);
            })->toThrow(InvalidArgumentException::class);
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter and validate with valid data', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $rawData = [
                'id' => "  {$linkId}  ",
                'url' => '  https://example.com  ',
                'title' => '  Test Link  ',
                'description' => '  A test link  ',
                'icon' => '  link  ',
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
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $rawData = [
                'id' => $linkId,
                'url' => 'https://example.com',
                'title' => 'Test',
                'description' => 'A test',
                'icon' => 'link',
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
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $rawData = [
                'id' => $linkId,
                'url' => 'https://example.com',
                'title' => '<script>Test</script>Link',
                'description' => '<p>A test</p> link',
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
            $spec = new LinkInputSpec();

            $rawData = [
                'id' => 'invalid-uuid',
                'url' => 'https://example.com',
                'title' => 'Test',
                'description' => 'A test'
            ];
            $filtered = $spec->filter($rawData);

            expect($filtered['id'])->toBe('invalid-uuid');

            expect(function() use ($spec, $filtered) {
                $spec->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });

        test('validation failure after filter with invalid URL', function () {
            $spec = new LinkInputSpec();
            $linkId = Uuid::uuid4()->toString();

            $rawData = [
                'id' => $linkId,
                'url' => 'not-a-url',
                'title' => 'Test',
                'description' => 'A test'
            ];
            $filtered = $spec->filter($rawData);

            expect($filtered['url'])->toBe('not-a-url');

            expect(function() use ($spec, $filtered) {
                $spec->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });

        test('handles multiple filter and validate cycles', function () {
            $spec = new LinkInputSpec();
            $linkId1 = Uuid::uuid4()->toString();
            $linkId2 = Uuid::uuid4()->toString();

            $filtered1 = $spec->filter([
                'id' => "  {$linkId1}  ",
                'url' => '  https://example.com  ',
                'title' => 'Link 1',
                'description' => 'First link',
            ]);
            $spec->validate($filtered1);

            $filtered2 = $spec->filter([
                'id' => "  {$linkId2}  ",
                'url' => '  https://other.com  ',
                'title' => 'Link 2',
                'description' => 'Second link',
            ]);
            $spec->validate($filtered2);

            expect($filtered1['id'])->toBe($linkId1);
            expect($filtered2['id'])->toBe($linkId2);
        });
    });
});
