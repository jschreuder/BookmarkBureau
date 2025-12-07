<?php

use jschreuder\BookmarkBureau\Action\CategoryLinkDeleteAction;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\BookmarkBureau\InputSpec\CategoryLinkInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

describe("CategoryLinkDeleteAction", function () {
    describe("filter method", function () {
        test("trims whitespace from id and link_id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryLinkInputSpec();
            $action = new CategoryLinkDeleteAction(
                $categoryService,
                $inputSpec,
            );
            $categoryId = Uuid::uuid4();
            $linkId = Uuid::uuid4();

            $filtered = $action->filter([
                "category_id" => "  {$categoryId->toString()}  ",
                "link_id" => "  {$linkId->toString()}  ",
            ]);

            expect($filtered["category_id"])->toBe($categoryId->toString());
            expect($filtered["link_id"])->toBe($linkId->toString());
        });

        test("handles missing keys with appropriate defaults", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryLinkInputSpec();
            $action = new CategoryLinkDeleteAction(
                $categoryService,
                $inputSpec,
            );

            $filtered = $action->filter([]);

            expect($filtered["category_id"])->toBe("");
            expect($filtered["link_id"])->toBe("");
        });
    });

    describe("validate method", function () {
        test("passes validation with valid data", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryLinkInputSpec();
            $action = new CategoryLinkDeleteAction(
                $categoryService,
                $inputSpec,
            );
            $categoryId = Uuid::uuid4();
            $linkId = Uuid::uuid4();

            $data = [
                "category_id" => $categoryId->toString(),
                "link_id" => $linkId->toString(),
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
            $inputSpec = new CategoryLinkInputSpec();
            $action = new CategoryLinkDeleteAction(
                $categoryService,
                $inputSpec,
            );

            $data = [
                "category_id" => "not-a-uuid",
                "link_id" => Uuid::uuid4()->toString(),
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for invalid link_id UUID", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryLinkInputSpec();
            $action = new CategoryLinkDeleteAction(
                $categoryService,
                $inputSpec,
            );

            $data = [
                "category_id" => Uuid::uuid4()->toString(),
                "link_id" => "not-a-uuid",
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for missing id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryLinkInputSpec();
            $action = new CategoryLinkDeleteAction(
                $categoryService,
                $inputSpec,
            );

            $data = [
                "link_id" => Uuid::uuid4()->toString(),
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for missing link_id", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $inputSpec = new CategoryLinkInputSpec();
            $action = new CategoryLinkDeleteAction(
                $categoryService,
                $inputSpec,
            );

            $data = [
                "category_id" => Uuid::uuid4()->toString(),
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });
    });

    describe("execute method", function () {
        test("executes with valid data and returns empty array", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $linkId = Uuid::uuid4();

            $categoryService
                ->shouldReceive("removeLinkFromCategory")
                ->with(
                    Mockery::type(UuidInterface::class),
                    Mockery::type(UuidInterface::class),
                )
                ->andReturn(null);

            $inputSpec = new CategoryLinkInputSpec();
            $action = new CategoryLinkDeleteAction(
                $categoryService,
                $inputSpec,
            );

            $result = $action->execute([
                "category_id" => $categoryId->toString(),
                "link_id" => $linkId->toString(),
            ]);

            expect($result)->toBe([]);
        });

        test("calls service with correct UUID arguments", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $linkId = Uuid::uuid4();

            $categoryService
                ->shouldReceive("removeLinkFromCategory")
                ->with(
                    Mockery::on(
                        fn($arg) => $arg->toString() ===
                            $categoryId->toString(),
                    ),
                    Mockery::on(
                        fn($arg) => $arg->toString() === $linkId->toString(),
                    ),
                )
                ->andReturn(null);

            $inputSpec = new CategoryLinkInputSpec();
            $action = new CategoryLinkDeleteAction(
                $categoryService,
                $inputSpec,
            );

            $action->execute([
                "category_id" => $categoryId->toString(),
                "link_id" => $linkId->toString(),
            ]);

            expect(true)->toBeTrue(); // Mockery validates the call
        });
    });

    describe("integration scenarios", function () {
        test("full workflow: filter, validate, and execute", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $categoryId = Uuid::uuid4();
            $linkId = Uuid::uuid4();

            $categoryService
                ->shouldReceive("removeLinkFromCategory")
                ->andReturn(null);

            $inputSpec = new CategoryLinkInputSpec();
            $action = new CategoryLinkDeleteAction(
                $categoryService,
                $inputSpec,
            );

            $rawData = [
                "category_id" => "  {$categoryId->toString()}  ",
                "link_id" => "  {$linkId->toString()}  ",
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
    });
});
