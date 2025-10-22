<?php

use jschreuder\BookmarkBureau\Action\CreateCategoryAction;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\InputSpec\CategoryInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Rfc4122\UuidV4;

describe('CreateCategoryAction', function () {
    describe('filter method', function () {
        test('trims whitespace from dashboard_id', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $filtered = $action->filter([
                'dashboard_id' => "  {$dashboardId->toString()}  ",
                'title' => 'Test Category',
                'color' => null,
                'sort_order' => 1
            ]);

            expect($filtered['dashboard_id'])->toBe($dashboardId->toString());
        });

        test('trims whitespace from title', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $filtered = $action->filter([
                'dashboard_id' => $dashboardId->toString(),
                'title' => '  Test Category  ',
                'color' => null,
                'sort_order' => 1
            ]);

            expect($filtered['title'])->toBe('Test Category');
        });

        test('trims whitespace from color', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $filtered = $action->filter([
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => '  #FF0000  ',
                'sort_order' => 1
            ]);

            expect($filtered['color'])->toBe('#FF0000');
        });

        test('handles missing keys with appropriate defaults', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);

            $filtered = $action->filter([]);

            expect($filtered['dashboard_id'])->toBe('');
            expect($filtered['title'])->toBe('');
            expect($filtered['color'])->toBeNull();
            expect($filtered['sort_order'])->toBe(1);
        });

        test('preserves null color as null', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $filtered = $action->filter([
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => null,
                'sort_order' => 1
            ]);

            expect($filtered['color'])->toBeNull();
        });

        test('excludes id field from filter', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $filtered = $action->filter([
                'id' => UuidV4::uuid4()->toString(),
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => null,
                'sort_order' => 1
            ]);

            expect($filtered)->not->toHaveKey('id');
        });
    });

    describe('validate method', function () {
        test('passes validation with valid data', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $data = [
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => '#FF0000',
                'sort_order' => 1
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('passes validation with null color', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $data = [
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => null,
                'sort_order' => 1
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for empty title', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $data = [
                'dashboard_id' => $dashboardId->toString(),
                'title' => '',
                'color' => null,
                'sort_order' => 1
            ];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for title exceeding max length', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $data = [
                'dashboard_id' => $dashboardId->toString(),
                'title' => str_repeat('a', 257),
                'color' => null,
                'sort_order' => 1
            ];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for invalid dashboard_id', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);

            $data = [
                'dashboard_id' => 'not-a-uuid',
                'title' => 'Test Category',
                'color' => null,
                'sort_order' => 1
            ];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for invalid color format', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $data = [
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => 'invalid-color',
                'sort_order' => 1
            ];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('includes title error in validation exceptions', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $data = [
                'dashboard_id' => $dashboardId->toString(),
                'title' => '',
                'color' => null,
                'sort_order' => 1
            ];

            expect(function() use ($action, $data) {
                $action->validate($data);
            })->toThrow(ValidationFailedException::class);
        });

        test('includes multiple validation errors', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);

            $data = [
                'dashboard_id' => 'invalid',
                'title' => '',
                'color' => null,
                'sort_order' => 1
            ];

            try {
                $action->validate($data);
                expect(true)->toBeFalse();
            } catch (ValidationFailedException $e) {
                $errors = $e->getValidationErrors();
                expect($errors)->toHaveKey('title');
            }
        });
    });

    describe('execute method', function () {
        test('executes with valid data and returns formatted category', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $dashboardId = UuidV4::uuid4();
            $category = TestEntityFactory::createCategory(
                dashboard: TestEntityFactory::createDashboard(id: $dashboardId),
                color: new HexColor('#FF0000')
            );

            $categoryService->shouldReceive('createCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class), 'Test Category', '#FF0000')
                ->andReturn($category);

            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);

            $result = $action->execute([
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => '#FF0000',
                'sort_order' => 1
            ]);

            expect($result)->toHaveKey('id');
            expect($result)->toHaveKey('dashboard_id');
            expect($result)->toHaveKey('title');
            expect($result)->toHaveKey('color');
            expect($result)->toHaveKey('sort_order');
            expect($result)->toHaveKey('created_at');
            expect($result)->toHaveKey('updated_at');
        });

        test('returns created_at and updated_at in ISO 8601 format', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $dashboardId = UuidV4::uuid4();
            $category = TestEntityFactory::createCategory(
                dashboard: TestEntityFactory::createDashboard(id: $dashboardId),
                color: new HexColor('#FF0000')
            );

            $categoryService->shouldReceive('createCategory')
                ->andReturn($category);

            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);

            $result = $action->execute([
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => '#FF0000',
                'sort_order' => 1
            ]);

            expect($result['created_at'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
            expect($result['updated_at'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
        });

        test('returns correct category service parameters with color', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $dashboardId = UuidV4::uuid4();
            $category = TestEntityFactory::createCategory(
                dashboard: TestEntityFactory::createDashboard(id: $dashboardId),
                color: new HexColor('#FF0000')
            );

            $categoryService->shouldReceive('createCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class), 'Test Category', '#FF0000')
                ->andReturn($category);

            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);

            $action->execute([
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => '#FF0000',
                'sort_order' => 1
            ]);

            expect(true)->toBeTrue(); // Mockery validates the call was made correctly
        });

        test('returns correct category service parameters with null color', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $dashboardId = UuidV4::uuid4();
            $category = TestEntityFactory::createCategory(
                dashboard: TestEntityFactory::createDashboard(id: $dashboardId),
                color: null
            );

            $categoryService->shouldReceive('createCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class), 'Test Category', null)
                ->andReturn($category);

            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);

            $action->execute([
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => null,
                'sort_order' => 1
            ]);

            expect(true)->toBeTrue();
        });

        test('converts string dashboard_id to UUID before passing to service', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $dashboardId = UuidV4::uuid4();
            $category = TestEntityFactory::createCategory(
                dashboard: TestEntityFactory::createDashboard(id: $dashboardId)
            );

            $categoryService->shouldReceive('createCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class), \Mockery::any(), \Mockery::any())
                ->andReturn($category);

            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);

            $action->execute([
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => null,
                'sort_order' => 1
            ]);

            expect(true)->toBeTrue();
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter, validate, and execute', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $dashboardId = UuidV4::uuid4();
            $category = TestEntityFactory::createCategory(
                dashboard: TestEntityFactory::createDashboard(id: $dashboardId),
                color: new HexColor('#FF0000')
            );

            $categoryService->shouldReceive('createCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class), 'Test Category', '#FF0000')
                ->andReturn($category);

            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);

            $rawData = [
                'dashboard_id' => "  {$dashboardId->toString()}  ",
                'title' => '  Test Category  ',
                'color' => '  #FF0000  ',
                'sort_order' => 1
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toHaveKey('id');
                expect($result)->toHaveKey('title');
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('full workflow with null color', function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $dashboardId = UuidV4::uuid4();
            $category = TestEntityFactory::createCategory(
                dashboard: TestEntityFactory::createDashboard(id: $dashboardId),
                color: null
            );

            $categoryService->shouldReceive('createCategory')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class), 'Test Category', null)
                ->andReturn($category);

            $inputSpec = new CategoryInputSpec();
            $action = new CreateCategoryAction($categoryService, $inputSpec);

            $rawData = [
                'dashboard_id' => $dashboardId->toString(),
                'title' => 'Test Category',
                'color' => null,
                'sort_order' => 1
            ];

            $filtered = $action->filter($rawData);
            expect($filtered['color'])->toBeNull();

            try {
                $action->validate($filtered);
                $action->execute($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });
});
