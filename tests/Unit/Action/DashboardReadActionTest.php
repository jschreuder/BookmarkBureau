<?php

use jschreuder\BookmarkBureau\Action\DashboardReadAction;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\InputSpec\IdInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\DashboardOutputSpec;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

describe("DashboardReadAction", function () {
    describe("filter method", function () {
        test("trims whitespace from id", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );
            $dashboardId = Uuid::uuid4();

            $filtered = $action->filter([
                "id" => "  {$dashboardId->toString()}  ",
            ]);

            expect($filtered["id"])->toBe($dashboardId->toString());
        });

        test("handles missing id key with empty string", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([]);

            expect($filtered["id"])->toBe("");
        });

        test("preserves valid id without modification", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );
            $dashboardId = Uuid::uuid4();

            $filtered = $action->filter([
                "id" => $dashboardId->toString(),
            ]);

            expect($filtered["id"])->toBe($dashboardId->toString());
        });

        test("ignores additional fields in input", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );
            $dashboardId = Uuid::uuid4();

            $filtered = $action->filter([
                "id" => $dashboardId->toString(),
                "title" => "Should be ignored",
                "description" => "Also ignored",
                "extra_field" => "ignored",
            ]);

            expect($filtered)->toHaveKey("id");
            expect($filtered)->not->toHaveKey("title");
            expect($filtered)->not->toHaveKey("description");
            expect($filtered)->not->toHaveKey("extra_field");
        });
    });

    describe("validate method", function () {
        test("passes validation with valid UUID", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );
            $dashboardId = Uuid::uuid4();

            $data = ["id" => $dashboardId->toString()];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for invalid UUID", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );

            $data = ["id" => "not-a-uuid"];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for empty id", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );

            $data = ["id" => ""];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for missing id key", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );

            $data = [];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });
    });

    describe("execute method", function () {
        test("calls getDashboard on service with correct UUID", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $dashboardService
                ->shouldReceive("getDashboard")
                ->with(Mockery::type(UuidInterface::class))
                ->once()
                ->andReturn($dashboard);

            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "id" => $dashboardId->toString(),
            ]);

            expect($result)->toBeArray();
            expect($result)->toHaveKey("id");
        });

        test("returns transformed dashboard data", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $dashboardService
                ->shouldReceive("getDashboard")
                ->with(Mockery::type(UuidInterface::class))
                ->andReturn($dashboard);

            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "id" => $dashboardId->toString(),
            ]);

            expect($result)->toBeArray();
            expect($result)->toHaveKey("id");
            expect($result)->toHaveKey("title");
            expect($result)->toHaveKey("description");
        });

        test(
            "converts string id to UUID before passing to service",
            function () {
                $dashboardService = Mockery::mock(
                    DashboardServiceInterface::class,
                );
                $dashboardId = Uuid::uuid4();
                $dashboard = TestEntityFactory::createDashboard(
                    id: $dashboardId,
                );

                $dashboardService
                    ->shouldReceive("getDashboard")
                    ->with(Mockery::type(UuidInterface::class))
                    ->once()
                    ->andReturn($dashboard);

                $inputSpec = new IdInputSpec();
                $outputSpec = new DashboardOutputSpec();
                $action = new DashboardReadAction(
                    $dashboardService,
                    $inputSpec,
                    $outputSpec,
                );

                $action->execute([
                    "id" => $dashboardId->toString(),
                ]);

                expect(true)->toBeTrue();
            },
        );

        test("returns correct dashboard data structure", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(
                id: $dashboardId,
                title: new Title("Test Dashboard"),
                description: "Test Description",
                icon: new Icon("test-icon"),
            );

            $dashboardService
                ->shouldReceive("getDashboard")
                ->with(Mockery::type(UuidInterface::class))
                ->andReturn($dashboard);

            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "id" => $dashboardId->toString(),
            ]);

            expect($result["id"])->toBe($dashboardId->toString());
            expect($result["title"])->toBe("Test Dashboard");
            expect($result["description"])->toBe("Test Description");
            expect($result["icon"])->toBe("test-icon");
        });

        test("includes timestamps in ISO 8601 format", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $dashboardService
                ->shouldReceive("getDashboard")
                ->andReturn($dashboard);

            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "id" => $dashboardId->toString(),
            ]);

            expect($result["created_at"])->toMatch(
                "/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/",
            );
            expect($result["updated_at"])->toMatch(
                "/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/",
            );
        });
    });

    describe("integration scenarios", function () {
        test("full workflow: filter, validate, and execute", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $dashboardService
                ->shouldReceive("getDashboard")
                ->with(Mockery::type(UuidInterface::class))
                ->once()
                ->andReturn($dashboard);

            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "id" => "  {$dashboardId->toString()}  ",
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBeArray();
                expect($result["id"])->toBe($dashboardId->toString());
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("full workflow with extra fields in input", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $dashboardService
                ->shouldReceive("getDashboard")
                ->with(Mockery::type(UuidInterface::class))
                ->once()
                ->andReturn($dashboard);

            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "id" => $dashboardId->toString(),
                "title" => "Should be ignored",
                "description" => "Also ignored",
                "extra" => "data",
            ];

            $filtered = $action->filter($rawData);
            expect($filtered)->not->toHaveKey("title");
            expect($filtered)->not->toHaveKey("description");

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBeArray();
                expect($result["id"])->toBe($dashboardId->toString());
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("validation failure prevents service call", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);

            $dashboardService->shouldNotReceive("getDashboard");

            $inputSpec = new IdInputSpec();
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardReadAction(
                $dashboardService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "id" => "invalid-uuid",
            ];

            $filtered = $action->filter($rawData);

            expect(function () use ($action, $filtered) {
                $action->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });
    });
});
