<?php

use jschreuder\BookmarkBureau\Entity\Mapper\FavoriteEntityMapper;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Util\SqlFormat;

describe("FavoriteEntityMapper", function () {
    describe("getFields", function () {
        test("returns all favorite field names", function () {
            $mapper = new FavoriteEntityMapper();
            $fields = $mapper->getFields();

            expect($fields)->toBe([
                "dashboard",
                "link",
                "sort_order",
                "created_at",
            ]);
        });
    });

    describe("getDbFields", function () {
        test(
            "returns database field names with dashboard_id and link_id",
            function () {
                $mapper = new FavoriteEntityMapper();
                $dbFields = $mapper->getDbFields();

                expect($dbFields)->toBe([
                    "dashboard_id",
                    "link_id",
                    "sort_order",
                    "created_at",
                ]);
            },
        );

        test(
            "getDbFields differs from getFields in entity reference fields",
            function () {
                $mapper = new FavoriteEntityMapper();
                $fields = $mapper->getFields();
                $dbFields = $mapper->getDbFields();

                expect($dbFields)->not->toBe($fields);
                expect(in_array("dashboard", $dbFields, true))->toBeFalse();
                expect(in_array("link", $dbFields, true))->toBeFalse();
                expect(in_array("dashboard_id", $dbFields, true))->toBeTrue();
                expect(in_array("link_id", $dbFields, true))->toBeTrue();
                expect(in_array("dashboard", $fields, true))->toBeTrue();
                expect(in_array("link", $fields, true))->toBeTrue();
            },
        );
    });

    describe("supports", function () {
        test("returns true for Favorite entities", function () {
            $mapper = new FavoriteEntityMapper();
            $favorite = TestEntityFactory::createFavorite();

            expect($mapper->supports($favorite))->toBeTrue();
        });

        test("returns false for non-Favorite entities", function () {
            $mapper = new FavoriteEntityMapper();
            $link = TestEntityFactory::createLink();

            expect($mapper->supports($link))->toBeFalse();
        });
    });

    describe("mapToEntity", function () {
        test("maps row data to Favorite entity", function () {
            $mapper = new FavoriteEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();
            $link = TestEntityFactory::createLink();

            $data = [
                "dashboard_id" => $dashboard->dashboardId->getBytes(),
                "link_id" => $link->linkId->getBytes(),
                "sort_order" => 3,
                "created_at" => "2024-03-01 08:00:00",
                "dashboard" => $dashboard,
                "link" => $link,
            ];

            $favorite = $mapper->mapToEntity($data);

            expect($favorite)->toBeInstanceOf(Favorite::class);
            expect($favorite->dashboard)->toBe($dashboard);
            expect($favorite->link)->toBe($link);
            expect($favorite->sortOrder)->toBe(3);
            expect($favorite->createdAt->format("Y-m-d H:i:s"))->toBe(
                "2024-03-01 08:00:00",
            );
        });

        test(
            "throws InvalidArgumentException when required fields are missing",
            function () {
                $mapper = new FavoriteEntityMapper();

                $data = [
                    "dashboard_id" => "some-id",
                    "link_id" => "some-link-id",
                    // Missing: sort_order, created_at, dashboard, link
                ];

                expect(fn() => $mapper->mapToEntity($data))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("mapToRow", function () {
        test("maps Favorite entity to row array", function () {
            $mapper = new FavoriteEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();
            $link = TestEntityFactory::createLink();
            $favorite = TestEntityFactory::createFavorite(
                dashboard: $dashboard,
                link: $link,
                sortOrder: 5,
            );

            $row = $mapper->mapToRow($favorite);

            expect($row)->toHaveKey("dashboard_id");
            expect($row)->toHaveKey("link_id");
            expect($row)->toHaveKey("sort_order");
            expect($row)->toHaveKey("created_at");

            expect($row["dashboard_id"])->toBe(
                $dashboard->dashboardId->getBytes(),
            );
            expect($row["link_id"])->toBe($link->linkId->getBytes());
            expect($row["sort_order"])->toBe("5");
        });

        test(
            "throws InvalidArgumentException when entity is not supported",
            function () {
                $mapper = new FavoriteEntityMapper();
                $dashboard = TestEntityFactory::createDashboard();

                expect(fn() => $mapper->mapToRow($dashboard))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("round-trip mapping", function () {
        test("maps entity to row and back preserves all data", function () {
            $mapper = new FavoriteEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();
            $link = TestEntityFactory::createLink();
            $originalFavorite = TestEntityFactory::createFavorite(
                dashboard: $dashboard,
                link: $link,
                sortOrder: 7,
            );

            $row = $mapper->mapToRow($originalFavorite);
            // Manually add related entities to data since mapToEntity expects them
            $row["dashboard"] = $dashboard;
            $row["link"] = $link;
            $restoredFavorite = $mapper->mapToEntity($row);

            expect($restoredFavorite->dashboard)->toBe($dashboard);
            expect($restoredFavorite->link)->toBe($link);
            expect($restoredFavorite->sortOrder)->toBe(7);
        });

        test("preserves sort order through round-trip", function () {
            $mapper = new FavoriteEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();
            $link = TestEntityFactory::createLink();
            $originalFavorite = TestEntityFactory::createFavorite(
                dashboard: $dashboard,
                link: $link,
                sortOrder: 42,
            );

            $row = $mapper->mapToRow($originalFavorite);
            $row["dashboard"] = $dashboard;
            $row["link"] = $link;
            $restoredFavorite = $mapper->mapToEntity($row);

            expect($restoredFavorite->sortOrder)->toBe(42);
        });
    });
});
