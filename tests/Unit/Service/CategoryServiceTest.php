<?php

use jschreuder\BookmarkBureau\Composite\CategoryCollection;
use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Service\CategoryService;
use jschreuder\BookmarkBureau\Service\CategoryServicePipelines;
use Ramsey\Uuid\Uuid;

describe("CategoryService", function () {
    describe("getCategory method", function () {
        test("retrieves an existing category", function () {
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(id: $categoryId);

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("findById")
                ->with($categoryId)
                ->andReturn($category)
                ->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $result = $service->getCategory($categoryId);

            expect($result)->toBe($category);
            expect($result->categoryId)->toEqual($categoryId);
        });

        test(
            "throws CategoryNotFoundException when category does not exist",
            function () {
                $categoryId = Uuid::uuid4();

                $categoryRepository = Mockery::mock(
                    CategoryRepositoryInterface::class,
                );
                $categoryRepository
                    ->shouldReceive("findById")
                    ->with($categoryId)
                    ->andThrow(CategoryNotFoundException::forId($categoryId));

                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $pipelines = new CategoryServicePipelines();

                $service = new CategoryService(
                    $categoryRepository,
                    $dashboardRepository,
                    $pipelines,
                );

                expect(fn() => $service->getCategory($categoryId))->toThrow(
                    CategoryNotFoundException::class,
                );
            },
        );
    });

    describe("createCategory method", function () {
        test("creates a new category with title and color", function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("getMaxSortOrderForDashboardId")
                ->with($dashboardId)
                ->andReturn(2);
            $categoryRepository->shouldReceive("save")->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $dashboardRepository
                ->shouldReceive("findById")
                ->with($dashboardId)
                ->andReturn($dashboard);

            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $result = $service->createCategory(
                $dashboardId,
                "New Category",
                "#FF5733",
            );

            expect($result)->toBeInstanceOf(Category::class);
            expect($result->title->value)->toBe("New Category");
            expect($result->color?->value)->toBe("#FF5733");
            expect($result->sortOrder)->toBe(3);
            expect($result->dashboard->dashboardId)->toEqual($dashboardId);
        });

        test("creates a new category without color", function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("getMaxSortOrderForDashboardId")
                ->with($dashboardId)
                ->andReturn(-1);
            $categoryRepository->shouldReceive("save")->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $dashboardRepository
                ->shouldReceive("findById")
                ->with($dashboardId)
                ->andReturn($dashboard);

            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $result = $service->createCategory($dashboardId, "New Category");

            expect($result->color)->toBeNull();
            expect($result->sortOrder)->toBe(0);
        });

        test(
            "throws DashboardNotFoundException when dashboard does not exist",
            function () {
                $dashboardId = Uuid::uuid4();

                $categoryRepository = Mockery::mock(
                    CategoryRepositoryInterface::class,
                );
                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $dashboardRepository
                    ->shouldReceive("findById")
                    ->with($dashboardId)
                    ->andThrow(DashboardNotFoundException::forId($dashboardId));

                $pipelines = new CategoryServicePipelines();

                $service = new CategoryService(
                    $categoryRepository,
                    $dashboardRepository,
                    $pipelines,
                );

                expect(
                    fn() => $service->createCategory($dashboardId, "Category"),
                )->toThrow(DashboardNotFoundException::class);
            },
        );

        test("rolls back transaction on invalid color", function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("getMaxSortOrderForDashboardId")
                ->andReturn(-1);

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $dashboardRepository
                ->shouldReceive("findById")
                ->andReturn($dashboard);

            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            // Invalid hex color format should throw InvalidArgumentException
            expect(
                fn() => $service->createCategory(
                    $dashboardId,
                    "New Category",
                    "invalid-color",
                ),
            )->toThrow(InvalidArgumentException::class);
        });
    });

    describe("updateCategory method", function () {
        test("updates an existing category with title and color", function () {
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(id: $categoryId);

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("findById")
                ->with($categoryId)
                ->andReturn($category);
            $categoryRepository->shouldReceive("save")->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $result = $service->updateCategory(
                $categoryId,
                "Updated Category",
                "#33FF57",
            );

            expect($result->title->value)->toBe("Updated Category");
            expect($result->color?->value)->toBe("#33FF57");
        });

        test("updates category without color", function () {
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(
                id: $categoryId,
                color: new HexColor("#FF5733"),
            );

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("findById")
                ->with($categoryId)
                ->andReturn($category);
            $categoryRepository->shouldReceive("save")->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $result = $service->updateCategory(
                $categoryId,
                "Updated Category",
                null,
            );

            expect($result->color)->toBeNull();
        });

        test(
            "throws CategoryNotFoundException when category does not exist",
            function () {
                $categoryId = Uuid::uuid4();

                $categoryRepository = Mockery::mock(
                    CategoryRepositoryInterface::class,
                );
                $categoryRepository
                    ->shouldReceive("findById")
                    ->with($categoryId)
                    ->andThrow(CategoryNotFoundException::forId($categoryId));

                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $pipelines = new CategoryServicePipelines();

                $service = new CategoryService(
                    $categoryRepository,
                    $dashboardRepository,
                    $pipelines,
                );

                expect(
                    fn() => $service->updateCategory(
                        $categoryId,
                        "Updated",
                        "#FF5733",
                    ),
                )->toThrow(CategoryNotFoundException::class);
            },
        );
    });

    describe("deleteCategory method", function () {
        test("deletes an existing category", function () {
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(id: $categoryId);

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("findById")
                ->with($categoryId)
                ->andReturn($category);
            $categoryRepository
                ->shouldReceive("delete")
                ->with($category)
                ->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $service->deleteCategory($categoryId);

            expect(true)->toBeTrue();
        });

        test(
            "throws CategoryNotFoundException when category does not exist",
            function () {
                $categoryId = Uuid::uuid4();

                $categoryRepository = Mockery::mock(
                    CategoryRepositoryInterface::class,
                );
                $categoryRepository
                    ->shouldReceive("findById")
                    ->with($categoryId)
                    ->andThrow(CategoryNotFoundException::forId($categoryId));

                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $pipelines = new CategoryServicePipelines();

                $service = new CategoryService(
                    $categoryRepository,
                    $dashboardRepository,
                    $pipelines,
                );

                expect(fn() => $service->deleteCategory($categoryId))->toThrow(
                    CategoryNotFoundException::class,
                );
            },
        );
    });

    describe("reorderCategories method", function () {
        test("reorders categories within a dashboard", function () {
            $dashboardId = Uuid::uuid4();
            $category1 = TestEntityFactory::createCategory(sortOrder: 0);
            $category2 = TestEntityFactory::createCategory(sortOrder: 1);
            $category3 = TestEntityFactory::createCategory(sortOrder: 2);
            $categories = new CategoryCollection(
                $category1,
                $category2,
                $category3,
            );

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("findByDashboardId")
                ->with($dashboardId)
                ->andReturn($categories);
            $categoryRepository->shouldReceive("save")->times(3);

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $categoryIdToSortOrder = [
                $category1->categoryId->toString() => 2,
                $category2->categoryId->toString() => 0,
                $category3->categoryId->toString() => 1,
            ];

            $service->reorderCategories($dashboardId, $categoryIdToSortOrder);

            expect($category1->sortOrder)->toBe(2);
            expect($category2->sortOrder)->toBe(0);
            expect($category3->sortOrder)->toBe(1);
        });

        test("ignores unmapped categories", function () {
            $dashboardId = Uuid::uuid4();
            $category1 = TestEntityFactory::createCategory(sortOrder: 0);
            $category2 = TestEntityFactory::createCategory(sortOrder: 1);
            $categories = new CategoryCollection($category1, $category2);

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("findByDashboardId")
                ->with($dashboardId)
                ->andReturn($categories);
            $categoryRepository->shouldReceive("save")->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $categoryIdToSortOrder = [
                $category1->categoryId->toString() => 5,
            ];

            $service->reorderCategories($dashboardId, $categoryIdToSortOrder);

            expect($category1->sortOrder)->toBe(5);
            expect($category2->sortOrder)->toBe(1);
        });
    });

    describe("addLinkToCategory method", function () {
        test("adds a link to a category with correct sort order", function () {
            $categoryId = Uuid::uuid4();
            $linkId = Uuid::uuid4();
            $categoryLink = TestEntityFactory::createCategoryLink();

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("findById")
                ->with($categoryId)
                ->andReturn(TestEntityFactory::createCategory());
            $categoryRepository
                ->shouldReceive("getMaxSortOrderForCategoryId")
                ->with($categoryId)
                ->andReturn(4);
            $categoryRepository
                ->shouldReceive("addLink")
                ->with($categoryId, $linkId, 5)
                ->andReturn($categoryLink)
                ->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $result = $service->addLinkToCategory($categoryId, $linkId);

            expect($result)->toBe($categoryLink);
        });

        test("adds link to empty category", function () {
            $categoryId = Uuid::uuid4();
            $linkId = Uuid::uuid4();
            $categoryLink = TestEntityFactory::createCategoryLink();

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("findById")
                ->with($categoryId)
                ->andReturn(TestEntityFactory::createCategory());
            $categoryRepository
                ->shouldReceive("getMaxSortOrderForCategoryId")
                ->with($categoryId)
                ->andReturn(-1);
            $categoryRepository
                ->shouldReceive("addLink")
                ->with($categoryId, $linkId, 0)
                ->andReturn($categoryLink)
                ->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $result = $service->addLinkToCategory($categoryId, $linkId);

            expect($result)->toBe($categoryLink);
        });

        test(
            "throws CategoryNotFoundException when category does not exist",
            function () {
                $categoryId = Uuid::uuid4();
                $linkId = Uuid::uuid4();

                $categoryRepository = Mockery::mock(
                    CategoryRepositoryInterface::class,
                );
                $categoryRepository
                    ->shouldReceive("findById")
                    ->with($categoryId)
                    ->andThrow(CategoryNotFoundException::forId($categoryId));

                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $pipelines = new CategoryServicePipelines();

                $service = new CategoryService(
                    $categoryRepository,
                    $dashboardRepository,
                    $pipelines,
                );

                expect(
                    fn() => $service->addLinkToCategory($categoryId, $linkId),
                )->toThrow(CategoryNotFoundException::class);
            },
        );

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $categoryId = Uuid::uuid4();
                $linkId = Uuid::uuid4();

                $categoryRepository = Mockery::mock(
                    CategoryRepositoryInterface::class,
                );
                $categoryRepository
                    ->shouldReceive("findById")
                    ->with($categoryId)
                    ->andReturn(TestEntityFactory::createCategory());
                $categoryRepository
                    ->shouldReceive("getMaxSortOrderForCategoryId")
                    ->with($categoryId)
                    ->andReturn(-1);
                $categoryRepository
                    ->shouldReceive("addLink")
                    ->with($categoryId, $linkId, 0)
                    ->andThrow(LinkNotFoundException::class);

                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $pipelines = new CategoryServicePipelines();

                $service = new CategoryService(
                    $categoryRepository,
                    $dashboardRepository,
                    $pipelines,
                );

                expect(
                    fn() => $service->addLinkToCategory($categoryId, $linkId),
                )->toThrow(LinkNotFoundException::class);
            },
        );
    });

    describe("removeLinkFromCategory method", function () {
        test("removes a link from a category", function () {
            $categoryId = Uuid::uuid4();
            $linkId = Uuid::uuid4();

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("removeLink")
                ->with($categoryId, $linkId)
                ->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $service->removeLinkFromCategory($categoryId, $linkId);

            expect(true)->toBeTrue();
        });
    });

    describe("reorderLinksInCategory method", function () {
        test("reorders links within a category", function () {
            $categoryId = Uuid::uuid4();
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $links = new LinkCollection($link1, $link2);

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("reorderLinks")
                ->with($categoryId, $links)
                ->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $service->reorderLinksInCategory($categoryId, $links);

            expect(true)->toBeTrue();
        });

        test("handles empty link collection", function () {
            $categoryId = Uuid::uuid4();
            $links = new LinkCollection();

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );
            $categoryRepository
                ->shouldReceive("reorderLinks")
                ->with($categoryId, $links)
                ->once();

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            $service->reorderLinksInCategory($categoryId, $links);

            expect(true)->toBeTrue();
        });
    });

    describe("integration scenarios", function () {
        test(
            "full workflow: create, update, add links, reorder, and delete",
            function () {
                $dashboardId = Uuid::uuid4();
                $dashboard = TestEntityFactory::createDashboard(
                    id: $dashboardId,
                );
                $categoryId = Uuid::uuid4();
                $category = TestEntityFactory::createCategory(
                    id: $categoryId,
                    dashboard: $dashboard,
                );
                $categoryLink = TestEntityFactory::createCategoryLink();

                $categoryRepository = Mockery::mock(
                    CategoryRepositoryInterface::class,
                );
                $categoryRepository
                    ->shouldReceive("getMaxSortOrderForDashboardId")
                    ->andReturn(-1);
                // 1 create + 1 update + 1 reorder = 3 save calls
                $categoryRepository->shouldReceive("save")->times(3);
                $categoryRepository
                    ->shouldReceive("findById")
                    ->andReturn($category);
                $categoryRepository
                    ->shouldReceive("findByDashboardId")
                    ->andReturn(new CategoryCollection($category));
                $categoryRepository
                    ->shouldReceive("getMaxSortOrderForCategoryId")
                    ->andReturn(-1);
                $categoryRepository
                    ->shouldReceive("addLink")
                    ->andReturn($categoryLink);
                $categoryRepository->shouldReceive("reorderLinks");
                $categoryRepository->shouldReceive("delete");

                $dashboardRepository = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $dashboardRepository
                    ->shouldReceive("findById")
                    ->andReturn($dashboard);

                $pipelines = new CategoryServicePipelines();

                $service = new CategoryService(
                    $categoryRepository,
                    $dashboardRepository,
                    $pipelines,
                );

                // Create
                $created = $service->createCategory(
                    $dashboardId,
                    "New Category",
                    "#FF5733",
                );
                expect($created->title->value)->toBe("New Category");

                // Update
                $updated = $service->updateCategory(
                    $categoryId,
                    "Updated Category",
                    "#33FF57",
                );
                expect($updated->title->value)->toBe("Updated Category");

                // Add link
                $service->addLinkToCategory($categoryId, Uuid::uuid4());

                // Reorder categories
                $service->reorderCategories($dashboardId, [
                    $categoryId->toString() => 5,
                ]);

                // Reorder links
                $links = new LinkCollection(TestEntityFactory::createLink());
                $service->reorderLinksInCategory($categoryId, $links);

                // Delete
                $service->deleteCategory($categoryId);

                expect(true)->toBeTrue();
            },
        );

        test("multiple categories management workflow", function () {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $category1 = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                sortOrder: 0,
            );
            $category2 = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                sortOrder: 1,
            );

            $categoryRepository = Mockery::mock(
                CategoryRepositoryInterface::class,
            );

            // First call returns 1 (max sort order for existing categories)
            // Second call returns 2 (after first create)
            // Third call returns the same value for reorder (doesn't increment)
            $categoryRepository
                ->shouldReceive("getMaxSortOrderForDashboardId")
                ->with($dashboardId)
                ->andReturn(1, 2);

            // 2 save calls: one for create1, one for create2, two for reorder = 4 total
            $categoryRepository->shouldReceive("save")->times(4);

            // For reorder, return the categories
            $categories = new CategoryCollection($category1, $category2);
            $categoryRepository
                ->shouldReceive("findByDashboardId")
                ->with($dashboardId)
                ->andReturn($categories);

            $dashboardRepository = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $dashboardRepository
                ->shouldReceive("findById")
                ->andReturn($dashboard);

            $pipelines = new CategoryServicePipelines();

            $service = new CategoryService(
                $categoryRepository,
                $dashboardRepository,
                $pipelines,
            );

            // Create two more categories
            $created1 = $service->createCategory(
                $dashboardId,
                "Category 3",
                "#FF5733",
            );
            expect($created1->sortOrder)->toBe(2);

            $created2 = $service->createCategory(
                $dashboardId,
                "Category 4",
                "#33FF57",
            );
            expect($created2->sortOrder)->toBe(3);

            // Reorder all categories
            $reorderMap = [
                $category1->categoryId->toString() => 1,
                $category2->categoryId->toString() => 0,
            ];
            $service->reorderCategories($dashboardId, $reorderMap);

            expect($category1->sortOrder)->toBe(1);
            expect($category2->sortOrder)->toBe(0);
        });
    });
});
