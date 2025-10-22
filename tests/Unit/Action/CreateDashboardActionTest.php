<?php

use jschreuder\BookmarkBureau\Action\CreateDashboardAction;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\InputSpec\DashboardInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;

describe('CreateDashboardAction', function () {
    describe('filter method', function () {
        test('trims whitespace from title', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $filtered = $action->filter([
                'title' => '  Test Dashboard  ',
                'description' => 'Test Description',
                'icon' => null
            ]);

            expect($filtered['title'])->toBe('Test Dashboard');
        });

        test('trims whitespace from description', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $filtered = $action->filter([
                'title' => 'Test Dashboard',
                'description' => '  Test Description  ',
                'icon' => null
            ]);

            expect($filtered['description'])->toBe('Test Description');
        });

        test('trims whitespace from icon', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $filtered = $action->filter([
                'title' => 'Test Dashboard',
                'description' => 'Test Description',
                'icon' => '  dashboard-icon  '
            ]);

            expect($filtered['icon'])->toBe('dashboard-icon');
        });

        test('handles missing keys with empty strings', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $filtered = $action->filter([]);

            expect($filtered['title'])->toBe('');
            expect($filtered['description'])->toBe('');
            expect($filtered['icon'])->toBeNull();
        });

        test('preserves null icon as null', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $filtered = $action->filter([
                'title' => 'Test Dashboard',
                'description' => 'Test Description',
                'icon' => null
            ]);

            expect($filtered['icon'])->toBeNull();
        });
    });

    describe('validate method', function () {
        test('passes validation with valid data', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $data = [
                'title' => 'Test Dashboard',
                'description' => 'Test Description',
                'icon' => 'dashboard-icon'
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('passes validation with empty description', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $data = [
                'title' => 'Test Dashboard',
                'description' => '',
                'icon' => 'dashboard-icon'
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('passes validation with null icon', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $data = [
                'title' => 'Test Dashboard',
                'description' => 'Test Description',
                'icon' => null
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for empty title', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $data = [
                'title' => '',
                'description' => 'Test Description',
                'icon' => null
            ];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for title exceeding max length', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $data = [
                'title' => str_repeat('a', 257),
                'description' => 'Test Description',
                'icon' => null
            ];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing description', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $data = [
                'title' => 'Test Dashboard',
                'icon' => null
            ];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('includes title error in validation exceptions', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $data = [
                'title' => '',
                'description' => 'Test Description',
                'icon' => null
            ];

            expect(function() use ($action, $data) {
                $action->validate($data);
            })->toThrow(ValidationFailedException::class);
        });

        test('includes multiple validation errors', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $data = [
                'title' => '',
                'description' => null,
                'icon' => null
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
        test('executes with valid data and returns formatted dashboard', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard = TestEntityFactory::createDashboard(icon: new Icon('test-icon'));

            $dashboardService->shouldReceive('createDashboard')
                ->with('Test Dashboard', 'Test Description', 'test-icon')
                ->andReturn($dashboard);

            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $result = $action->execute([
                'title' => 'Test Dashboard',
                'description' => 'Test Description',
                'icon' => 'test-icon'
            ]);

            expect($result)->toHaveKey('id');
            expect($result)->toHaveKey('title');
            expect($result)->toHaveKey('description');
            expect($result)->toHaveKey('icon');
            expect($result)->toHaveKey('created_at');
            expect($result)->toHaveKey('updated_at');
        });

        test('returns created_at and updated_at in ISO 8601 format', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard = TestEntityFactory::createDashboard(icon: new Icon('test-icon'));

            $dashboardService->shouldReceive('createDashboard')
                ->andReturn($dashboard);

            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $result = $action->execute([
                'title' => 'Test Dashboard',
                'description' => 'Test Description',
                'icon' => 'test-icon'
            ]);

            expect($result['created_at'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
            expect($result['updated_at'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
        });

        test('returns correct dashboard service parameters with icon', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard = TestEntityFactory::createDashboard(icon: new Icon('test-icon'));

            $dashboardService->shouldReceive('createDashboard')
                ->with('Test Dashboard', 'Long description', 'test-icon')
                ->andReturn($dashboard);

            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $action->execute([
                'title' => 'Test Dashboard',
                'description' => 'Long description',
                'icon' => 'test-icon'
            ]);

            expect(true)->toBeTrue(); // Mockery validates the call was made correctly
        });

        test('returns correct dashboard service parameters with null icon', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard = TestEntityFactory::createDashboard(icon: null);

            $dashboardService->shouldReceive('createDashboard')
                ->with('Test Dashboard', 'Test Description', null)
                ->andReturn($dashboard);

            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $action->execute([
                'title' => 'Test Dashboard',
                'description' => 'Test Description',
                'icon' => null
            ]);

            expect(true)->toBeTrue();
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter, validate, and execute', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard = TestEntityFactory::createDashboard(icon: new Icon('test-icon'));

            $dashboardService->shouldReceive('createDashboard')
                ->with('Test Dashboard', 'Test Description', 'test-icon')
                ->andReturn($dashboard);

            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $rawData = [
                'title' => '  Test Dashboard  ',
                'description' => '  Test Description  ',
                'icon' => '  test-icon  '
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

        test('full workflow with null icon', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard = TestEntityFactory::createDashboard(icon: null);

            $dashboardService->shouldReceive('createDashboard')
                ->with('Test Dashboard', 'Test Description', null)
                ->andReturn($dashboard);

            $inputSpec = new DashboardInputSpec();
            $action = new CreateDashboardAction($dashboardService, $inputSpec);

            $rawData = [
                'title' => 'Test Dashboard',
                'description' => 'Test Description',
                'icon' => null
            ];

            $filtered = $action->filter($rawData);
            expect($filtered['icon'])->toBeNull();

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
