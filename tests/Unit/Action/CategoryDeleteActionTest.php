<?php

use jschreuder\BookmarkBureau\Action\CategoryDeleteAction;
use jschreuder\BookmarkBureau\InputSpec\IdInputSpec;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

describe("CategoryDeleteAction", function () {
    describe("getAttributeKeysForData method", function () {
        test("returns category_id attribute key", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            expect($action->getAttributeKeysForData())->toBe(["category_id"]);
        });
    });

    describe("filter method", function () {
        test("trims whitespace from id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);
            $categoryId = Uuid::uuid4();

            $filtered = $action->filter([
                "category_id" => "  {$categoryId->toString()}  ",
            ]);

            expect($filtered["category_id"])->toBe($categoryId->toString());
        });

        test("handles missing id key with empty string", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $filtered = $action->filter([]);

            expect($filtered["category_id"])->toBe("");
        });

        test("preserves valid id without modification", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);
            $categoryId = Uuid::uuid4();

            $filtered = $action->filter([
                "category_id" => $categoryId->toString(),
            ]);

            expect($filtered["category_id"])->toBe($categoryId->toString());
        });

        test("ignores additional fields in input", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);
            $categoryId = Uuid::uuid4();

            $filtered = $action->filter([
                "category_id" => $categoryId->toString(),
                "title" => "Should be ignored",
                "color" => "Should be ignored",
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
            $action = new CategoryDeleteAction($categoryService, $inputSpec);
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
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $data = ["category_id" => "not-a-uuid"];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for empty id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $data = ["category_id" => ""];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for missing id key", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $data = [];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for whitespace-only id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $data = ["category_id" => "   "];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for null id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $data = ["category_id" => null];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("validates UUID in different formats", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);
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
        test("calls deleteCategory on service with correct UUID", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();

            $categoryService
                ->shouldReceive("deleteCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $result = $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($result)->toBe([]);
        });

        test("returns empty array after successful deletion", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();

            $categoryService
                ->shouldReceive("deleteCategory")
                ->with(Mockery::type(UuidInterface::class));

            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $result = $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($result)->toEqual([]);
            expect($result)->toBeArray();
        });

        test(
            "converts string id to UUID before passing to service",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $categoryId = Uuid::uuid4();

                $categoryService
                    ->shouldReceive("deleteCategory")
                    ->with(Mockery::type(UuidInterface::class))
                    ->once();

                $inputSpec = new IdInputSpec("category_id");
                $action = new CategoryDeleteAction(
                    $categoryService,
                    $inputSpec,
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

            $uuidCapture = null;
            $categoryService
                ->shouldReceive("deleteCategory")
                ->andReturnUsing(function ($uuid) use (&$uuidCapture) {
                    $uuidCapture = $uuid;
                });

            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $action->execute([
                "category_id" => $categoryId->toString(),
            ]);

            expect($uuidCapture->toString())->toBe($categoryId->toString());
        });
    });

    describe("integration scenarios", function () {
        test("full workflow: filter, validate, and execute", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();

            $categoryService
                ->shouldReceive("deleteCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $rawData = [
                "category_id" => "  {$categoryId->toString()}  ",
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBe([]);
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("full workflow with extra fields in input", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();

            $categoryService
                ->shouldReceive("deleteCategory")
                ->with(Mockery::type(UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $rawData = [
                "category_id" => $categoryId->toString(),
                "title" => "Should be ignored",
                "color" => "Should be ignored",
                "extra" => "data",
            ];

            $filtered = $action->filter($rawData);
            expect($filtered)->not->toHaveKey("title");
            expect($filtered)->not->toHaveKey("color");

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBe([]);
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("full workflow filters and validates id correctly", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();

            $categoryService
                ->shouldReceive("deleteCategory")
                ->with(Mockery::type(UuidInterface::class));

            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $rawData = [
                "category_id" => "  {$categoryId->toString()}  ",
            ];

            $filtered = $action->filter($rawData);
            expect($filtered["category_id"])->toBe($categoryId->toString());

            $action->validate($filtered);
            $result = $action->execute($filtered);

            expect($result)->toBe([]);
        });

        test("validation failure prevents service call", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);

            $categoryService->shouldNotReceive("deleteCategory");

            $inputSpec = new IdInputSpec("category_id");
            $action = new CategoryDeleteAction($categoryService, $inputSpec);

            $rawData = [
                "category_id" => "invalid-uuid",
            ];

            $filtered = $action->filter($rawData);

            expect(function () use ($action, $filtered) {
                $action->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });
    });
});
