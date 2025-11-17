<?php

use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Service\FavoriteService;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;
use Ramsey\Uuid\Uuid;

describe("FavoriteService", function () {
    describe("addFavorite method", function () {
        test(
            "adds a link as favorite to a dashboard with correct sort order",
            function () {
                $dashboardId = Uuid::uuid4();
                $linkId = Uuid::uuid4();
                $dashboard = TestEntityFactory::createDashboard(
                    id: $dashboardId,
                );
                $link = TestEntityFactory::createLink(id: $linkId);
                $favorite = TestEntityFactory::createFavorite(
                    dashboard: $dashboard,
                    link: $link,
                );

                $favoriteRepository = Mockery::mock(
                    FavoriteRepositoryInterface::class,
                );
                $favoriteRepository
                    ->shouldReceive("getMaxSortOrderForDashboardId")
                    ->with($dashboardId)
                    ->once()
                    ->andReturn(2);
                $favoriteRepository
                    ->shouldReceive("isFavorite")
                    ->with($dashboardId, $linkId)
                    ->once()
                    ->andReturn(false);
                $favoriteRepository
                    ->shouldReceive("addFavorite")
                    ->with($dashboardId, $linkId, 3)
                    ->once()
                    ->andReturn($favorite);

                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $dashboardRepository
                    ->shouldReceive("findById")
                    ->with($dashboardId)
                    ->once()
                    ->andReturn($dashboard);

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $linkRepository
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->once()
                    ->andReturn($link);

                $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
                $unitOfWork
                    ->shouldReceive("transactional")
                    ->andReturnUsing(function ($callback) {
                        return $callback();
                    });

                $service = new FavoriteService(
                    $favoriteRepository,
                    $dashboardRepository,
                    $linkRepository,
                    $unitOfWork,
                );

                $service->addFavorite($dashboardId, $linkId);

                expect(true)->toBeTrue();
            },
        );

        test("adds favorite to empty dashboard with sort order 0", function () {
            $dashboardId = Uuid::uuid4();
            $linkId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);
            $link = TestEntityFactory::createLink(id: $linkId);
            $favorite = TestEntityFactory::createFavorite(
                dashboard: $dashboard,
                link: $link,
            );

            $favoriteRepository = Mockery::mock(
                FavoriteRepositoryInterface::class,
            );
            $favoriteRepository
                ->shouldReceive("getMaxSortOrderForDashboardId")
                ->with($dashboardId)
                ->once()
                ->andReturn(-1);
            $favoriteRepository
                ->shouldReceive("isFavorite")
                ->with($dashboardId, $linkId)
                ->once()
                ->andReturn(false);
            $favoriteRepository
                ->shouldReceive("addFavorite")
                ->with($dashboardId, $linkId, 0)
                ->once()
                ->andReturn($favorite);

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $dashboardRepository
                ->shouldReceive("findById")
                ->with($dashboardId)
                ->once()
                ->andReturn($dashboard);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->with($linkId)
                ->once()
                ->andReturn($link);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork
                ->shouldReceive("transactional")
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new FavoriteService(
                $favoriteRepository,
                $dashboardRepository,
                $linkRepository,
                $unitOfWork,
            );

            $service->addFavorite($dashboardId, $linkId);

            expect(true)->toBeTrue();
        });

        test(
            "throws FavoriteNotFoundException when link is already favorited",
            function () {
                $dashboardId = Uuid::uuid4();
                $linkId = Uuid::uuid4();
                $dashboard = TestEntityFactory::createDashboard(
                    id: $dashboardId,
                );
                $link = TestEntityFactory::createLink(id: $linkId);

                $favoriteRepository = Mockery::mock(
                    FavoriteRepositoryInterface::class,
                );
                $favoriteRepository
                    ->shouldReceive("isFavorite")
                    ->with($dashboardId, $linkId)
                    ->once()
                    ->andReturn(true);
                // addFavorite should not be called
                $favoriteRepository->shouldNotReceive("addFavorite");

                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $dashboardRepository
                    ->shouldReceive("findById")
                    ->with($dashboardId)
                    ->once()
                    ->andReturn($dashboard);

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $linkRepository
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->once()
                    ->andReturn($link);

                $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
                $unitOfWork
                    ->shouldReceive("transactional")
                    ->andReturnUsing(function ($callback) {
                        return $callback();
                    });

                $service = new FavoriteService(
                    $favoriteRepository,
                    $dashboardRepository,
                    $linkRepository,
                    $unitOfWork,
                );

                expect(
                    fn() => $service->addFavorite($dashboardId, $linkId),
                )->toThrow(FavoriteNotFoundException::class);
            },
        );

        test(
            "throws DashboardNotFoundException when dashboard does not exist",
            function () {
                $dashboardId = Uuid::uuid4();
                $linkId = Uuid::uuid4();

                $favoriteRepository = Mockery::mock(
                    FavoriteRepositoryInterface::class,
                );
                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $dashboardRepository
                    ->shouldReceive("findById")
                    ->with($dashboardId)
                    ->once()
                    ->andThrow(DashboardNotFoundException::forId($dashboardId));

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
                $unitOfWork
                    ->shouldReceive("transactional")
                    ->andReturnUsing(function ($callback) {
                        return $callback();
                    });

                $service = new FavoriteService(
                    $favoriteRepository,
                    $dashboardRepository,
                    $linkRepository,
                    $unitOfWork,
                );

                expect(
                    fn() => $service->addFavorite($dashboardId, $linkId),
                )->toThrow(DashboardNotFoundException::class);
            },
        );

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $dashboardId = Uuid::uuid4();
                $linkId = Uuid::uuid4();
                $dashboard = TestEntityFactory::createDashboard(
                    id: $dashboardId,
                );

                $favoriteRepository = Mockery::mock(
                    FavoriteRepositoryInterface::class,
                );
                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $dashboardRepository
                    ->shouldReceive("findById")
                    ->with($dashboardId)
                    ->once()
                    ->andReturn($dashboard);

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $linkRepository
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->once()
                    ->andThrow(LinkNotFoundException::class);

                $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
                $unitOfWork
                    ->shouldReceive("transactional")
                    ->andReturnUsing(function ($callback) {
                        return $callback();
                    });

                $service = new FavoriteService(
                    $favoriteRepository,
                    $dashboardRepository,
                    $linkRepository,
                    $unitOfWork,
                );

                expect(
                    fn() => $service->addFavorite($dashboardId, $linkId),
                )->toThrow(LinkNotFoundException::class);
            },
        );

        test("wraps add favorite in a transaction", function () {
            $dashboardId = Uuid::uuid4();
            $linkId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);
            $link = TestEntityFactory::createLink(id: $linkId);
            $favorite = TestEntityFactory::createFavorite(
                dashboard: $dashboard,
                link: $link,
            );

            $favoriteRepository = Mockery::mock(
                FavoriteRepositoryInterface::class,
            );
            $favoriteRepository->shouldReceive("isFavorite")->andReturn(false);
            $favoriteRepository
                ->shouldReceive("getMaxSortOrderForDashboardId")
                ->andReturn(-1);
            $favoriteRepository
                ->shouldReceive("addFavorite")
                ->andReturn($favorite);

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $dashboardRepository
                ->shouldReceive("findById")
                ->andReturn($dashboard);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository->shouldReceive("findById")->andReturn($link);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork
                ->shouldReceive("transactional")
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new FavoriteService(
                $favoriteRepository,
                $dashboardRepository,
                $linkRepository,
                $unitOfWork,
            );

            $service->addFavorite($dashboardId, $linkId);

            expect(true)->toBeTrue();
        });
    });

    describe("removeFavorite method", function () {
        test("removes a favorite from a dashboard", function () {
            $dashboardId = Uuid::uuid4();
            $linkId = Uuid::uuid4();

            $favoriteRepository = Mockery::mock(
                FavoriteRepositoryInterface::class,
            );
            $favoriteRepository
                ->shouldReceive("removeFavorite")
                ->with($dashboardId, $linkId)
                ->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork
                ->shouldReceive("transactional")
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new FavoriteService(
                $favoriteRepository,
                $dashboardRepository,
                $linkRepository,
                $unitOfWork,
            );

            $service->removeFavorite($dashboardId, $linkId);

            expect(true)->toBeTrue();
        });

        test(
            "throws FavoriteNotFoundException when favorite does not exist",
            function () {
                $dashboardId = Uuid::uuid4();
                $linkId = Uuid::uuid4();

                $favoriteRepository = Mockery::mock(
                    FavoriteRepositoryInterface::class,
                );
                $favoriteRepository
                    ->shouldReceive("removeFavorite")
                    ->with($dashboardId, $linkId)
                    ->once()
                    ->andThrow(FavoriteNotFoundException::class);

                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
                $unitOfWork
                    ->shouldReceive("transactional")
                    ->andReturnUsing(function ($callback) {
                        return $callback();
                    });

                $service = new FavoriteService(
                    $favoriteRepository,
                    $dashboardRepository,
                    $linkRepository,
                    $unitOfWork,
                );

                expect(
                    fn() => $service->removeFavorite($dashboardId, $linkId),
                )->toThrow(FavoriteNotFoundException::class);
            },
        );

        test("wraps remove favorite in a transaction", function () {
            $dashboardId = Uuid::uuid4();
            $linkId = Uuid::uuid4();

            $favoriteRepository = Mockery::mock(
                FavoriteRepositoryInterface::class,
            );
            $favoriteRepository->shouldReceive("removeFavorite");

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork
                ->shouldReceive("transactional")
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new FavoriteService(
                $favoriteRepository,
                $dashboardRepository,
                $linkRepository,
                $unitOfWork,
            );

            $service->removeFavorite($dashboardId, $linkId);

            expect(true)->toBeTrue();
        });
    });

    describe("reorderFavorites method", function () {
        test("reorders favorites within a dashboard", function () {
            $dashboardId = Uuid::uuid4();
            $linkId1 = Uuid::uuid4();
            $linkId2 = Uuid::uuid4();
            $linkId3 = Uuid::uuid4();

            $linkIdToSortOrder = [
                $linkId1->toString() => 2,
                $linkId2->toString() => 0,
                $linkId3->toString() => 1,
            ];

            $favorite1 = TestEntityFactory::createFavorite(sortOrder: 2);
            $favorite2 = TestEntityFactory::createFavorite(sortOrder: 0);
            $favorite3 = TestEntityFactory::createFavorite(sortOrder: 1);

            $favoriteRepository = Mockery::mock(
                FavoriteRepositoryInterface::class,
            );
            $favoriteRepository
                ->shouldReceive("reorderFavorites")
                ->with($dashboardId, $linkIdToSortOrder)
                ->once();
            $favoriteRepository
                ->shouldReceive("findByDashboardId")
                ->with($dashboardId)
                ->once()
                ->andReturn(
                    new FavoriteCollection($favorite1, $favorite2, $favorite3),
                );

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork
                ->shouldReceive("transactional")
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new FavoriteService(
                $favoriteRepository,
                $dashboardRepository,
                $linkRepository,
                $unitOfWork,
            );

            $result = $service->reorderFavorites(
                $dashboardId,
                $linkIdToSortOrder,
            );

            expect($result)->toHaveCount(3);
        });

        test("handles reordering with empty array", function () {
            $dashboardId = Uuid::uuid4();

            $favoriteRepository = Mockery::mock(
                FavoriteRepositoryInterface::class,
            );
            $favoriteRepository
                ->shouldReceive("reorderFavorites")
                ->with($dashboardId, [])
                ->once();
            $favoriteRepository
                ->shouldReceive("findByDashboardId")
                ->with($dashboardId)
                ->once()
                ->andReturn(new FavoriteCollection());

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork
                ->shouldReceive("transactional")
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new FavoriteService(
                $favoriteRepository,
                $dashboardRepository,
                $linkRepository,
                $unitOfWork,
            );

            $result = $service->reorderFavorites($dashboardId, []);

            expect($result)->toHaveCount(0);
        });

        test("wraps reorder favorites in a transaction", function () {
            $dashboardId = Uuid::uuid4();
            $linkIdToSortOrder = [];

            $favoriteRepository = Mockery::mock(
                FavoriteRepositoryInterface::class,
            );
            $favoriteRepository->shouldReceive("reorderFavorites");
            $favoriteRepository
                ->shouldReceive("findByDashboardId")
                ->andReturn(new FavoriteCollection());

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork
                ->shouldReceive("transactional")
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new FavoriteService(
                $favoriteRepository,
                $dashboardRepository,
                $linkRepository,
                $unitOfWork,
            );

            $service->reorderFavorites($dashboardId, $linkIdToSortOrder);

            expect(true)->toBeTrue();
        });
    });

    describe("integration scenarios", function () {
        test("full workflow: add, reorder, and remove favorites", function () {
            $dashboardId = Uuid::uuid4();
            $linkId1 = Uuid::uuid4();
            $linkId2 = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);
            $link1 = TestEntityFactory::createLink(id: $linkId1);
            $link2 = TestEntityFactory::createLink(id: $linkId2);
            $favorite1 = TestEntityFactory::createFavorite(
                dashboard: $dashboard,
                link: $link1,
            );
            $favorite2 = TestEntityFactory::createFavorite(
                dashboard: $dashboard,
                link: $link2,
            );

            $favoriteRepository = Mockery::mock(
                FavoriteRepositoryInterface::class,
            );
            // For first addFavorite
            $favoriteRepository
                ->shouldReceive("isFavorite")
                ->with($dashboardId, $linkId1)
                ->once()
                ->andReturn(false);
            $favoriteRepository
                ->shouldReceive("getMaxSortOrderForDashboardId")
                ->with($dashboardId)
                ->andReturn(-1, 0);
            $favoriteRepository
                ->shouldReceive("addFavorite")
                ->times(2)
                ->andReturn($favorite1, $favorite2);
            // For reorderFavorites
            $favoriteRepository->shouldReceive("reorderFavorites");
            $favoriteRepository
                ->shouldReceive("findByDashboardId")
                ->andReturn(new FavoriteCollection($favorite1, $favorite2));
            // For removeFavorite
            $favoriteRepository->shouldReceive("removeFavorite");
            // For second addFavorite
            $favoriteRepository
                ->shouldReceive("isFavorite")
                ->with($dashboardId, $linkId2)
                ->once()
                ->andReturn(false);

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $dashboardRepository
                ->shouldReceive("findById")
                ->times(2) // only for the two addFavorite calls, not for reorder or remove
                ->andReturn($dashboard);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->times(2) // only for the two addFavorite calls
                ->andReturn($link1, $link2);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork
                ->shouldReceive("transactional")
                ->times(4) // add 2 times, reorder, remove
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new FavoriteService(
                $favoriteRepository,
                $dashboardRepository,
                $linkRepository,
                $unitOfWork,
            );

            // Add first favorite
            $service->addFavorite($dashboardId, $linkId1);

            // Add second favorite
            $service->addFavorite($dashboardId, $linkId2);

            // Reorder
            $linkIdToSortOrder = [
                $linkId1->toString() => 1,
                $linkId2->toString() => 0,
            ];
            $service->reorderFavorites($dashboardId, $linkIdToSortOrder);

            // Remove first favorite
            $service->removeFavorite($dashboardId, $linkId1);

            expect(true)->toBeTrue();
        });

        test(
            "multiple favoring workflow with duplicate prevention",
            function () {
                $dashboardId = Uuid::uuid4();
                $linkId = Uuid::uuid4();
                $dashboard = TestEntityFactory::createDashboard(
                    id: $dashboardId,
                );
                $link = TestEntityFactory::createLink(id: $linkId);
                $favorite = TestEntityFactory::createFavorite(
                    dashboard: $dashboard,
                    link: $link,
                );

                $favoriteRepository = Mockery::mock(
                    FavoriteRepositoryInterface::class,
                );
                // First addFavorite - not favorited yet
                $favoriteRepository
                    ->shouldReceive("isFavorite")
                    ->with($dashboardId, $linkId)
                    ->once()
                    ->andReturn(false);
                $favoriteRepository
                    ->shouldReceive("getMaxSortOrderForDashboardId")
                    ->with($dashboardId)
                    ->once()
                    ->andReturn(-1);
                $favoriteRepository
                    ->shouldReceive("addFavorite")
                    ->once()
                    ->andReturn($favorite);

                // Second addFavorite - already favorited
                $favoriteRepository
                    ->shouldReceive("isFavorite")
                    ->with($dashboardId, $linkId)
                    ->once()
                    ->andReturn(true);

                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $dashboardRepository
                    ->shouldReceive("findById")
                    ->times(2)
                    ->andReturn($dashboard);

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $linkRepository
                    ->shouldReceive("findById")
                    ->times(2)
                    ->andReturn($link);

                $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
                $unitOfWork
                    ->shouldReceive("transactional")
                    ->times(2)
                    ->andReturnUsing(function ($callback) {
                        return $callback();
                    });

                $service = new FavoriteService(
                    $favoriteRepository,
                    $dashboardRepository,
                    $linkRepository,
                    $unitOfWork,
                );

                // Add favorite first time
                $service->addFavorite($dashboardId, $linkId);

                // Try to add same favorite again - should throw FavoriteNotFoundException
                expect(
                    fn() => $service->addFavorite($dashboardId, $linkId),
                )->toThrow(FavoriteNotFoundException::class);
            },
        );
    });
});
