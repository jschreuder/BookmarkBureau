<?php

use jschreuder\BookmarkBureau\Entity\Mapper\TagEntityMapper;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\TagName;

describe("TagEntityMapper", function () {
    describe("getFields", function () {
        test("returns all tag field names", function () {
            $mapper = new TagEntityMapper();
            $fields = $mapper->getFields();

            expect($fields)->toBe(["tag_name", "color"]);
        });
    });

    describe("getDbFields", function () {
        test(
            "returns same fields as getFields since no entity references",
            function () {
                $mapper = new TagEntityMapper();
                $fields = $mapper->getFields();
                $dbFields = $mapper->getDbFields();

                expect($dbFields)->toBe($fields);
                expect($dbFields)->toBe(["tag_name", "color"]);
            },
        );
    });

    describe("supports", function () {
        test("returns true for Tag entities", function () {
            $mapper = new TagEntityMapper();
            $tag = TestEntityFactory::createTag();

            expect($mapper->supports($tag))->toBeTrue();
        });

        test("returns false for non-Tag entities", function () {
            $mapper = new TagEntityMapper();
            $link = TestEntityFactory::createLink();

            expect($mapper->supports($link))->toBeFalse();
        });
    });

    describe("mapToEntity", function () {
        test("maps row data to Tag entity", function () {
            $mapper = new TagEntityMapper();

            $data = [
                "tag_name" => "important",
                "color" => "#FF5733",
            ];

            $tag = $mapper->mapToEntity($data);

            expect($tag)->toBeInstanceOf(Tag::class);
            expect((string) $tag->tagName)->toBe("important");
            expect((string) $tag->color)->toBe("#FF5733");
        });

        test("maps row data with null color to Tag entity", function () {
            $mapper = new TagEntityMapper();

            $data = [
                "tag_name" => "uncolored",
                "color" => null,
            ];

            $tag = $mapper->mapToEntity($data);

            expect($tag->color)->toBeNull();
        });

        test(
            "throws InvalidArgumentException when required fields are missing",
            function () {
                $mapper = new TagEntityMapper();

                $data = [
                    "tag_name" => "incomplete",
                    // Missing: color
                ];

                expect(fn() => $mapper->mapToEntity($data))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("mapToRow", function () {
        test("maps Tag entity to row array", function () {
            $mapper = new TagEntityMapper();
            $tag = TestEntityFactory::createTag(
                tagName: new TagName("work"),
                color: new HexColor("#0000FF"),
            );

            $row = $mapper->mapToRow($tag);

            expect($row)->toHaveKey("tag_name");
            expect($row)->toHaveKey("color");

            expect($row["tag_name"])->toBe("work");
            expect($row["color"])->toBe("#0000FF");
        });

        test("maps Tag entity with null color to row array", function () {
            $mapper = new TagEntityMapper();
            $tag = TestEntityFactory::createTag(
                tagName: new TagName("personal"),
                color: null,
            );

            $row = $mapper->mapToRow($tag);

            expect($row["color"])->toBeNull();
        });

        test(
            "throws InvalidArgumentException when entity is not supported",
            function () {
                $mapper = new TagEntityMapper();
                $dashboard = TestEntityFactory::createDashboard();

                expect(fn() => $mapper->mapToRow($dashboard))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("round-trip mapping", function () {
        test("maps entity to row and back preserves all data", function () {
            $mapper = new TagEntityMapper();
            $originalTag = TestEntityFactory::createTag(
                tagName: new TagName("round-trip-test"),
                color: new HexColor("#00FF00"),
            );

            $row = $mapper->mapToRow($originalTag);
            $restoredTag = $mapper->mapToEntity($row);

            expect((string) $restoredTag->tagName)->toBe(
                (string) $originalTag->tagName,
            );
            expect((string) $restoredTag->color)->toBe(
                (string) $originalTag->color,
            );
        });

        test("round-trip mapping with null color preserves null", function () {
            $mapper = new TagEntityMapper();
            $originalTag = TestEntityFactory::createTag(
                tagName: new TagName("no-color"),
                color: null,
            );

            $row = $mapper->mapToRow($originalTag);
            $restoredTag = $mapper->mapToEntity($row);

            expect($restoredTag->color)->toBeNull();
        });

        test("preserves tag name through round-trip", function () {
            $mapper = new TagEntityMapper();
            $tagName = "preserve-me";
            $originalTag = TestEntityFactory::createTag(
                tagName: new TagName($tagName),
                color: new HexColor("#123456"),
            );

            $row = $mapper->mapToRow($originalTag);
            $restoredTag = $mapper->mapToEntity($row);

            expect((string) $restoredTag->tagName)->toBe($tagName);
        });
    });
});
