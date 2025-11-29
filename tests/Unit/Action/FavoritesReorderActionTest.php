<?php

use jschreuder\BookmarkBureau\Action\FavoriteReorderAction;
use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use jschreuder\BookmarkBureau\InputSpec\ReorderFavoritesInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\FavoriteOutputSpec;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe("FavoritesReorderAction", function () {
    describe("filter method", function () {
        test("filters dashboard_id and links", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new ReorderFavoritesInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteReorderAction(
                $favoriteService,
                $inputSpec,
                $outputSpec,
            );

            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $action->filter([
                "dashboard_id" => "  {$dashboardId}  ",
                "links" => [["link_id" => "  {$linkId}  ", "sort_order" => 1]],
            ]);

            expect($filtered["dashboard_id"])->toBe($dashboardId);
            expect($filtered["links"][0]["link_id"])->toBe($linkId);
        });
    });

    describe("validate method", function () {
        test("passes validation with valid data", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new ReorderFavoritesInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteReorderAction(
                $favoriteService,
                $inputSpec,
                $outputSpec,
            );

            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            try {
                $action->validate([
                    "dashboard_id" => $dashboardId,
                    "links" => [["link_id" => $linkId, "sort_order" => 1]],
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for empty links array", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new ReorderFavoritesInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteReorderAction(
                $favoriteService,
                $inputSpec,
                $outputSpec,
            );

            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($action, $dashboardId) {
                $action->validate([
                    "dashboard_id" => $dashboardId,
                    "links" => [],
                ]);
            })->toThrow(ValidationFailedException::class);
        });
    });

    describe("execute method", function () {
        test(
            "calls favoriteService.reorderFavorites with correct parameters",
            function () {
                $favoriteService = Mockery::mock(
                    FavoriteServiceInterface::class,
                );
                $favorite1 = TestEntityFactory::createFavorite(sortOrder: 1);
                $favorite2 = TestEntityFactory::createFavorite(sortOrder: 2);
                $collection = new FavoriteCollection($favorite1, $favorite2);

                $dashboardId = $favorite1->dashboard->dashboardId;
                $linkId1 = $favorite1->link->linkId->toString();
                $linkId2 = $favorite2->link->linkId->toString();

                $favoriteService
                    ->shouldReceive("getFavoritesForDashboard")
                    ->withAnyArgs()
                    ->andReturn($collection);

                $favoriteService
                    ->shouldReceive("reorderFavorites")
                    ->with(
                        Mockery::on(
                            fn($arg) => $arg->toString() ===
                                $dashboardId->toString(),
                        ),
                        \Mockery::type(FavoriteCollection::class),
                    );

                $inputSpec = new ReorderFavoritesInputSpec();
                $outputSpec = new FavoriteOutputSpec();
                $action = new FavoriteReorderAction(
                    $favoriteService,
                    $inputSpec,
                    $outputSpec,
                );

                $action->execute([
                    "dashboard_id" => $dashboardId->toString(),
                    "links" => [
                        ["link_id" => $linkId1, "sort_order" => 1],
                        ["link_id" => $linkId2, "sort_order" => 2],
                    ],
                ]);

                expect(true)->toBeTrue();
            },
        );

        test("returns array of transformed favorites", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $favorite1 = TestEntityFactory::createFavorite(sortOrder: 1);
            $favorite2 = TestEntityFactory::createFavorite(sortOrder: 2);
            $collection = new FavoriteCollection($favorite1, $favorite2);

            $favoriteService
                ->shouldReceive("getFavoritesForDashboard")
                ->andReturn($collection);

            $favoriteService->shouldReceive("reorderFavorites");

            $inputSpec = new ReorderFavoritesInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteReorderAction(
                $favoriteService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "dashboard_id" => $favorite1->dashboard->dashboardId->toString(),
                "links" => [
                    [
                        "link_id" => $favorite1->link->linkId->toString(),
                        "sort_order" => 1,
                    ],
                    [
                        "link_id" => $favorite2->link->linkId->toString(),
                        "sort_order" => 2,
                    ],
                ],
            ]);

            expect($result["favorites"])->toHaveCount(2);
            expect($result["favorites"][0])->toHaveKey("dashboard_id");
            expect($result["favorites"][0])->toHaveKey("link_id");
            expect($result["favorites"][0])->toHaveKey("sort_order");
            expect($result["favorites"][1])->toHaveKey("dashboard_id");
            expect($result["favorites"][1])->toHaveKey("link_id");
            expect($result["favorites"][1])->toHaveKey("sort_order");
        });

        test("returns empty array when reordering empty list", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $collection = new FavoriteCollection();

            $favoriteService
                ->shouldReceive("getFavoritesForDashboard")
                ->andReturn($collection);

            $favoriteService
                ->shouldReceive("reorderFavorites")
                ->with(
                    Mockery::any(),
                    Mockery::type(FavoriteCollection::class),
                );

            $inputSpec = new ReorderFavoritesInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteReorderAction(
                $favoriteService,
                $inputSpec,
                $outputSpec,
            );

            $dashboardId = Uuid::uuid4()->toString();

            $result = $action->execute([
                "dashboard_id" => $dashboardId,
                "links" => [],
            ]);

            expect($result)->toBe(["favorites" => []]);
        });

        test("transforms each favorite with correct sort_order", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $favorite1 = TestEntityFactory::createFavorite(sortOrder: 5);
            $favorite2 = TestEntityFactory::createFavorite(sortOrder: 10);
            $collection = new FavoriteCollection($favorite1, $favorite2);

            $favoriteService
                ->shouldReceive("getFavoritesForDashboard")
                ->andReturn($collection);

            $favoriteService->shouldReceive("reorderFavorites");

            $inputSpec = new ReorderFavoritesInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteReorderAction(
                $favoriteService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "dashboard_id" => $favorite1->dashboard->dashboardId->toString(),
                "links" => [
                    [
                        "link_id" => $favorite1->link->linkId->toString(),
                        "sort_order" => 5,
                    ],
                    [
                        "link_id" => $favorite2->link->linkId->toString(),
                        "sort_order" => 10,
                    ],
                ],
            ]);

            expect($result["favorites"][0]["sort_order"])->toBe(5);
            expect($result["favorites"][1]["sort_order"])->toBe(10);
        });

        test(
            "sorts favorites by sort_order regardless of input order",
            function () {
                $favoriteService = Mockery::mock(
                    FavoriteServiceInterface::class,
                );
                $favorite1 = TestEntityFactory::createFavorite(sortOrder: 1);
                $favorite2 = TestEntityFactory::createFavorite(sortOrder: 2);
                $favorite3 = TestEntityFactory::createFavorite(sortOrder: 3);
                $collection = new FavoriteCollection(
                    $favorite1,
                    $favorite2,
                    $favorite3,
                );

                $favoriteService
                    ->shouldReceive("getFavoritesForDashboard")
                    ->andReturn($collection);

                // Verify that the FavoriteCollection passed to reorderFavorites is in correct order
                $favoriteService->shouldReceive("reorderFavorites")->with(
                    Mockery::any(),
                    Mockery::on(function (FavoriteCollection $favorites) use (
                        $favorite1,
                        $favorite2,
                        $favorite3,
                    ) {
                        $favoritesArray = iterator_to_array($favorites);
                        return count($favoritesArray) === 3 &&
                            $favoritesArray[0]->link->linkId->equals(
                                $favorite1->link->linkId,
                            ) &&
                            $favoritesArray[1]->link->linkId->equals(
                                $favorite2->link->linkId,
                            ) &&
                            $favoritesArray[2]->link->linkId->equals(
                                $favorite3->link->linkId,
                            );
                    }),
                );

                $inputSpec = new ReorderFavoritesInputSpec();
                $outputSpec = new FavoriteOutputSpec();
                $action = new FavoriteReorderAction(
                    $favoriteService,
                    $inputSpec,
                    $outputSpec,
                );

                // Send favorites in REVERSE order but with correct sort_order values
                $result = $action->execute([
                    "dashboard_id" => $favorite1->dashboard->dashboardId->toString(),
                    "links" => [
                        [
                            "link_id" => $favorite3->link->linkId->toString(),
                            "sort_order" => 3,
                        ],
                        [
                            "link_id" => $favorite1->link->linkId->toString(),
                            "sort_order" => 1,
                        ],
                        [
                            "link_id" => $favorite2->link->linkId->toString(),
                            "sort_order" => 2,
                        ],
                    ],
                ]);

                // Verify the output is in the correct order (by sort_order, not input order)
                expect($result["favorites"][0]["link_id"])->toBe(
                    $favorite1->link->linkId->toString(),
                );
                expect($result["favorites"][1]["link_id"])->toBe(
                    $favorite2->link->linkId->toString(),
                );
                expect($result["favorites"][2]["link_id"])->toBe(
                    $favorite3->link->linkId->toString(),
                );
            },
        );

        test(
            "throws FavoriteNotFoundException for link not favorited",
            function () {
                $favoriteService = Mockery::mock(
                    FavoriteServiceInterface::class,
                );
                $favorite1 = TestEntityFactory::createFavorite();
                $collection = new FavoriteCollection($favorite1);

                $favoriteService
                    ->shouldReceive("getFavoritesForDashboard")
                    ->andReturn($collection);

                $inputSpec = new ReorderFavoritesInputSpec();
                $outputSpec = new FavoriteOutputSpec();
                $action = new FavoriteReorderAction(
                    $favoriteService,
                    $inputSpec,
                    $outputSpec,
                );

                $dashboardId = $favorite1->dashboard->dashboardId->toString();
                $invalidLinkId = Uuid::uuid4()->toString();

                expect(function () use (
                    $action,
                    $dashboardId,
                    $favorite1,
                    $invalidLinkId,
                ) {
                    $action->execute([
                        "dashboard_id" => $dashboardId,
                        "links" => [
                            [
                                "link_id" => $favorite1->link->linkId->toString(),
                                "sort_order" => 1,
                            ],
                            [
                                "link_id" => $invalidLinkId,
                                "sort_order" => 2,
                            ],
                        ],
                    ]);
                })->toThrow(FavoriteNotFoundException::class);
            },
        );
    });

    describe("integration scenarios", function () {
        test("full workflow: filter, validate, and execute", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $favorite1 = TestEntityFactory::createFavorite(sortOrder: 1);
            $favorite2 = TestEntityFactory::createFavorite(sortOrder: 2);
            $collection = new FavoriteCollection($favorite1, $favorite2);

            $favoriteService
                ->shouldReceive("getFavoritesForDashboard")
                ->andReturn($collection);

            $favoriteService->shouldReceive("reorderFavorites");

            $inputSpec = new ReorderFavoritesInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteReorderAction(
                $favoriteService,
                $inputSpec,
                $outputSpec,
            );

            $dashboardId = $favorite1->dashboard->dashboardId->toString();
            $linkId1 = $favorite1->link->linkId->toString();
            $linkId2 = $favorite2->link->linkId->toString();

            $rawData = [
                "dashboard_id" => "  {$dashboardId}  ",
                "links" => [
                    ["link_id" => "  {$linkId1}  ", "sort_order" => 1],
                    ["link_id" => "  {$linkId2}  ", "sort_order" => 2],
                ],
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result["favorites"])->toHaveCount(2);
                expect($result["favorites"][0])->toHaveKey("sort_order");
                expect($result["favorites"][1])->toHaveKey("sort_order");
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });
});
