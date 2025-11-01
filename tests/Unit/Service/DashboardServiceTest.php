<?php

use jschreuder\BookmarkBureau\Collection\CategoryCollection;
use jschreuder\BookmarkBureau\Collection\CategoryLinkCollection;
use jschreuder\BookmarkBureau\Collection\CategoryWithLinks;
use jschreuder\BookmarkBureau\Collection\CategoryWithLinksCollection;
use jschreuder\BookmarkBureau\Collection\DashboardCollection;
use jschreuder\BookmarkBureau\Collection\DashboardWithCategoriesAndFavorites;
use jschreuder\BookmarkBureau\Collection\FavoriteCollection;
use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use jschreuder\BookmarkBureau\Service\DashboardService;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;
use Ramsey\Uuid\Uuid;

describe('DashboardService', function () {
    describe('getFullDashboard method', function () {
        test('retrieves dashboard with categories and favorites', function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $category1 = TestEntityFactory::createCategory(dashboard: $dashboard);
            $category2 = TestEntityFactory::createCategory(dashboard: $dashboard);
            $categories = new CategoryCollection($category1, $category2);

            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $categoryLinks1 = new CategoryLinkCollection(
                TestEntityFactory::createCategoryLink(category: $category1, link: $link1),
                TestEntityFactory::createCategoryLink(category: $category1, link: $link2)
            );
            $categoryLinks2 = new CategoryLinkCollection();

            $favorite1 = TestEntityFactory::createFavorite(dashboard: $dashboard, link: $link1);
            $favorites = new FavoriteCollection($favorite1);

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')
                ->with($dashboardId)
                ->andReturn($dashboard);

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $categoryRepository->shouldReceive('findByDashboardId')
                ->with($dashboardId)
                ->andReturn($categories);
            $categoryRepository->shouldReceive('findCategoryLinksForCategoryId')
                ->with($category1->categoryId)
                ->andReturn($categoryLinks1);
            $categoryRepository->shouldReceive('findCategoryLinksForCategoryId')
                ->with($category2->categoryId)
                ->andReturn($categoryLinks2);

            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);
            $favoriteRepository->shouldReceive('findByDashboardId')
                ->with($dashboardId)
                ->andReturn($favorites);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $result = $service->getFullDashboard($dashboardId);

            expect($result)->toBeInstanceOf(DashboardWithCategoriesAndFavorites::class);
            expect($result->dashboard)->toEqual($dashboard);
            expect(count($result->categories))->toBe(2);
            expect(count($result->favorites))->toBe(1);
        });

        test('throws DashboardNotFoundException when dashboard does not exist', function () {
            $dashboardId = Uuid::uuid4();

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')
                ->with($dashboardId)
                ->andThrow(DashboardNotFoundException::forId($dashboardId));

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);
            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            expect(fn() => $service->getFullDashboard($dashboardId))
                ->toThrow(DashboardNotFoundException::class);
        });

        test('returns dashboard with empty categories', function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $categories = new CategoryCollection();
            $favorites = new FavoriteCollection();

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')
                ->with($dashboardId)
                ->andReturn($dashboard);

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $categoryRepository->shouldReceive('findByDashboardId')
                ->with($dashboardId)
                ->andReturn($categories);

            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);
            $favoriteRepository->shouldReceive('findByDashboardId')
                ->with($dashboardId)
                ->andReturn($favorites);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $result = $service->getFullDashboard($dashboardId);

            expect(count($result->categories))->toBe(0);
            expect(count($result->favorites))->toBe(0);
        });

        test('returns dashboard with empty favorites', function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $category = TestEntityFactory::createCategory(dashboard: $dashboard);
            $categories = new CategoryCollection($category);
            $categoryLinks = new CategoryLinkCollection();
            $favorites = new FavoriteCollection();

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')
                ->with($dashboardId)
                ->andReturn($dashboard);

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $categoryRepository->shouldReceive('findByDashboardId')
                ->with($dashboardId)
                ->andReturn($categories);
            $categoryRepository->shouldReceive('findCategoryLinksForCategoryId')
                ->with($category->categoryId)
                ->andReturn($categoryLinks);

            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);
            $favoriteRepository->shouldReceive('findByDashboardId')
                ->with($dashboardId)
                ->andReturn($favorites);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $result = $service->getFullDashboard($dashboardId);

            expect(count($result->categories))->toBe(1);
            expect(count($result->favorites))->toBe(0);
        });
    });

    describe('listAllDashboards method', function () {
        test('returns all dashboards', function () {
            $dashboard1 = TestEntityFactory::createDashboard();
            $dashboard2 = TestEntityFactory::createDashboard();
            $collection = new DashboardCollection($dashboard1, $dashboard2);

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findAll')
                ->andReturn($collection);

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);
            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $result = $service->listAllDashboards();

            expect($result)->toEqual($collection);
            expect(count($result))->toBe(2);
        });

        test('returns empty collection when no dashboards exist', function () {
            $collection = new DashboardCollection();

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findAll')
                ->andReturn($collection);

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);
            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $result = $service->listAllDashboards();

            expect(count($result))->toBe(0);
        });
    });

    describe('createDashboard method', function () {
        test('creates a new dashboard with all parameters', function () {
            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('save')->once();

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $result = $service->createDashboard(
                'Test Dashboard',
                'Test Description',
                'test-icon'
            );

            expect($result)->toBeInstanceOf(\jschreuder\BookmarkBureau\Entity\Dashboard::class);
            expect($result->title->value)->toBe('Test Dashboard');
            expect($result->description)->toBe('Test Description');
            expect($result->icon?->value)->toBe('test-icon');
        });

        test('creates a new dashboard without icon', function () {
            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('save')->once();

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $result = $service->createDashboard(
                'Test Dashboard',
                'Test Description',
                null
            );

            expect($result->icon)->toBeNull();
        });

        test('wraps creation in a transaction', function () {
            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('save')->once();

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $service->createDashboard('Test Dashboard', 'Test Description', 'test-icon');

            expect(true)->toBeTrue(); // Mockery validates the transactional was called
        });

        test('rolls back transaction on invalid title', function () {
            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            expect(fn() => $service->createDashboard('', 'Test Description'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('updateDashboard method', function () {
        test('updates an existing dashboard with all parameters', function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')
                ->with($dashboardId)
                ->andReturn($dashboard);
            $dashboardRepository->shouldReceive('save')->once();

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $result = $service->updateDashboard(
                $dashboardId,
                'Updated Dashboard',
                'Updated Description',
                'updated-icon'
            );

            expect($result->title->value)->toBe('Updated Dashboard');
            expect($result->description)->toBe('Updated Description');
            expect($result->icon?->value)->toBe('updated-icon');
        });

        test('updates dashboard without icon', function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId, icon: new Icon('original-icon'));

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')
                ->with($dashboardId)
                ->andReturn($dashboard);
            $dashboardRepository->shouldReceive('save')->once();

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $result = $service->updateDashboard(
                $dashboardId,
                'Updated Dashboard',
                'Updated Description',
                null
            );

            expect($result->icon)->toBeNull();
        });

        test('throws DashboardNotFoundException when dashboard does not exist', function () {
            $dashboardId = Uuid::uuid4();

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')
                ->with($dashboardId)
                ->andThrow(DashboardNotFoundException::forId($dashboardId));

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            expect(fn() => $service->updateDashboard($dashboardId, 'Title', 'Description'))
                ->toThrow(DashboardNotFoundException::class);
        });

        test('wraps update in a transaction', function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')->andReturn($dashboard);
            $dashboardRepository->shouldReceive('save')->once();

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $service->updateDashboard($dashboardId, 'Updated', 'Description');

            expect(true)->toBeTrue(); // Mockery validates the transactional was called
        });
    });

    describe('deleteDashboard method', function () {
        test('deletes an existing dashboard', function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')
                ->with($dashboardId)
                ->andReturn($dashboard);
            $dashboardRepository->shouldReceive('delete')
                ->with($dashboard)
                ->once();

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $service->deleteDashboard($dashboardId);

            expect(true)->toBeTrue(); // Mockery validates the delete was called
        });

        test('throws DashboardNotFoundException when dashboard does not exist', function () {
            $dashboardId = Uuid::uuid4();

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')
                ->with($dashboardId)
                ->andThrow(DashboardNotFoundException::forId($dashboardId));

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            expect(fn() => $service->deleteDashboard($dashboardId))
                ->toThrow(DashboardNotFoundException::class);
        });

        test('wraps deletion in a transaction', function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')->andReturn($dashboard);
            $dashboardRepository->shouldReceive('delete');

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $service->deleteDashboard($dashboardId);

            expect(true)->toBeTrue(); // Mockery validates the transactional was called
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: create, list, update, and delete', function () {
            $dashboardId = Uuid::uuid4();
            $originalDashboard = TestEntityFactory::createDashboard(id: $dashboardId);
            $updatedDashboard = TestEntityFactory::createDashboard(id: $dashboardId);
            $collection = new DashboardCollection($originalDashboard);

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('save')->twice();
            $dashboardRepository->shouldReceive('findAll')->andReturn($collection);
            $dashboardRepository->shouldReceive('findById')
                ->with($dashboardId)
                ->andReturn($updatedDashboard);
            $dashboardRepository->shouldReceive('delete')->once();

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);
            $unitOfWork->shouldReceive('transactional')
                ->times(3)
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            // Create
            $created = $service->createDashboard('New Dashboard', 'Description');
            expect($created)->toBeInstanceOf(\jschreuder\BookmarkBureau\Entity\Dashboard::class);

            // List
            $all = $service->listAllDashboards();
            expect(count($all))->toBe(1);

            // Update
            $updated = $service->updateDashboard($dashboardId, 'Updated Dashboard', 'Updated Description');
            expect($updated)->toBeInstanceOf(\jschreuder\BookmarkBureau\Entity\Dashboard::class);

            // Delete
            $service->deleteDashboard($dashboardId);

            expect(true)->toBeTrue();
        });

        test('getFullDashboard workflow with multiple categories and links', function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $category1 = TestEntityFactory::createCategory(dashboard: $dashboard);
            $category2 = TestEntityFactory::createCategory(dashboard: $dashboard);
            $categories = new CategoryCollection($category1, $category2);

            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $link3 = TestEntityFactory::createLink();

            $categoryLinks1 = new CategoryLinkCollection(
                TestEntityFactory::createCategoryLink(category: $category1, link: $link1),
                TestEntityFactory::createCategoryLink(category: $category1, link: $link2)
            );
            $categoryLinks2 = new CategoryLinkCollection(
                TestEntityFactory::createCategoryLink(category: $category2, link: $link3)
            );

            $favorites = new FavoriteCollection(
                TestEntityFactory::createFavorite(dashboard: $dashboard, link: $link1)
            );

            $dashboardRepository = Mockery::mock(DashboardRepositoryInterface::class);
            $dashboardRepository->shouldReceive('findById')
                ->with($dashboardId)
                ->andReturn($dashboard);

            $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
            $categoryRepository->shouldReceive('findByDashboardId')
                ->with($dashboardId)
                ->andReturn($categories);
            $categoryRepository->shouldReceive('findCategoryLinksForCategoryId')
                ->with($category1->categoryId)
                ->andReturn($categoryLinks1);
            $categoryRepository->shouldReceive('findCategoryLinksForCategoryId')
                ->with($category2->categoryId)
                ->andReturn($categoryLinks2);

            $favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);
            $favoriteRepository->shouldReceive('findByDashboardId')
                ->with($dashboardId)
                ->andReturn($favorites);

            $unitOfWork = Mockery::mock(UnitOfWorkInterface::class);

            $service = new DashboardService($dashboardRepository, $categoryRepository, $favoriteRepository, $unitOfWork);

            $result = $service->getFullDashboard($dashboardId);

            expect($result->dashboard->dashboardId)->toEqual($dashboardId);
            expect(count($result->categories))->toBe(2);
            expect(count($result->favorites))->toBe(1);
        });
    });
});
