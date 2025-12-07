<?php

use jschreuder\BookmarkBureau\Action\TagCreateAction;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\BookmarkBureau\InputSpec\TagInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\Middle\Exception\ValidationFailedException;

describe("TagCreateAction", function () {
    describe("filter method", function () {
        test("trims whitespace from id", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);

            $filtered = $action->filter([
                "tag_name" => "  important  ",
                "color" => "#FF0000",
            ]);

            expect($filtered["tag_name"])->toBe("important");
        });

        test("trims whitespace from color", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);

            $filtered = $action->filter([
                "tag_name" => "important",
                "color" => "  #FF0000  ",
            ]);

            expect($filtered["color"])->toBe("#FF0000");
        });

        test("handles missing keys with defaults", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);

            $filtered = $action->filter([]);

            expect($filtered["tag_name"])->toBe("");
            expect($filtered["color"])->toBeNull();
        });

        test("preserves null color as null", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);

            $filtered = $action->filter([
                "tag_name" => "important",
                "color" => null,
            ]);

            expect($filtered["color"])->toBeNull();
        });
    });

    describe("validate method", function () {
        test("passes validation with valid data", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);

            $data = [
                "tag_name" => "important",
                "color" => "#FF0000",
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("passes validation with null color", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);

            $data = [
                "tag_name" => "important",
                "color" => null,
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for empty id", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);

            $data = [
                "tag_name" => "",
                "color" => "#FF0000",
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for missing id", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);

            $data = [
                "color" => "#FF0000",
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });
    });

    describe("execute method", function () {
        test("calls tagService.createTag with correct parameters", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);
            $createdTag = TestEntityFactory::createTag();

            $tagService
                ->shouldReceive("createTag")
                ->once()
                ->with("important", "#FF0000")
                ->andReturn($createdTag);

            $result = $action->execute([
                "tag_name" => "important",
                "color" => "#FF0000",
            ]);

            expect($result)->toBeArray();
            expect($result)->toHaveKey("tag_name");
            expect($result)->toHaveKey("color");
        });

        test("calls tagService.createTag with null color", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);
            $createdTag = TestEntityFactory::createTag(color: null);

            $tagService
                ->shouldReceive("createTag")
                ->once()
                ->with("important", null)
                ->andReturn($createdTag);

            $result = $action->execute([
                "tag_name" => "important",
                "color" => null,
            ]);

            expect($result)->toBeArray();
            expect($result["color"])->toBeNull();
        });

        test("returns transformed tag from service", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);
            $createdTag = TestEntityFactory::createTag(
                tagName: new TagName("important"),
            );

            $tagService->shouldReceive("createTag")->andReturn($createdTag);

            $result = $action->execute([
                "tag_name" => "important",
                "color" => "#FF0000",
            ]);

            expect($result["tag_name"])->toBe("important");
        });
    });

    describe("full workflow", function () {
        test("filter -> validate -> execute workflow", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $inputSpec = new TagInputSpec();
            $outputSpec = new TagOutputSpec();
            $action = new TagCreateAction($tagService, $inputSpec, $outputSpec);
            $createdTag = TestEntityFactory::createTag();

            $rawData = [
                "tag_name" => "  important  ",
                "color" => "  #FF0000  ",
                "extra_field" => "ignored",
            ];

            $filtered = $action->filter($rawData);
            expect($filtered["tag_name"])->toBe("important");
            expect($filtered["color"])->toBe("#FF0000");

            try {
                $action->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }

            $tagService
                ->shouldReceive("createTag")
                ->once()
                ->with("important", "#FF0000")
                ->andReturn($createdTag);

            $result = $action->execute($filtered);
            expect($result)->toBeArray();
        });
    });
});
