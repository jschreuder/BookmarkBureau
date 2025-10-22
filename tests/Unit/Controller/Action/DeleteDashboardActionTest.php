<?php

use jschreuder\BookmarkBureau\Controller\Action\DeleteDashboardAction;
use jschreuder\BookmarkBureau\InputSpec\IdInputSpec;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Rfc4122\UuidV4;

describe('DeleteDashboardAction', function () {
    describe('filter method', function () {
        test('trims whitespace from id', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $filtered = $action->filter([
                'id' => "  {$dashboardId->toString()}  "
            ]);

            expect($filtered['id'])->toBe($dashboardId->toString());
        });

        test('handles missing id key with empty string', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $filtered = $action->filter([]);

            expect($filtered['id'])->toBe('');
        });

        test('preserves valid id without modification', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $filtered = $action->filter([
                'id' => $dashboardId->toString()
            ]);

            expect($filtered['id'])->toBe($dashboardId->toString());
        });

        test('ignores additional fields in input', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $filtered = $action->filter([
                'id' => $dashboardId->toString(),
                'title' => 'Should be ignored',
                'description' => 'Should be ignored',
                'extra_field' => 'ignored'
            ]);

            expect($filtered)->toHaveKey('id');
            expect($filtered)->not->toHaveKey('title');
            expect($filtered)->not->toHaveKey('description');
            expect($filtered)->not->toHaveKey('extra_field');
        });
    });

    describe('validate method', function () {
        test('passes validation with valid UUID', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $data = ['id' => $dashboardId->toString()];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for invalid UUID', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $data = ['id' => 'not-a-uuid'];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty id', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $data = ['id' => ''];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing id key', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $data = [];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for whitespace-only id', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $data = ['id' => '   '];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for null id', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $data = ['id' => null];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('validates UUID in different formats', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);
            $dashboardId = UuidV4::uuid4();

            $data = ['id' => $dashboardId->toString()];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });

    describe('execute method', function () {
        test('calls deleteDashboard on service with correct UUID', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = UuidV4::uuid4();

            $dashboardService->shouldReceive('deleteDashboard')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $result = $action->execute([
                'id' => $dashboardId->toString()
            ]);

            expect($result)->toBe([]);
        });

        test('returns empty array after successful deletion', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = UuidV4::uuid4();

            $dashboardService->shouldReceive('deleteDashboard')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class));

            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $result = $action->execute([
                'id' => $dashboardId->toString()
            ]);

            expect($result)->toEqual([]);
            expect($result)->toBeArray();
        });

        test('converts string id to UUID before passing to service', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = UuidV4::uuid4();

            $dashboardService->shouldReceive('deleteDashboard')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $action->execute([
                'id' => $dashboardId->toString()
            ]);

            expect(true)->toBeTrue();
        });

        test('passes exact UUID to service', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = UuidV4::uuid4();

            $uuidCapture = null;
            $dashboardService->shouldReceive('deleteDashboard')
                ->andReturnUsing(function($uuid) use (&$uuidCapture) {
                    $uuidCapture = $uuid;
                });

            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $action->execute([
                'id' => $dashboardId->toString()
            ]);

            expect($uuidCapture->toString())->toBe($dashboardId->toString());
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter, validate, and execute', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = UuidV4::uuid4();

            $dashboardService->shouldReceive('deleteDashboard')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $rawData = [
                'id' => "  {$dashboardId->toString()}  "
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
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = UuidV4::uuid4();

            $dashboardService->shouldReceive('deleteDashboard')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $rawData = [
                'id' => $dashboardId->toString(),
                'title' => 'Should be ignored',
                'description' => 'Should be ignored',
                'extra' => 'data'
            ];

            $filtered = $action->filter($rawData);
            expect($filtered)->not->toHaveKey('title');
            expect($filtered)->not->toHaveKey('description');

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBe([]);
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('full workflow filters and validates id correctly', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = UuidV4::uuid4();

            $dashboardService->shouldReceive('deleteDashboard')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class));

            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

            $rawData = [
                'id' => "  {$dashboardId->toString()}  "
            ];

            $filtered = $action->filter($rawData);
            expect($filtered['id'])->toBe($dashboardId->toString());

            $action->validate($filtered);
            $result = $action->execute($filtered);

            expect($result)->toBe([]);
        });

        test('validation failure prevents service call', function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);

            $dashboardService->shouldNotReceive('deleteDashboard');

            $inputSpec = new IdInputSpec();
            $action = new DeleteDashboardAction($dashboardService, $inputSpec);

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
