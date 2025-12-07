<?php

use jschreuder\BookmarkBureau\Action\CategoryUpdateAction;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\InputSpec\CategoryInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\CategoryOutputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

describe("CategoryUpdateAction", function () {
    describe("filter method", function () {
        test("trims whitespace from id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();

            $filtered = $action->filter([
                "category_id" => "  {$categoryId->toString()}  ",
                "dashboard_id" => Uuid::uuid4()->toString(),
                "title" => "Test Category",
                "color" => null,
                "sort_order" => 1,
            ]);

            expect($filtered["category_id"])->toBe($categoryId->toString());
        });

        test("trims whitespace from title", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();

            $filtered = $action->filter([
                "category_id" => $categoryId->toString(),
                "dashboard_id" => Uuid::uuid4()->toString(),
                "title" => "  Test Category  ",
                "color" => null,
                "sort_order" => 1,
            ]);

            expect($filtered["title"])->toBe("Test Category");
        });

        test("trims whitespace from color", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();

            $filtered = $action->filter([
                "category_id" => $categoryId->toString(),
                "dashboard_id" => Uuid::uuid4()->toString(),
                "title" => "Test Category",
                "color" => "  #FF0000  ",
                "sort_order" => 1,
            ]);

            expect($filtered["color"])->toBe("#FF0000");
        });

        test("handles missing keys with appropriate defaults", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([]);

            expect($filtered["category_id"])->toBe("");
            expect($filtered["dashboard_id"])->toBe("");
            expect($filtered["title"])->toBe("");
            expect($filtered["color"])->toBeNull();
            expect($filtered["sort_order"])->toBe(1);
        });

        test("preserves null color as null", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();

            $filtered = $action->filter([
                "category_id" => $categoryId->toString(),
                "dashboard_id" => Uuid::uuid4()->toString(),
                "title" => "Test Category",
                "color" => null,
                "sort_order" => 1,
            ]);

            expect($filtered["color"])->toBeNull();
        });
    });

    describe("validate method", function () {
        test("passes validation with valid data", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();
            $dashboardId = Uuid::uuid4();

            $data = [
                "category_id" => $categoryId->toString(),
                "dashboard_id" => $dashboardId->toString(),
                "title" => "Test Category",
                "color" => "#FF0000",
                "sort_order" => 1,
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("passes validation with null color", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();
            $dashboardId = Uuid::uuid4();

            $data = [
                "category_id" => $categoryId->toString(),
                "dashboard_id" => $dashboardId->toString(),
                "title" => "Test Category",
                "color" => null,
                "sort_order" => 1,
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for invalid id UUID", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "category_id" => "not-a-uuid",
                "dashboard_id" => Uuid::uuid4()->toString(),
                "title" => "Test Category",
                "color" => null,
                "sort_order" => 1,
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for empty id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "category_id" => "",
                "dashboard_id" => Uuid::uuid4()->toString(),
                "title" => "Test Category",
                "color" => null,
                "sort_order" => 1,
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for empty title", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();

            $data = [
                "category_id" => $categoryId->toString(),
                "dashboard_id" => Uuid::uuid4()->toString(),
                "title" => "",
                "color" => null,
                "sort_order" => 1,
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test(
            "throws validation error for title exceeding max length",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $inputSpec = new CategoryInputSpec();
                $outputSpec = new CategoryOutputSpec();
                $action = new CategoryUpdateAction(
                    $categoryService,
                    $inputSpec,
                    $outputSpec,
                );
                $categoryId = Uuid::uuid4();

                $data = [
                    "category_id" => $categoryId->toString(),
                    "dashboard_id" => Uuid::uuid4()->toString(),
                    "title" => str_repeat("a", 257),
                    "color" => null,
                    "sort_order" => 1,
                ];

                expect(fn() => $action->validate($data))->toThrow(
                    ValidationFailedException::class,
                );
            },
        );

        test("throws validation error for invalid color format", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();

            $data = [
                "category_id" => $categoryId->toString(),
                "dashboard_id" => Uuid::uuid4()->toString(),
                "title" => "Test Category",
                "color" => "invalid-color",
                "sort_order" => 1,
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("includes multiple validation errors", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "category_id" => "not-uuid",
                "dashboard_id" => "not-uuid",
                "title" => "",
                "color" => null,
                "sort_order" => 1,
            ];

            try {
                $action->validate($data);
                expect(true)->toBeFalse();
            } catch (ValidationFailedException $e) {
                $errors = $e->getValidationErrors();
                expect($errors)->toHaveKey("title");
            }
        });
    });

    describe("execute method", function () {
        test(
            "executes with valid data and returns formatted category",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $categoryId = Uuid::uuid4();
                $dashboardId = Uuid::uuid4();
                $category = TestEntityFactory::createCategory(
                    id: $categoryId,
                    dashboard: TestEntityFactory::createDashboard(
                        id: $dashboardId,
                    ),
                    color: new HexColor("#FF0000"),
                );

                $categoryService
                    ->shouldReceive("updateCategory")
                    ->with(
                        Mockery::type(UuidInterface::class),
                        "Test Category",
                        "#FF0000",
                    )
                    ->andReturn($category);

                $inputSpec = new CategoryInputSpec();
                $outputSpec = new CategoryOutputSpec();
                $action = new CategoryUpdateAction(
                    $categoryService,
                    $inputSpec,
                    $outputSpec,
                );

                $result = $action->execute([
                    "category_id" => $categoryId->toString(),
                    "dashboard_id" => $dashboardId->toString(),
                    "title" => "Test Category",
                    "color" => "#FF0000",
                    "sort_order" => 1,
                ]);

                expect($result)->toHaveKey("category_id");
                expect($result)->toHaveKey("dashboard_id");
                expect($result)->toHaveKey("title");
                expect($result)->toHaveKey("color");
                expect($result)->toHaveKey("sort_order");
                expect($result)->toHaveKey("created_at");
                expect($result)->toHaveKey("updated_at");
            },
        );

        test(
            "returns created_at and updated_at in ISO 8601 format",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $categoryId = Uuid::uuid4();
                $dashboardId = Uuid::uuid4();
                $category = TestEntityFactory::createCategory(
                    id: $categoryId,
                    dashboard: TestEntityFactory::createDashboard(
                        id: $dashboardId,
                    ),
                    color: new HexColor("#FF0000"),
                );

                $categoryService
                    ->shouldReceive("updateCategory")
                    ->andReturn($category);

                $inputSpec = new CategoryInputSpec();
                $outputSpec = new CategoryOutputSpec();
                $action = new CategoryUpdateAction(
                    $categoryService,
                    $inputSpec,
                    $outputSpec,
                );

                $result = $action->execute([
                    "category_id" => $categoryId->toString(),
                    "dashboard_id" => $dashboardId->toString(),
                    "title" => "Test Category",
                    "color" => "#FF0000",
                    "sort_order" => 1,
                ]);

                expect($result["created_at"])->toMatch(
                    "/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/",
                );
                expect($result["updated_at"])->toMatch(
                    "/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/",
                );
            },
        );

        test(
            "returns correct category service parameters with color",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $categoryId = Uuid::uuid4();
                $dashboardId = Uuid::uuid4();
                $category = TestEntityFactory::createCategory(
                    id: $categoryId,
                    dashboard: TestEntityFactory::createDashboard(
                        id: $dashboardId,
                    ),
                    color: new HexColor("#FF0000"),
                );

                $categoryService
                    ->shouldReceive("updateCategory")
                    ->with(
                        Mockery::type(UuidInterface::class),
                        "Test Category",
                        "#FF0000",
                    )
                    ->andReturn($category);

                $inputSpec = new CategoryInputSpec();
                $outputSpec = new CategoryOutputSpec();
                $action = new CategoryUpdateAction(
                    $categoryService,
                    $inputSpec,
                    $outputSpec,
                );

                $action->execute([
                    "category_id" => $categoryId->toString(),
                    "dashboard_id" => $dashboardId->toString(),
                    "title" => "Test Category",
                    "color" => "#FF0000",
                    "sort_order" => 1,
                ]);

                expect(true)->toBeTrue(); // Mockery validates the call was made correctly
            },
        );

        test(
            "returns correct category service parameters with null color",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $categoryId = Uuid::uuid4();
                $dashboardId = Uuid::uuid4();
                $category = TestEntityFactory::createCategory(
                    id: $categoryId,
                    dashboard: TestEntityFactory::createDashboard(
                        id: $dashboardId,
                    ),
                    color: null,
                );

                $categoryService
                    ->shouldReceive("updateCategory")
                    ->with(
                        Mockery::type(UuidInterface::class),
                        "Test Category",
                        null,
                    )
                    ->andReturn($category);

                $inputSpec = new CategoryInputSpec();
                $outputSpec = new CategoryOutputSpec();
                $action = new CategoryUpdateAction(
                    $categoryService,
                    $inputSpec,
                    $outputSpec,
                );

                $action->execute([
                    "category_id" => $categoryId->toString(),
                    "dashboard_id" => $dashboardId->toString(),
                    "title" => "Test Category",
                    "color" => null,
                    "sort_order" => 1,
                ]);

                expect(true)->toBeTrue();
            },
        );

        test(
            "converts string id to UUID before passing to service",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $categoryId = Uuid::uuid4();
                $category = TestEntityFactory::createCategory(id: $categoryId);

                $categoryService
                    ->shouldReceive("updateCategory")
                    ->with(
                        Mockery::type(UuidInterface::class),
                        Mockery::any(),
                        Mockery::any(),
                    )
                    ->andReturn($category);

                $inputSpec = new CategoryInputSpec();
                $outputSpec = new CategoryOutputSpec();
                $action = new CategoryUpdateAction(
                    $categoryService,
                    $inputSpec,
                    $outputSpec,
                );

                $action->execute([
                    "category_id" => $categoryId->toString(),
                    "dashboard_id" => Uuid::uuid4()->toString(),
                    "title" => "Test Category",
                    "color" => null,
                    "sort_order" => 1,
                ]);

                expect(true)->toBeTrue();
            },
        );
    });

    describe("integration scenarios", function () {
        test("full workflow: filter, validate, and execute", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $dashboardId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(
                id: $categoryId,
                dashboard: TestEntityFactory::createDashboard(id: $dashboardId),
                color: new HexColor("#FF0000"),
            );

            $categoryService
                ->shouldReceive("updateCategory")
                ->with(
                    Mockery::type(UuidInterface::class),
                    "Test Category",
                    "#FF0000",
                )
                ->andReturn($category);

            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "category_id" => "  {$categoryId->toString()}  ",
                "dashboard_id" => "  {$dashboardId->toString()}  ",
                "title" => "  Test Category  ",
                "color" => "  #FF0000  ",
                "sort_order" => 1,
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toHaveKey("category_id");
                expect($result)->toHaveKey("title");
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("full workflow with null color", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $dashboardId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(
                id: $categoryId,
                dashboard: TestEntityFactory::createDashboard(id: $dashboardId),
                color: null,
            );

            $categoryService
                ->shouldReceive("updateCategory")
                ->with(
                    Mockery::type(UuidInterface::class),
                    "Test Category",
                    null,
                )
                ->andReturn($category);

            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "category_id" => $categoryId->toString(),
                "dashboard_id" => $dashboardId->toString(),
                "title" => "Test Category",
                "color" => null,
                "sort_order" => 1,
            ];

            $filtered = $action->filter($rawData);
            expect($filtered["color"])->toBeNull();

            try {
                $action->validate($filtered);
                $action->execute($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("full workflow filters and validates id correctly", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $dashboardId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(id: $categoryId);

            $categoryService
                ->shouldReceive("updateCategory")
                ->andReturn($category);

            $inputSpec = new CategoryInputSpec();
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryUpdateAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "category_id" => "  {$categoryId->toString()}  ",
                "dashboard_id" => $dashboardId->toString(),
                "title" => "Test Category",
                "color" => null,
                "sort_order" => 1,
            ];

            $filtered = $action->filter($rawData);
            expect($filtered["category_id"])->toBe($categoryId->toString());

            $action->validate($filtered);
            $action->execute($filtered);

            expect(true)->toBeTrue();
        });
    });
});
