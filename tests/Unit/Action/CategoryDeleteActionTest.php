<?php

use jschreuder\BookmarkBureau\Action\CategoryDeleteAction;
use jschreuder\BookmarkBureau\InputSpec\IdInputSpec;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Rfc4122\UuidV4;

describe('CategoryDeleteAction', function () {
    describe('filter method', function () {
        test('trims whitespace from id', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);
            $categoryId = UuidV4::uuid4();

            $filtered = $action->filter([
                'id' => "  {$categoryId->toString()}  "
            ]);

            expect($filtered['id'])->toBe($categoryId->toString());
        });

        test('handles missing id key with empty string', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $filtered = $action->filter([]);

            expect($filtered['id'])->toBe('');
        });

        test('preserves valid id without modification', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);
            $categoryId = UuidV4::uuid4();

            $filtered = $action->filter([
                'id' => $categoryId->toString()
            ]);

            expect($filtered['id'])->toBe($categoryId->toString());
        });

        test('ignores additional fields in input', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);
            $categoryId = UuidV4::uuid4();

            $filtered = $action->filter([
                'id' => $categoryId->toString(),
                'title' => 'Should be ignored',
                'color' => 'Should be ignored',
                'extra_field' => 'ignored'
            ]);

            expect($filtered)->toHaveKey('id');
            expect($filtered)->not->toHaveKey('title');
            expect($filtered)->not->toHaveKey('color');
            expect($filtered)->not->toHaveKey('extra_field');
        });
    });

    describe('validate method', function () {
        test('passes validation with valid UUID', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);
            $categoryId = UuidV4::uuid4();

            $data = ['id' => $categoryId->toString()];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for invalid UUID', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $data = ['id' => 'not-a-uuid'];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty id', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $data = ['id' => ''];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing id key', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $data = [];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for whitespace-only id', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $data = ['id' => '   '];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for null id', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $data = ['id' => null];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('validates UUID in different formats', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);
            $categoryId = UuidV4::uuid4();

            $data = ['id' => $categoryId->toString()];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });

    describe('execute method', function () {
        test('calls deleteCategory on service with correct UUID', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = UuidV4::uuid4();

            $categoryService->shouldReceive('deleteCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $result = $action->execute([
                'id' => $categoryId->toString()
            ]);

            expect($result)->toBe([]);
        });

        test('returns empty array after successful deletion', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = UuidV4::uuid4();

            $categoryService->shouldReceive('deleteCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class));

            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $result = $action->execute([
                'id' => $categoryId->toString()
            ]);

            expect($result)->toEqual([]);
            expect($result)->toBeArray();
        });

        test('converts string id to UUID before passing to service', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = UuidV4::uuid4();

            $categoryService->shouldReceive('deleteCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $action->execute([
                'id' => $categoryId->toString()
            ]);

            expect(true)->toBeTrue();
        });

        test('passes exact UUID to service', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = UuidV4::uuid4();

            $uuidCapture = null;
            $categoryService->shouldReceive('deleteCategory')
                ->andReturnUsing(function($uuid) use (&$uuidCapture) {
                    $uuidCapture = $uuid;
                });

            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $action->execute([
                'id' => $categoryId->toString()
            ]);

            expect($uuidCapture->toString())->toBe($categoryId->toString());
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter, validate, and execute', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = UuidV4::uuid4();

            $categoryService->shouldReceive('deleteCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $rawData = [
                'id' => "  {$categoryId->toString()}  "
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBe([]);
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('full workflow with extra fields in input', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = UuidV4::uuid4();

            $categoryService->shouldReceive('deleteCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $rawData = [
                'id' => $categoryId->toString(),
                'title' => 'Should be ignored',
                'color' => 'Should be ignored',
                'extra' => 'data'
            ];

            $filtered = $action->filter($rawData);
            expect($filtered)->not->toHaveKey('title');
            expect($filtered)->not->toHaveKey('color');

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBe([]);
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('full workflow filters and validates id correctly', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = UuidV4::uuid4();

            $categoryService->shouldReceive('deleteCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class));

            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $rawData = [
                'id' => "  {$categoryId->toString()}  "
            ];

            $filtered = $action->filter($rawData);
            expect($filtered['id'])->toBe($categoryId->toString());

            $action->validate($filtered);
            $result = $action->execute($filtered);

            expect($result)->toBe([]);
        });

        test('validation failure prevents service call', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);

            $categoryService->shouldNotReceive('deleteCategory');

            $inputSpec = new IdInputSpec();
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $rawData = [
                'id' => 'invalid-uuid'
            ];

            $filtered = $action->filter($rawData);

            expect(function() use ($action, $filtered) {
                $action->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });
    });
});
