<?php

use jschreuder\BookmarkBureau\Action\TagReadAction;
use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\BookmarkBureau\InputSpec\TagNameInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Exception\TagNotFoundException;
use jschreuder\Middle\Exception\ValidationFailedException;

describe("TagReadAction", function () {
    describe("getAttributeKeysForData method", function () {
        test("returns tag_name attribute key", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagReadAction(
                $tagService,
                new TagNameInputSpec(),
                new TagOutputSpec(),
            );

            expect($action->getAttributeKeysForData())->toBe(["tag_name"]);
        });
    });

    describe("filter method", function () {
        test("filters id and trims whitespace", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagReadAction(
                $tagService,
                new TagNameInputSpec(),
                new TagOutputSpec(),
            );

            $filtered = $action->filter(["tag_name" => "  important  "]);

            expect($filtered["tag_name"])->toBe("important");
        });
    });

    describe("validate method", function () {
        test("passes validation with valid id", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagReadAction(
                $tagService,
                new TagNameInputSpec(),
                new TagOutputSpec(),
            );

            try {
                $action->validate(["tag_name" => "important"]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for empty id", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagReadAction(
                $tagService,
                new TagNameInputSpec(),
                new TagOutputSpec(),
            );

            expect(fn() => $action->validate(["tag_name" => ""]))->toThrow(
                ValidationFailedException::class,
            );
        });
    });

    describe("execute method", function () {
        test("returns tag when found in list", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagReadAction(
                $tagService,
                new TagNameInputSpec(),
                new TagOutputSpec(),
            );

            $tag = TestEntityFactory::createTag(
                tagName: new TagName("important"),
            );

            $tagService
                ->shouldReceive("getTag")
                ->with($tag->tagName->value)
                ->once()
                ->andReturn($tag);

            $result = $action->execute(["tag_name" => "important"]);

            expect($result)->toBeArray();
            expect($result["tag_name"])->toBe("important");
        });

        test("throws TagNotFoundException when tag not found", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagReadAction(
                $tagService,
                new TagNameInputSpec(),
                new TagOutputSpec(),
            );

            $tagService
                ->shouldReceive("getTag")
                ->with("nonexistent")
                ->once()
                ->andThrow(TagNotFoundException::class);

            expect(
                fn() => $action->execute(["tag_name" => "nonexistent"]),
            )->toThrow(TagNotFoundException::class);
        });
    });

    describe("full workflow", function () {
        test("filter -> validate -> execute workflow", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagReadAction(
                $tagService,
                new TagNameInputSpec(),
                new TagOutputSpec(),
            );

            $rawData = ["tag_name" => "  important  "];
            $filtered = $action->filter($rawData);
            expect($filtered["tag_name"])->toBe("important");

            try {
                $action->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }

            $tag = TestEntityFactory::createTag(
                tagName: new TagName("important"),
            );

            $tagService
                ->shouldReceive("getTag")
                ->with($tag->tagName->value)
                ->once()
                ->andReturn($tag);

            $result = $action->execute($filtered);
            expect($result)->toBeArray();
        });
    });
});
