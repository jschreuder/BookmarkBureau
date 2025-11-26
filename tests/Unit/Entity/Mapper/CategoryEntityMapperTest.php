<?php

use jschreuder\BookmarkBureau\Entity\Mapper\CategoryEntityMapper;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use Ramsey\Uuid\Uuid;

describe("CategoryEntityMapper", function () {
    describe("getFields", function () {
        test("returns all category field names", function () {
            $mapper = new CategoryEntityMapper();
            $fields = $mapper->getFields();

            expect($fields)->toBe([
                "category_id",
                "dashboard",
                "title",
                "color",
                "sort_order",
                "created_at",
                "updated_at",
            ]);
        });
    });

    describe("getDbFields", function () {
        test(
            "returns database field names with dashboard_id instead of dashboard",
            function () {
                $mapper = new CategoryEntityMapper();
                $dbFields = $mapper->getDbFields();

                expect($dbFields)->toBe([
                    "category_id",
                    "dashboard_id",
                    "title",
                    "color",
                    "sort_order",
                    "created_at",
                    "updated_at",
                ]);
            },
        );

        test(
            "getDbFields differs from getFields only in dashboard field",
            function () {
                $mapper = new CategoryEntityMapper();
                $fields = $mapper->getFields();
                $dbFields = $mapper->getDbFields();

                expect($dbFields)->not->toBe($fields);
                expect(in_array("dashboard", $dbFields, true))->toBeFalse();
                expect(in_array("dashboard_id", $dbFields, true))->toBeTrue();
                expect(in_array("dashboard", $fields, true))->toBeTrue();
                expect(in_array("dashboard_id", $fields, true))->toBeFalse();
            },
        );
    });

    describe("supports", function () {
        test("returns true for Category entities", function () {
            $mapper = new CategoryEntityMapper();
            $category = TestEntityFactory::createCategory();

            expect($mapper->supports($category))->toBeTrue();
        });

        test("returns false for non-Category entities", function () {
            $mapper = new CategoryEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();

            expect($mapper->supports($dashboard))->toBeFalse();
        });
    });

    describe("mapToEntity", function () {
        test("maps row data to Category entity", function () {
            $mapper = new CategoryEntityMapper();
            $categoryId = Uuid::uuid4();
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $data = [
                "category_id" => $categoryId->getBytes(),
                "dashboard_id" => $dashboardId->getBytes(),
                "title" => "Bookmarks",
                "color" => "#FF5733",
                "sort_order" => 1,
                "created_at" => "2024-03-01 08:00:00",
                "updated_at" => "2024-03-05 10:30:00",
                "dashboard" => $dashboard,
            ];

            $category = $mapper->mapToEntity($data);

            expect($category)->toBeInstanceOf(Category::class);
            expect($category->categoryId->equals($categoryId))->toBeTrue();
            expect($category->dashboard)->toBe($dashboard);
            expect((string) $category->title)->toBe("Bookmarks");
            expect((string) $category->color)->toBe("#FF5733");
            expect($category->sortOrder)->toBe(1);
            expect($category->createdAt->format("Y-m-d H:i:s"))->toBe(
                "2024-03-01 08:00:00",
            );
            expect($category->updatedAt->format("Y-m-d H:i:s"))->toBe(
                "2024-03-05 10:30:00",
            );
        });

        test("maps row data with null color to Category entity", function () {
            $mapper = new CategoryEntityMapper();
            $categoryId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard();

            $data = [
                "category_id" => $categoryId->getBytes(),
                "dashboard_id" => $dashboard->dashboardId->getBytes(),
                "title" => "Bookmarks",
                "color" => null,
                "sort_order" => 0,
                "created_at" => "2024-03-01 08:00:00",
                "updated_at" => "2024-03-01 08:00:00",
                "dashboard" => $dashboard,
            ];

            $category = $mapper->mapToEntity($data);

            expect($category->color)->toBeNull();
        });

        test(
            "throws InvalidArgumentException when required fields are missing",
            function () {
                $mapper = new CategoryEntityMapper();

                $data = [
                    "category_id" => Uuid::uuid4()->getBytes(),
                    "title" => "Bookmarks",
                    // Missing: dashboard_id, color, sort_order, created_at, updated_at, dashboard
                ];

                expect(fn() => $mapper->mapToEntity($data))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("mapToRow", function () {
        test("maps Category entity to row array", function () {
            $mapper = new CategoryEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                title: new Title("Important"),
                color: new HexColor("#FF0000"),
                sortOrder: 2,
            );

            $row = $mapper->mapToRow($category);

            expect($row)->toHaveKey("category_id");
            expect($row)->toHaveKey("dashboard_id");
            expect($row)->toHaveKey("title");
            expect($row)->toHaveKey("color");
            expect($row)->toHaveKey("sort_order");
            expect($row)->toHaveKey("created_at");
            expect($row)->toHaveKey("updated_at");

            expect($row["category_id"])->toBe(
                $category->categoryId->getBytes(),
            );
            expect($row["dashboard_id"])->toBe(
                $dashboard->dashboardId->getBytes(),
            );
            expect($row["title"])->toBe("Important");
            expect($row["color"])->toBe("#FF0000");
            expect($row["sort_order"])->toBe("2");
        });

        test("maps Category entity with null color to row array", function () {
            $mapper = new CategoryEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                color: null,
            );

            $row = $mapper->mapToRow($category);

            expect($row["color"])->toBeNull();
        });

        test(
            "extracts dashboard_id from related Dashboard entity",
            function () {
                $mapper = new CategoryEntityMapper();
                $dashboard = TestEntityFactory::createDashboard();
                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );

                $row = $mapper->mapToRow($category);

                expect($row["dashboard_id"])->toBe(
                    $dashboard->dashboardId->getBytes(),
                );
            },
        );

        test("formats timestamps correctly", function () {
            $mapper = new CategoryEntityMapper();
            $createdAt = new DateTimeImmutable("2024-03-01 08:00:45");
            $updatedAt = new DateTimeImmutable("2024-03-05 10:30:20");
            $category = TestEntityFactory::createCategory(
                createdAt: $createdAt,
                updatedAt: $updatedAt,
            );

            $row = $mapper->mapToRow($category);

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
                $mapper = new CategoryEntityMapper();
                $link = TestEntityFactory::createLink();

                expect(fn() => $mapper->mapToRow($link))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("round-trip mapping", function () {
        test("maps entity to row and back preserves all data", function () {
            $mapper = new CategoryEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();
            $originalCategory = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                title: new Title("Round Trip"),
                color: new HexColor("#00FF00"),
                sortOrder: 5,
            );

            $row = $mapper->mapToRow($originalCategory);
            // Manually add dashboard to data since mapToEntity expects it
            $row["dashboard"] = $dashboard;
            $restoredCategory = $mapper->mapToEntity($row);

            expect(
                $restoredCategory->categoryId->equals(
                    $originalCategory->categoryId,
                ),
            )->toBeTrue();
            expect($restoredCategory->dashboard)->toBe($dashboard);
            expect((string) $restoredCategory->title)->toBe(
                (string) $originalCategory->title,
            );
            expect((string) $restoredCategory->color)->toBe(
                (string) $originalCategory->color,
            );
            expect($restoredCategory->sortOrder)->toBe(5);
        });

        test("round-trip mapping with null color preserves null", function () {
            $mapper = new CategoryEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();
            $originalCategory = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                color: null,
            );

            $row = $mapper->mapToRow($originalCategory);
            $row["dashboard"] = $dashboard;
            $restoredCategory = $mapper->mapToEntity($row);

            expect($restoredCategory->color)->toBeNull();
        });

        test("preserves sort order through round-trip", function () {
            $mapper = new CategoryEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();
            $originalCategory = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                sortOrder: 42,
            );

            $row = $mapper->mapToRow($originalCategory);
            $row["dashboard"] = $dashboard;
            $restoredCategory = $mapper->mapToEntity($row);

            expect($restoredCategory->sortOrder)->toBe(42);
        });
    });
});
