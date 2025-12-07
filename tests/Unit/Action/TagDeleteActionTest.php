<?php

use jschreuder\BookmarkBureau\Action\TagDeleteAction;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\BookmarkBureau\InputSpec\TagNameInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;

describe("TagDeleteAction", function () {
    describe("getAttributeKeysForData method", function () {
        test("returns tag_name attribute key", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            expect($action->getAttributeKeysForData())->toBe(["tag_name"]);
        });
    });

    describe("filter method", function () {
        test("filters id and trims whitespace", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            $filtered = $action->filter(["tag_name" => "  important  "]);

            expect($filtered["tag_name"])->toBe("important");
        });
    });

    describe("validate method", function () {
        test("passes validation with valid id", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            try {
                $action->validate(["tag_name" => "important"]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for empty id", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            expect(fn() => $action->validate(["tag_name" => ""]))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for missing id", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            expect(fn() => $action->validate([]))->toThrow(
                ValidationFailedException::class,
            );
        });
    });

    describe("execute method", function () {
        test("calls tagService.deleteTag and returns empty array", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            $tagService->shouldReceive("deleteTag")->once()->with("important");

            $result = $action->execute(["tag_name" => "important"]);

            expect($result)->toBe([]);
        });
    });

    describe("full workflow", function () {
        test("filter -> validate -> execute workflow", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            $rawData = ["tag_name" => "  important  ", "extra" => "ignored"];
            $filtered = $action->filter($rawData);
            expect($filtered["tag_name"])->toBe("important");

            try {
                $action->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }

            $tagService->shouldReceive("deleteTag")->once()->with("important");

            $result = $action->execute($filtered);
            expect($result)->toBe([]);
        });
    });
});
