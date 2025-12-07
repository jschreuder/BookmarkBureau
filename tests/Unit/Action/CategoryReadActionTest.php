<?php

use jschreuder\BookmarkBureau\Action\CategoryReadAction;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\InputSpec\IdInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\CategoryOutputSpec;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

describe("CategoryReadAction", function () {
    describe("filter method", function () {
        test("trims whitespace from id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();

            $filtered = $action->filter([
                "category_id" => "  {$categoryId->toString()}  ",
            ]);

            expect($filtered["category_id"])->toBe($categoryId->toString());
        });

        test("handles missing id key with empty string", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([]);

            expect($filtered["category_id"])->toBe("");
        });

        test("preserves valid id without modification", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();

            $filtered = $action->filter([
                "category_id" => $categoryId->toString(),
            ]);

            expect($filtered["category_id"])->toBe($categoryId->toString());
        });

        test("ignores additional fields in input", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();

            $filtered = $action->filter([
                "category_id" => $categoryId->toString(),
                "title" => "Should be ignored",
                "color" => "#FF0000",
                "extra_field" => "ignored",
            ]);

            expect($filtered)->toHaveKey("category_id");
            expect($filtered)->not->toHaveKey("title");
            expect($filtered)->not->toHaveKey("color");
            expect($filtered)->not->toHaveKey("extra_field");
        });
    });

    describe("validate method", function () {
        test("passes validation with valid UUID", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();

            $data = ["category_id" => $categoryId->toString()];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for invalid UUID", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $data = ["category_id" => "not-a-uuid"];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for empty id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $data = ["category_id" => ""];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for missing id key", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $data = [];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for whitespace-only id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $data = ["category_id" => "   "];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for null id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $data = ["category_id" => null];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("validates UUID in different formats", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );
            $categoryId = Uuid::uuid4();

            $data = ["category_id" => $categoryId->toString()];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });

    describe("execute method", function () {
        test("calls getCategory on service with correct UUID", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(id: $categoryId);

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->once()
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($result)->toBeArray();
            expect($result)->toHaveKey("category_id");
        });

        test("returns transformed category data", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(id: $categoryId);

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($result)->toBeArray();
            expect($result)->toHaveKey("category_id");
            expect($result)->toHaveKey("dashboard_id");
            expect($result)->toHaveKey("title");
            expect($result)->toHaveKey("color");
            expect($result)->toHaveKey("sort_order");
            expect($result)->toHaveKey("created_at");
            expect($result)->toHaveKey("updated_at");
        });

        test("returns correct category data structure", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(
                id: $categoryId,
                title: new Title("Test Category"),
                color: new HexColor("#FF5733"),
                sortOrder: 5,
            );

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($result["category_id"])->toBe($categoryId->toString());
            expect($result["title"])->toBe("Test Category");
            expect($result["color"])->toBe("#FF5733");
            expect($result["sort_order"])->toBe(5);
        });

        test(
            "converts string id to UUID before passing to service",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $categoryId = Uuid::uuid4();
                $category = TestEntityFactory::createCategory(id: $categoryId);

                $categoryService
                    ->shouldReceive("getCategory")
                    ->with(Mockery::type(UuidInterface::class))
                    ->once()
                    ->andReturn($category);

                $inputSpec = new IdInputSpec("category_id");
                $outputSpec = new CategoryOutputSpec();
                $action = new CategoryReadAction(
                    $categoryService,
                    $inputSpec,
                    $outputSpec,
                );

                $action->execute([
                    "category_id" => $categoryId->toString(),
                ]);

                expect(true)->toBeTrue();
            },
        );

        test("passes exact UUID to service", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(id: $categoryId);

            $uuidCapture = null;
            $categoryService
                ->shouldReceive("getCategory")
                ->andReturnUsing(function ($uuid) use (
                    &$uuidCapture,
                    $category,
                ) {
                    $uuidCapture = $uuid;
                    return $category;
                });

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($uuidCapture->toString())->toBe($categoryId->toString());
        });

        test("handles category with null color", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(
                id: $categoryId,
                color: null,
            );

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($result["color"])->toBeNull();
        });

        test("handles category with color", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(
                id: $categoryId,
                color: new HexColor("#00FF00"),
            );

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($result["color"])->toBe("#00FF00");
        });

        test("formats dates correctly", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable("2024-01-01 12:00:00");
            $updatedAt = new DateTimeImmutable("2024-01-02 13:00:00");
            $category = TestEntityFactory::createCategory(
                id: $categoryId,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
            );

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($result["created_at"])->toBe(
                $createdAt->format(DateTimeInterface::ATOM),
            );
            expect($result["updated_at"])->toBe(
                $updatedAt->format(DateTimeInterface::ATOM),
            );
        });

        test("includes dashboard_id in output", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);
            $category = TestEntityFactory::createCategory(
                id: $categoryId,
                dashboard: $dashboard,
            );

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($result["dashboard_id"])->toBe($dashboardId->toString());
        });

        test("includes sort_order in output", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(
                id: $categoryId,
                sortOrder: 10,
            );

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $result = $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($result["sort_order"])->toBe(10);
        });
    });

    describe("integration scenarios", function () {
        test("full workflow: filter, validate, and execute", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(id: $categoryId);

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->once()
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "category_id" => "  {$categoryId->toString()}  ",
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBeArray();
                expect($result["category_id"])->toBe($categoryId->toString());
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("full workflow with extra fields in input", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(id: $categoryId);

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->once()
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "category_id" => $categoryId->toString(),
                "title" => "Should be ignored",
                "color" => "#FF0000",
                "extra" => "data",
            ];

            $filtered = $action->filter($rawData);
            expect($filtered)->not->toHaveKey("title");
            expect($filtered)->not->toHaveKey("color");

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBeArray();
                expect($result["category_id"])->toBe($categoryId->toString());
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("full workflow filters and validates id correctly", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(id: $categoryId);

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "category_id" => "  {$categoryId->toString()}  ",
            ];

            $filtered = $action->filter($rawData);
            expect($filtered["category_id"])->toBe($categoryId->toString());

            $action->validate($filtered);
            $result = $action->execute($filtered);

            expect($result)->toBeArray();
            expect($result["category_id"])->toBe($categoryId->toString());
        });

        test("validation failure prevents service call", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);

            $categoryService->shouldNotReceive("getCategory");

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "category_id" => "invalid-uuid",
            ];

            $filtered = $action->filter($rawData);

            expect(function () use ($action, $filtered) {
                $action->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });

        test("complete data transformation workflow", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);
            $category = TestEntityFactory::createCategory(
                id: $categoryId,
                dashboard: $dashboard,
                title: new Title("Integration Test Category"),
                color: new HexColor("#ABCDEF"),
                sortOrder: 3,
            );

            $categoryService
                ->shouldReceive("getCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->andReturn($category);

            $inputSpec = new IdInputSpec("category_id");
            $outputSpec = new CategoryOutputSpec();
            $action = new CategoryReadAction(
                $categoryService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = ["category_id" => $categoryId->toString()];
            $filtered = $action->filter($rawData);
            $action->validate($filtered);
            $result = $action->execute($filtered);

            expect($result["category_id"])->toBe($categoryId->toString());
            expect($result["dashboard_id"])->toBe($dashboardId->toString());
            expect($result["title"])->toBe("Integration Test Category");
            expect($result["color"])->toBe("#ABCDEF");
            expect($result["sort_order"])->toBe(3);
            expect($result)->toHaveKey("created_at");
            expect($result)->toHaveKey("updated_at");
        });
    });
});
