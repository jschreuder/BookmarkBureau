<?php

use jschreuder\BookmarkBureau\Action\CategoryReorderAction;
use jschreuder\BookmarkBureau\Composite\CategoryCollection;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\InputSpec\ReorderCategoriesInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\CategoryOutputSpec;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe("CategoryReorderAction", function () {
    describe("getAttributeKeysForData method", function () {
        test("returns only dashboard_id for reorder action", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new ReorderCategoriesInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReorderAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            expect($action->getAttributeKeysForData())->toBe(["dashboard_id"]);
        });
    });

    describe("filter method", function () {
        test("filters dashboard_id and categories", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $categoryId = Uuid::uuid4()->toString();

            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReorderAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([
                "dashboard_id" => "  {$dashboardId}  ",
                "categories" => [
                    ["category_id" => "  {$categoryId}  ", "sort_order" => 1],
                ],
            ]);

            expect($filtered["dashboard_id"])->toBe($dashboardId);
            expect($filtered["categories"][0]["category_id"])->toBe(
                $categoryId,
            );
        });
    });

    describe("validate method", function () {
        test("passes validation with valid data", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();
            $categoryId = Uuid::uuid4()->toString();

            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReorderAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            try {
                $action->validate([
                    "dashboard_id" => $dashboardId,
                    "categories" => [
                        ["category_id" => $categoryId, "sort_order" => 1],
                    ],
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error when categories empty", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new ReorderCategoriesInputSpec();
            $dashboardId = Uuid::uuid4()->toString();

            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReorderAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            expect(function () use ($action, $dashboardId) {
                $action->validate([
                    "dashboard_id" => $dashboardId,
                    "categories" => [],
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for invalid dashboard_id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new ReorderCategoriesInputSpec();
            $categoryId = Uuid::uuid4()->toString();

            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReorderAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            expect(function () use ($action, $categoryId) {
                $action->validate([
                    "dashboard_id" => "invalid-uuid",
                    "categories" => [
                        ["category_id" => $categoryId, "sort_order" => 1],
                    ],
                ]);
            })->toThrow(ValidationFailedException::class);
        });
    });

    describe("execute method", function () {
        test(
            "calls categoryService.reorderCategories with correct parameters",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $inputSpec = new ReorderCategoriesInputSpec();
                $category1 = TestEntityFactory::createCategory(
                    title: new Title("First"),
                );
                $category2 = TestEntityFactory::createCategory(
                    title: new Title("Second"),
                );
                $collection = new CategoryCollection($category1, $category2);

                $dashboardId = Uuid::uuid4();
                $categoryId1 = $category1->categoryId->toString();
                $categoryId2 = $category2->categoryId->toString();

                $categoryService
                    ->shouldReceive("getCategoriesForDashboard")
                    ->with(Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                    ->andReturn($collection);

                $categoryService
                    ->shouldReceive("reorderCategories")
                    ->with(
                        Mockery::on(
                            fn($arg) => $arg->toString() ===
                                $dashboardId->toString(),
                        ),
                        \Mockery::type(CategoryCollection::class),
                    );

                $outputSpec = new CategoryOutputSpec();
                $action = new CategoryReorderAction(
                    $categoryService,
                    $inputSpec,
                    $outputSpec,
                );

                $action->execute([
                    "dashboard_id" => $dashboardId->toString(),
                    "categories" => [
                        ["category_id" => $categoryId1, "sort_order" => 1],
                        ["category_id" => $categoryId2, "sort_order" => 2],
                    ],
                ]);

                expect(true)->toBeTrue();
            },
        );

        test("returns array of transformed categories", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new ReorderCategoriesInputSpec();
            $category1 = TestEntityFactory::createCategory(
                title: new Title("First Category"),
            );
            $category2 = TestEntityFactory::createCategory(
                title: new Title("Second Category"),
            );
            $collection = new CategoryCollection($category1, $category2);

            $dashboardId = Uuid::uuid4();

            $categoryService
                ->shouldReceive("getCategoriesForDashboard")
                ->andReturn($collection);

            $categoryService->shouldReceive("reorderCategories");

            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReorderAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "dashboard_id" => $dashboardId->toString(),
                "categories" => [
                    [
                        "category_id" => $category1->categoryId->toString(),
                        "sort_order" => 1,
                    ],
                    [
                        "category_id" => $category2->categoryId->toString(),
                        "sort_order" => 2,
                    ],
                ],
            ]);

            expect($result["categories"])->toHaveCount(2);
            expect($result["categories"][0])->toHaveKey("category_id");
            expect($result["categories"][0])->toHaveKey("title");
            expect($result["categories"][1])->toHaveKey("category_id");
            expect($result["categories"][1])->toHaveKey("title");
        });

        test(
            "sorts categories by sort_order regardless of input order",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $inputSpec = new ReorderCategoriesInputSpec();
                $category1 = TestEntityFactory::createCategory(
                    title: new Title("First Category"),
                );
                $category2 = TestEntityFactory::createCategory(
                    title: new Title("Second Category"),
                );
                $category3 = TestEntityFactory::createCategory(
                    title: new Title("Third Category"),
                );
                $collection = new CategoryCollection(
                    $category1,
                    $category2,
                    $category3,
                );

                $dashboardId = Uuid::uuid4();

                $categoryService
                    ->shouldReceive("getCategoriesForDashboard")
                    ->andReturn($collection);

                // Verify that the CategoryCollection passed to reorderCategories is in correct order
                $categoryService->shouldReceive("reorderCategories")->with(
                    Mockery::any(),
                    Mockery::on(function (CategoryCollection $categories) use (
                        $category1,
                        $category2,
                        $category3,
                    ) {
                        $categoriesArray = iterator_to_array($categories);
                        return count($categoriesArray) === 3 &&
                            $categoriesArray[0]->categoryId->equals(
                                $category1->categoryId,
                            ) &&
                            $categoriesArray[1]->categoryId->equals(
                                $category2->categoryId,
                            ) &&
                            $categoriesArray[2]->categoryId->equals(
                                $category3->categoryId,
                            );
                    }),
                );

                $outputSpec = new CategoryOutputSpec();
                $action = new CategoryReorderAction(
                    $categoryService,
                    $inputSpec,
                    $outputSpec,
                );

                // Send categories in REVERSE order but with correct sort_order values
                $result = $action->execute([
                    "dashboard_id" => $dashboardId->toString(),
                    "categories" => [
                        [
                            "category_id" => $category3->categoryId->toString(),
                            "sort_order" => 3,
                        ],
                        [
                            "category_id" => $category1->categoryId->toString(),
                            "sort_order" => 1,
                        ],
                        [
                            "category_id" => $category2->categoryId->toString(),
                            "sort_order" => 2,
                        ],
                    ],
                ]);

                // Verify the output is in the correct order (by sort_order, not input order)
                expect($result["categories"][0]["title"])->toBe(
                    "First Category",
                );
                expect($result["categories"][1]["title"])->toBe(
                    "Second Category",
                );
                expect($result["categories"][2]["title"])->toBe(
                    "Third Category",
                );
            },
        );

        test(
            "throws CategoryNotFoundException for category not in dashboard",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $inputSpec = new ReorderCategoriesInputSpec();
                $category1 = TestEntityFactory::createCategory();
                $collection = new CategoryCollection($category1);

                $dashboardId = Uuid::uuid4();

                $categoryService
                    ->shouldReceive("getCategoriesForDashboard")
                    ->andReturn($collection);

                $outputSpec = new CategoryOutputSpec();
                $action = new CategoryReorderAction(
                    $categoryService,
                    $inputSpec,
                    $outputSpec,
                );

                $invalidCategoryId = Uuid::uuid4()->toString();

                expect(function () use (
                    $action,
                    $dashboardId,
                    $category1,
                    $invalidCategoryId,
                ) {
                    $action->execute([
                        "dashboard_id" => $dashboardId->toString(),
                        "categories" => [
                            [
                                "category_id" => $category1->categoryId->toString(),
                                "sort_order" => 1,
                            ],
                            [
                                "category_id" => $invalidCategoryId,
                                "sort_order" => 2,
                            ],
                        ],
                    ]);
                })->toThrow(CategoryNotFoundException::class);
            },
        );
    });

    describe("integration scenarios", function () {
        test("full workflow: filter, validate, and execute", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new ReorderCategoriesInputSpec();
            $category1 = TestEntityFactory::createCategory();
            $category2 = TestEntityFactory::createCategory();
            $collection = new CategoryCollection($category1, $category2);

            $categoryService
                ->shouldReceive("getCategoriesForDashboard")
                ->andReturn($collection);

            $categoryService->shouldReceive("reorderCategories");

            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReorderAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $dashboardId = Uuid::uuid4()->toString();
            $categoryId1 = $category1->categoryId->toString();
            $categoryId2 = $category2->categoryId->toString();

            $rawData = [
                "dashboard_id" => "  {$dashboardId}  ",
                "categories" => [
                    ["category_id" => "  {$categoryId1}  ", "sort_order" => 1],
                    ["category_id" => "  {$categoryId2}  ", "sort_order" => 2],
                ],
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result["categories"])->toHaveCount(2);
                expect($result["categories"][0])->toHaveKey("category_id");
                expect($result["categories"][1])->toHaveKey("category_id");
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });
});
