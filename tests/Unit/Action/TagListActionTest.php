<?php

use jschreuder\BookmarkBureau\Action\TagListAction;
use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;

describe("TagListAction", function () {
    describe("getAttributeKeysForData method", function () {
        test(
            "returns empty array since list actions have no routing params",
            function () {
                $tagService = Mockery::mock(TagServiceInterface::class);
                $outputSpec = new TagOutputSpec();
                $action = new TagListAction($tagService, $outputSpec);

                expect($action->getAttributeKeysForData())->toBe([]);
            },
        );
    });

    describe("filter method", function () {
        test("returns empty array for list operation", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $outputSpec = new TagOutputSpec();
            $action = new TagListAction($tagService, $outputSpec);

            $filtered = $action->filter([]);

            expect($filtered)->toBe([]);
        });

        test("ignores input data for list operation", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $outputSpec = new TagOutputSpec();
            $action = new TagListAction($tagService, $outputSpec);

            $filtered = $action->filter([
                "tag_name" => "some-tag",
                "extra" => "data",
            ]);

            expect($filtered)->toBe([]);
        });
    });

    describe("validate method", function () {
        test("always validates without errors for list operation", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $outputSpec = new TagOutputSpec();
            $action = new TagListAction($tagService, $outputSpec);

            try {
                $action->validate([]);
                expect(true)->toBeTrue();
            } catch (Exception $e) {
                throw $e;
            }
        });

        test("validates with arbitrary input data", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $outputSpec = new TagOutputSpec();
            $action = new TagListAction($tagService, $outputSpec);

            try {
                $action->validate(["anything" => "ignored"]);
                expect(true)->toBeTrue();
            } catch (Exception $e) {
                throw $e;
            }
        });
    });

    describe("execute method", function () {
        test("calls getAllTags on service", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $collection = new TagCollection();

            $tagService
                ->shouldReceive("getAllTags")
                ->once()
                ->andReturn($collection);

            $outputSpec = new TagOutputSpec();
            $action = new TagListAction($tagService, $outputSpec);

            $result = $action->execute([]);

            expect($result)->toBeArray();
        });

        test("returns empty tags array for empty collection", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $collection = new TagCollection();

            $tagService->shouldReceive("getAllTags")->andReturn($collection);

            $outputSpec = new TagOutputSpec();
            $action = new TagListAction($tagService, $outputSpec);

            $result = $action->execute([]);

            expect($result)->toHaveKey("tags");
            expect($result["tags"])->toBe([]);
        });

        test("transforms single tag", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $tag = TestEntityFactory::createTag();
            $collection = new TagCollection($tag);

            $tagService->shouldReceive("getAllTags")->andReturn($collection);

            $outputSpec = new TagOutputSpec();
            $action = new TagListAction($tagService, $outputSpec);

            $result = $action->execute([]);

            expect($result)->toHaveKey("tags");
            expect($result["tags"])->toHaveCount(1);
            expect($result["tags"][0])->toHaveKey("tag_name");
            expect($result["tags"][0])->toHaveKey("color");
        });

        test("transforms multiple tags", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $tag1 = TestEntityFactory::createTag(tagName: new TagName("php"));
            $tag2 = TestEntityFactory::createTag(
                tagName: new TagName("javascript"),
            );
            $tag3 = TestEntityFactory::createTag(
                tagName: new TagName("python"),
            );
            $collection = new TagCollection($tag1, $tag2, $tag3);

            $tagService->shouldReceive("getAllTags")->andReturn($collection);

            $outputSpec = new TagOutputSpec();
            $action = new TagListAction($tagService, $outputSpec);

            $result = $action->execute([]);

            expect($result["tags"])->toHaveCount(3);
            expect($result["tags"][0]["tag_name"])->toBe("php");
            expect($result["tags"][1]["tag_name"])->toBe("javascript");
            expect($result["tags"][2]["tag_name"])->toBe("python");
        });

        test("returns formatted tag structure", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $tag = TestEntityFactory::createTag(
                tagName: new TagName("important"),
            );
            $collection = new TagCollection($tag);

            $tagService->shouldReceive("getAllTags")->andReturn($collection);

            $outputSpec = new TagOutputSpec();
            $action = new TagListAction($tagService, $outputSpec);

            $result = $action->execute([]);

            expect($result["tags"][0]["tag_name"])->toBe("important");
        });

        test("includes tag color in output", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $tag = TestEntityFactory::createTag();
            $collection = new TagCollection($tag);

            $tagService->shouldReceive("getAllTags")->andReturn($collection);

            $outputSpec = new TagOutputSpec();
            $action = new TagListAction($tagService, $outputSpec);

            $result = $action->execute([]);

            expect($result["tags"][0])->toHaveKey("color");
        });

        test("returns all required fields for each tag", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $tag = TestEntityFactory::createTag();
            $collection = new TagCollection($tag);

            $tagService->shouldReceive("getAllTags")->andReturn($collection);

            $outputSpec = new TagOutputSpec();
            $action = new TagListAction($tagService, $outputSpec);

            $result = $action->execute([]);

            $tag = $result["tags"][0];
            expect($tag)->toHaveKey("tag_name");
            expect($tag)->toHaveKey("color");
        });
    });

    describe("integration scenarios", function () {
        test(
            "full workflow: filter, validate, and execute with empty list",
            function () {
                $tagService = Mockery::mock(TagServiceInterface::class);
                $collection = new TagCollection();

                $tagService
                    ->shouldReceive("getAllTags")
                    ->andReturn($collection);

                $outputSpec = new TagOutputSpec();
                $action = new TagListAction($tagService, $outputSpec);

                $rawData = [];
                $filtered = $action->filter($rawData);
                $action->validate($filtered);
                $result = $action->execute($filtered);

                expect($result["tags"])->toBe([]);
            },
        );

        test(
            "full workflow: filter, validate, and execute with tags",
            function () {
                $tagService = Mockery::mock(TagServiceInterface::class);
                $tag1 = TestEntityFactory::createTag(
                    tagName: new TagName("frontend"),
                );
                $tag2 = TestEntityFactory::createTag(
                    tagName: new TagName("backend"),
                );
                $collection = new TagCollection($tag1, $tag2);

                $tagService
                    ->shouldReceive("getAllTags")
                    ->andReturn($collection);

                $outputSpec = new TagOutputSpec();
                $action = new TagListAction($tagService, $outputSpec);

                $rawData = [];
                $filtered = $action->filter($rawData);
                $action->validate($filtered);
                $result = $action->execute($filtered);

                expect($result["tags"])->toHaveCount(2);
                expect($result["tags"][0]["tag_name"])->toBe("frontend");
                expect($result["tags"][1]["tag_name"])->toBe("backend");
            },
        );

        test(
            "full workflow ignores input data and returns all tags",
            function () {
                $tagService = Mockery::mock(TagServiceInterface::class);
                $tag1 = TestEntityFactory::createTag(
                    tagName: new TagName("tag1"),
                );
                $tag2 = TestEntityFactory::createTag(
                    tagName: new TagName("tag2"),
                );
                $collection = new TagCollection($tag1, $tag2);

                $tagService
                    ->shouldReceive("getAllTags")
                    ->andReturn($collection);

                $outputSpec = new TagOutputSpec();
                $action = new TagListAction($tagService, $outputSpec);

                $rawData = [
                    "tag_name" => "ignored",
                    "random" => "data",
                ];
                $filtered = $action->filter($rawData);
                $action->validate($filtered);
                $result = $action->execute($filtered);

                expect($result["tags"])->toHaveCount(2);
            },
        );
    });
});
