<?php

use jschreuder\BookmarkBureau\Action\DashboardListAction;
use jschreuder\BookmarkBureau\Composite\DashboardCollection;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\OutputSpec\DashboardOutputSpec;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;

describe("DashboardListAction", function () {
    describe("filter method", function () {
        test("returns empty array for list operation", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardListAction($dashboardService, $outputSpec);

            $filtered = $action->filter([]);

            expect($filtered)->toBe([]);
        });

        test("ignores input data for list operation", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardListAction($dashboardService, $outputSpec);

            $filtered = $action->filter([
                "id" => "some-id",
                "title" => "some title",
                "extra" => "data",
            ]);

            expect($filtered)->toBe([]);
        });
    });

    describe("validate method", function () {
        test("always validates without errors for list operation", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardListAction($dashboardService, $outputSpec);

            try {
                $action->validate([]);
                expect(true)->toBeTrue();
            } catch (Exception $e) {
                throw $e;
            }
        });

        test("validates with arbitrary input data", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardListAction($dashboardService, $outputSpec);

            try {
                $action->validate(["anything" => "ignored"]);
                expect(true)->toBeTrue();
            } catch (Exception $e) {
                throw $e;
            }
        });
    });

    describe("execute method", function () {
        test("calls getAllDashboards on service", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $collection = new DashboardCollection();

            $dashboardService
                ->shouldReceive("getAllDashboards")
                ->once()
                ->andReturn($collection);

            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardListAction($dashboardService, $outputSpec);

            $result = $action->execute([]);

            expect($result)->toBeArray();
        });

        test(
            "returns empty dashboards array for empty collection",
            function () {
                $dashboardService = Mockery::mock(
                    DashboardServiceInterface::class,
                );
                $collection = new DashboardCollection();

                $dashboardService
                    ->shouldReceive("getAllDashboards")
                    ->andReturn($collection);

                $outputSpec = new DashboardOutputSpec();
                $action = new DashboardListAction(
                    $dashboardService,
                    $outputSpec,
                );

                $result = $action->execute([]);

                expect($result)->toHaveKey("dashboards");
                expect($result["dashboards"])->toBe([]);
            },
        );

        test("transforms single dashboard", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard = TestEntityFactory::createDashboard();
            $collection = new DashboardCollection($dashboard);

            $dashboardService
                ->shouldReceive("getAllDashboards")
                ->andReturn($collection);

            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardListAction($dashboardService, $outputSpec);

            $result = $action->execute([]);

            expect($result)->toHaveKey("dashboards");
            expect($result["dashboards"])->toHaveCount(1);
            expect($result["dashboards"][0])->toHaveKey("id");
            expect($result["dashboards"][0])->toHaveKey("title");
            expect($result["dashboards"][0])->toHaveKey("description");
            expect($result["dashboards"][0])->toHaveKey("created_at");
            expect($result["dashboards"][0])->toHaveKey("updated_at");
        });

        test("transforms multiple dashboards", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard1 = TestEntityFactory::createDashboard(
                title: new Title("Dashboard 1"),
            );
            $dashboard2 = TestEntityFactory::createDashboard(
                title: new Title("Dashboard 2"),
            );
            $dashboard3 = TestEntityFactory::createDashboard(
                title: new Title("Dashboard 3"),
            );
            $collection = new DashboardCollection(
                $dashboard1,
                $dashboard2,
                $dashboard3,
            );

            $dashboardService
                ->shouldReceive("getAllDashboards")
                ->andReturn($collection);

            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardListAction($dashboardService, $outputSpec);

            $result = $action->execute([]);

            expect($result["dashboards"])->toHaveCount(3);
            expect($result["dashboards"][0]["title"])->toBe("Dashboard 1");
            expect($result["dashboards"][1]["title"])->toBe("Dashboard 2");
            expect($result["dashboards"][2]["title"])->toBe("Dashboard 3");
        });

        test("returns formatted dashboard structure", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard = TestEntityFactory::createDashboard(
                title: new Title("Test Dashboard"),
                description: "Test Description",
                icon: new Icon("test-icon"),
            );
            $collection = new DashboardCollection($dashboard);

            $dashboardService
                ->shouldReceive("getAllDashboards")
                ->andReturn($collection);

            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardListAction($dashboardService, $outputSpec);

            $result = $action->execute([]);

            expect($result["dashboards"][0]["title"])->toBe("Test Dashboard");
            expect($result["dashboards"][0]["description"])->toBe(
                "Test Description",
            );
            expect($result["dashboards"][0]["icon"])->toBe("test-icon");
        });

        test("includes timestamps in ISO 8601 format", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard = TestEntityFactory::createDashboard();
            $collection = new DashboardCollection($dashboard);

            $dashboardService
                ->shouldReceive("getAllDashboards")
                ->andReturn($collection);

            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardListAction($dashboardService, $outputSpec);

            $result = $action->execute([]);

            expect($result["dashboards"][0]["created_at"])->toMatch(
                "/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/",
            );
            expect($result["dashboards"][0]["updated_at"])->toMatch(
                "/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/",
            );
        });

        test("handles dashboards with icon", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard = TestEntityFactory::createDashboard(
                icon: new Icon("custom-icon"),
            );
            $collection = new DashboardCollection($dashboard);

            $dashboardService
                ->shouldReceive("getAllDashboards")
                ->andReturn($collection);

            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardListAction($dashboardService, $outputSpec);

            $result = $action->execute([]);

            expect($result["dashboards"][0]["icon"])->toBe("custom-icon");
        });

        test("returns all required fields for each dashboard", function () {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboard = TestEntityFactory::createDashboard();
            $collection = new DashboardCollection($dashboard);

            $dashboardService
                ->shouldReceive("getAllDashboards")
                ->andReturn($collection);

            $outputSpec = new DashboardOutputSpec();
            $action = new DashboardListAction($dashboardService, $outputSpec);

            $result = $action->execute([]);

            $dashboard = $result["dashboards"][0];
            expect($dashboard)->toHaveKey("id");
            expect($dashboard)->toHaveKey("title");
            expect($dashboard)->toHaveKey("description");
            expect($dashboard)->toHaveKey("icon");
            expect($dashboard)->toHaveKey("created_at");
            expect($dashboard)->toHaveKey("updated_at");
        });
    });

    describe("integration scenarios", function () {
        test(
            "full workflow: filter, validate, and execute with empty list",
            function () {
                $dashboardService = Mockery::mock(
                    DashboardServiceInterface::class,
                );
                $collection = new DashboardCollection();

                $dashboardService
                    ->shouldReceive("getAllDashboards")
                    ->andReturn($collection);

                $outputSpec = new DashboardOutputSpec();
                $action = new DashboardListAction(
                    $dashboardService,
                    $outputSpec,
                );

                $rawData = [];
                $filtered = $action->filter($rawData);
                $action->validate($filtered);
                $result = $action->execute($filtered);

                expect($result["dashboards"])->toBe([]);
            },
        );

        test(
            "full workflow: filter, validate, and execute with dashboards",
            function () {
                $dashboardService = Mockery::mock(
                    DashboardServiceInterface::class,
                );
                $dashboard1 = TestEntityFactory::createDashboard(
                    title: new Title("Dashboard A"),
                );
                $dashboard2 = TestEntityFactory::createDashboard(
                    title: new Title("Dashboard B"),
                );
                $collection = new DashboardCollection($dashboard1, $dashboard2);

                $dashboardService
                    ->shouldReceive("getAllDashboards")
                    ->andReturn($collection);

                $outputSpec = new DashboardOutputSpec();
                $action = new DashboardListAction(
                    $dashboardService,
                    $outputSpec,
                );

                $rawData = [];
                $filtered = $action->filter($rawData);
                $action->validate($filtered);
                $result = $action->execute($filtered);

                expect($result["dashboards"])->toHaveCount(2);
                expect($result["dashboards"][0]["title"])->toBe("Dashboard A");
                expect($result["dashboards"][1]["title"])->toBe("Dashboard B");
            },
        );

        test(
            "full workflow ignores input data and returns all dashboards",
            function () {
                $dashboardService = Mockery::mock(
                    DashboardServiceInterface::class,
                );
                $dashboard1 = TestEntityFactory::createDashboard(
                    title: new Title("Dashboard 1"),
                );
                $dashboard2 = TestEntityFactory::createDashboard(
                    title: new Title("Dashboard 2"),
                );
                $collection = new DashboardCollection($dashboard1, $dashboard2);

                $dashboardService
                    ->shouldReceive("getAllDashboards")
                    ->andReturn($collection);

                $outputSpec = new DashboardOutputSpec();
                $action = new DashboardListAction(
                    $dashboardService,
                    $outputSpec,
                );

                $rawData = [
                    "id" => "ignored",
                    "title" => "also ignored",
                    "random" => "data",
                ];
                $filtered = $action->filter($rawData);
                $action->validate($filtered);
                $result = $action->execute($filtered);

                expect($result["dashboards"])->toHaveCount(2);
            },
        );
    });
});
