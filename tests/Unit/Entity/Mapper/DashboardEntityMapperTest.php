<?php

use jschreuder\BookmarkBureau\Entity\Mapper\DashboardEntityMapper;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use Ramsey\Uuid\Uuid;

describe("DashboardEntityMapper", function () {
    describe("getFields", function () {
        test("returns all dashboard field names", function () {
            $mapper = new DashboardEntityMapper();
            $fields = $mapper->getFields();

            expect($fields)->toBe([
                "dashboard_id",
                "title",
                "description",
                "icon",
                "created_at",
                "updated_at",
            ]);
        });
    });

    describe("getDbFields", function () {
        test(
            "returns same fields as getFields since no entity references",
            function () {
                $mapper = new DashboardEntityMapper();
                $fields = $mapper->getFields();
                $dbFields = $mapper->getDbFields();

                expect($dbFields)->toBe($fields);
                expect($dbFields)->toBe([
                    "dashboard_id",
                    "title",
                    "description",
                    "icon",
                    "created_at",
                    "updated_at",
                ]);
            },
        );
    });

    describe("supports", function () {
        test("returns true for Dashboard entities", function () {
            $mapper = new DashboardEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();

            expect($mapper->supports($dashboard))->toBeTrue();
        });

        test("returns false for non-Dashboard entities", function () {
            $mapper = new DashboardEntityMapper();
            $link = TestEntityFactory::createLink();

            expect($mapper->supports($link))->toBeFalse();
        });
    });

    describe("mapToEntity", function () {
        test("maps row data to Dashboard entity", function () {
            $mapper = new DashboardEntityMapper();
            $dashboardId = Uuid::uuid4();

            $data = [
                "dashboard_id" => $dashboardId->getBytes(),
                "title" => "My Dashboard",
                "description" => "My personal dashboard",
                "icon" => "dashboard-icon",
                "created_at" => "2024-02-01 09:15:00",
                "updated_at" => "2024-02-05 11:20:00",
            ];

            $dashboard = $mapper->mapToEntity($data);

            expect($dashboard)->toBeInstanceOf(Dashboard::class);
            expect($dashboard->dashboardId->equals($dashboardId))->toBeTrue();
            expect((string) $dashboard->title)->toBe("My Dashboard");
            expect($dashboard->description)->toBe("My personal dashboard");
            expect((string) $dashboard->icon)->toBe("dashboard-icon");
            expect($dashboard->createdAt->format("Y-m-d H:i:s"))->toBe(
                "2024-02-01 09:15:00",
            );
            expect($dashboard->updatedAt->format("Y-m-d H:i:s"))->toBe(
                "2024-02-05 11:20:00",
            );
        });

        test("maps row data with null icon to Dashboard entity", function () {
            $mapper = new DashboardEntityMapper();
            $dashboardId = Uuid::uuid4();

            $data = [
                "dashboard_id" => $dashboardId->getBytes(),
                "title" => "My Dashboard",
                "description" => "My personal dashboard",
                "icon" => null,
                "created_at" => "2024-02-01 09:15:00",
                "updated_at" => "2024-02-05 11:20:00",
            ];

            $dashboard = $mapper->mapToEntity($data);

            expect($dashboard->icon)->toBeNull();
        });

        test(
            "throws InvalidArgumentException when required fields are missing",
            function () {
                $mapper = new DashboardEntityMapper();

                $data = [
                    "dashboard_id" => Uuid::uuid4()->getBytes(),
                    "title" => "My Dashboard",
                    // Missing: description, icon, created_at, updated_at
                ];

                expect(fn() => $mapper->mapToEntity($data))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("mapToRow", function () {
        test("maps Dashboard entity to row array", function () {
            $mapper = new DashboardEntityMapper();
            $dashboard = TestEntityFactory::createDashboard(
                title: new Title("Work Dashboard"),
                description: "For work stuff",
                icon: new Icon("work-icon"),
            );

            $row = $mapper->mapToRow($dashboard);

            expect($row)->toHaveKey("dashboard_id");
            expect($row)->toHaveKey("title");
            expect($row)->toHaveKey("description");
            expect($row)->toHaveKey("icon");
            expect($row)->toHaveKey("created_at");
            expect($row)->toHaveKey("updated_at");

            expect($row["dashboard_id"])->toBe(
                $dashboard->dashboardId->getBytes(),
            );
            expect($row["title"])->toBe("Work Dashboard");
            expect($row["description"])->toBe("For work stuff");
            expect($row["icon"])->toBe("work-icon");
        });

        test("maps Dashboard entity with null icon to row array", function () {
            $mapper = new DashboardEntityMapper();
            $dashboardId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable("2024-02-01 09:15:00");
            $updatedAt = new DateTimeImmutable("2024-02-05 11:20:00");
            $dashboard = new Dashboard(
                dashboardId: $dashboardId,
                title: new Title("Test Dashboard"),
                description: "Test Description",
                icon: null,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
            );

            $row = $mapper->mapToRow($dashboard);

            expect($row["icon"])->toBeNull();
        });

        test("formats timestamps correctly", function () {
            $mapper = new DashboardEntityMapper();
            $createdAt = new DateTimeImmutable("2024-02-01 09:15:30");
            $updatedAt = new DateTimeImmutable("2024-02-05 11:20:15");
            $dashboard = TestEntityFactory::createDashboard(
                createdAt: $createdAt,
                updatedAt: $updatedAt,
            );

            $row = $mapper->mapToRow($dashboard);

            expect($row["created_at"])->toBe(
                $createdAt->format(SqlFormat::TIMESTAMP),
            );
            expect($row["updated_at"])->toBe(
                $updatedAt->format(SqlFormat::TIMESTAMP),
            );
        });

        test(
            "throws InvalidArgumentException when entity is not supported",
            function () {
                $mapper = new DashboardEntityMapper();
                $link = TestEntityFactory::createLink();

                expect(fn() => $mapper->mapToRow($link))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("round-trip mapping", function () {
        test("maps entity to row and back preserves all data", function () {
            $mapper = new DashboardEntityMapper();
            $originalDashboard = TestEntityFactory::createDashboard(
                title: new Title("Round Trip Dashboard"),
                description: "Testing round-trip",
                icon: new Icon("rt-dash"),
            );

            $row = $mapper->mapToRow($originalDashboard);
            $restoredDashboard = $mapper->mapToEntity($row);

            expect(
                $restoredDashboard->dashboardId->equals(
                    $originalDashboard->dashboardId,
                ),
            )->toBeTrue();
            expect((string) $restoredDashboard->title)->toBe(
                (string) $originalDashboard->title,
            );
            expect($restoredDashboard->description)->toBe(
                $originalDashboard->description,
            );
            expect((string) $restoredDashboard->icon)->toBe(
                (string) $originalDashboard->icon,
            );
        });

        test("round-trip mapping with null icon preserves null", function () {
            $mapper = new DashboardEntityMapper();
            $dashboardId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable("2024-02-01 09:15:00");
            $updatedAt = new DateTimeImmutable("2024-02-05 11:20:00");
            $originalDashboard = new Dashboard(
                dashboardId: $dashboardId,
                title: new Title("Test Dashboard"),
                description: "Test Description",
                icon: null,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
            );

            $row = $mapper->mapToRow($originalDashboard);
            $restoredDashboard = $mapper->mapToEntity($row);

            expect($restoredDashboard->icon)->toBeNull();
        });
    });
});
