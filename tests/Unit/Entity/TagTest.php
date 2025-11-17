<?php

use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\TagName;

describe("Tag Entity", function () {
    describe("construction", function () {
        test("creates a tag with all properties", function () {
            $tagName = new TagName("important");
            $color = new HexColor("#FF0000");

            $tag = new Tag($tagName, $color);

            expect($tag)->toBeInstanceOf(Tag::class);
        });

        test(
            "stores all properties correctly during construction",
            function () {
                $tagName = new TagName("important");
                $color = new HexColor("#FF0000");

                $tag = new Tag($tagName, $color);

                expect($tag->tagName)->toBe($tagName);
                expect($tag->color)->toBe($color);
            },
        );
    });

    describe("tagName getter", function () {
        test("getTagName returns the tag name", function () {
            $tagName = new TagName("important");
            $tag = TestEntityFactory::createTag(tagName: $tagName);

            expect($tag->tagName)->toBe($tagName);
            expect($tag->tagName)->toBeInstanceOf(TagName::class);
        });

        test("getTagName returns the exact tag name provided", function () {
            $tagNames = [
                new TagName("todo"),
                new TagName("bug-fix"),
                new TagName("documentation"),
                new TagName("feature-request"),
            ];

            foreach ($tagNames as $tagName) {
                $tag = TestEntityFactory::createTag(tagName: $tagName);
                expect($tag->tagName)->toBe($tagName);
            }
        });

        test("tagName is readonly and cannot be modified", function () {
            $tag = TestEntityFactory::createTag();

            expect(
                fn() => ($tag->tagName = new TagName("modified-tag")),
            )->toThrow(Error::class);
        });
    });

    describe("color getter and setter", function () {
        test("getColor returns the color", function () {
            $color = new HexColor("#00FF00");
            $tag = TestEntityFactory::createTag(color: $color);

            expect($tag->color)->toBe($color);
            expect($tag->color)->toBeInstanceOf(HexColor::class);
        });

        test("setColor updates the color", function () {
            $tag = TestEntityFactory::createTag();
            $newColor = new HexColor("#0000FF");

            $tag->color = $newColor;

            expect($tag->color)->toBe($newColor);
        });

        test("setColor works with various hex colors", function () {
            $tag = TestEntityFactory::createTag();
            $colors = [
                new HexColor("#FFFFFF"),
                new HexColor("#000000"),
                new HexColor("#123456"),
                new HexColor("#abcdef"),
            ];

            foreach ($colors as $color) {
                $tag->color = $color;
                expect($tag->color)->toBe($color);
            }
        });
    });

    describe("multiple setters", function () {
        test("can update color multiple times in sequence", function () {
            $tag = TestEntityFactory::createTag();
            $color1 = new HexColor("#FF0000");
            $color2 = new HexColor("#00FF00");
            $color3 = new HexColor("#0000FF");

            $tag->color = $color1;
            expect($tag->color)->toBe($color1);

            $tag->color = $color2;
            expect($tag->color)->toBe($color2);

            $tag->color = $color3;
            expect($tag->color)->toBe($color3);
        });
    });

    describe("immutability constraints", function () {
        test("tagName cannot be modified directly", function () {
            $tag = TestEntityFactory::createTag();

            expect(
                fn() => ($tag->tagName = new TagName("different-tag")),
            )->toThrow(Error::class);
        });
    });

    describe("edge cases", function () {
        test("can create tag with single character tag name", function () {
            $tag = TestEntityFactory::createTag(tagName: new TagName("a"));

            expect($tag->tagName->value)->toBe("a");
        });

        test("can create tag with long tag name", function () {
            $longTagName = new TagName(str_repeat("tag", 30));
            $tag = TestEntityFactory::createTag(tagName: $longTagName);

            expect($tag->tagName)->toBe($longTagName);
        });

        test(
            "cannot create tag with tag name containing special characters",
            function () {
                expect(
                    fn() => TestEntityFactory::createTag(
                        tagName: new TagName("tag-with-dashes_and_underscores"),
                    ),
                )->toThrow(InvalidArgumentException::class);
            },
        );

        test("can create tag with tag name containing numbers", function () {
            $tag = TestEntityFactory::createTag(tagName: new TagName("tag123"));

            expect($tag->tagName->value)->toBe("tag123");
        });
    });

    describe("equals method", function () {
        test("equals returns true for same tag name", function () {
            $tagName = new TagName("important");
            $color1 = new HexColor("#FF0000");
            $color2 = new HexColor("#00FF00");

            $tag1 = new Tag($tagName, $color1);
            $tag2 = new Tag($tagName, $color2);

            expect($tag1->equals($tag2))->toBeTrue();
        });

        test("equals returns false for different tag names", function () {
            $tagName1 = new TagName("important");
            $tagName2 = new TagName("urgent");
            $color = new HexColor("#FF0000");

            $tag1 = new Tag($tagName1, $color);
            $tag2 = new Tag($tagName2, $color);

            expect($tag1->equals($tag2))->toBeFalse();
        });

        test(
            "equals returns false when comparing with different type",
            function () {
                $tag = TestEntityFactory::createTag();
                $category = TestEntityFactory::createCategory();

                expect($tag->equals($category))->toBeFalse();
            },
        );

        test(
            "equals returns false when comparing with non-entity object",
            function () {
                $tag = TestEntityFactory::createTag();
                $stdObject = new stdClass();

                expect($tag->equals($stdObject))->toBeFalse();
            },
        );

        test("equals compares tag name value correctly", function () {
            $tag1 = new Tag(new TagName("bug"), new HexColor("#FF0000"));
            $tag2 = new Tag(new TagName("bug"), new HexColor("#FF0000"));

            expect($tag1->equals($tag2))->toBeTrue();
        });
    });
});
