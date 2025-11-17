<?php

use jschreuder\BookmarkBureau\Composite\TagNameCollection;
use jschreuder\BookmarkBureau\Entity\Value\TagName;

describe("TagNameCollection", function () {
    describe("construction", function () {
        test("creates an empty collection", function () {
            $collection = new TagNameCollection();

            expect($collection)->toBeInstanceOf(TagNameCollection::class);
        });

        test("creates a collection with single tag name", function () {
            $tagName = new TagName("php");
            $collection = new TagNameCollection($tagName);

            expect($collection)->toBeInstanceOf(TagNameCollection::class);
            expect($collection->count())->toBe(1);
        });

        test("creates a collection with multiple tag names", function () {
            $tagName1 = new TagName("php");
            $tagName2 = new TagName("laravel");
            $tagName3 = new TagName("testing");
            $collection = new TagNameCollection(
                $tagName1,
                $tagName2,
                $tagName3,
            );

            expect($collection)->toBeInstanceOf(TagNameCollection::class);
            expect($collection->count())->toBe(3);
        });

        test("stores tag names in the collection", function () {
            $tagName1 = new TagName("php");
            $tagName2 = new TagName("laravel");
            $collection = new TagNameCollection($tagName1, $tagName2);

            $array = $collection->toArray();
            expect($array[0])->toBe($tagName1);
            expect($array[1])->toBe($tagName2);
        });
    });

    describe("Countable interface", function () {
        test("count returns zero for empty collection", function () {
            $collection = new TagNameCollection();

            expect($collection->count())->toBe(0);
            expect(count($collection))->toBe(0);
        });

        test("count returns correct number of tag names", function () {
            $tagNames = [
                new TagName("php"),
                new TagName("javascript"),
                new TagName("testing"),
            ];
            $collection = new TagNameCollection(...$tagNames);

            expect($collection->count())->toBe(3);
            expect(count($collection))->toBe(3);
        });

        test(
            "count works after collection construction with variadic args",
            function () {
                $collection = new TagNameCollection(
                    new TagName("development"),
                    new TagName("web"),
                );

                expect($collection->count())->toBe(2);
            },
        );
    });

    describe("IteratorAggregate interface", function () {
        test("can iterate over empty collection", function () {
            $collection = new TagNameCollection();

            $iterations = 0;
            foreach ($collection as $tagName) {
                $iterations++;
            }

            expect($iterations)->toBe(0);
        });

        test("can iterate over collection with tag names", function () {
            $tagName1 = new TagName("php");
            $tagName2 = new TagName("laravel");
            $tagName3 = new TagName("testing");
            $collection = new TagNameCollection(
                $tagName1,
                $tagName2,
                $tagName3,
            );

            $iterations = 0;
            $iteratedTagNames = [];
            foreach ($collection as $tagName) {
                $iterations++;
                $iteratedTagNames[] = $tagName;
            }

            expect($iterations)->toBe(3);
            expect($iteratedTagNames[0])->toBe($tagName1);
            expect($iteratedTagNames[1])->toBe($tagName2);
            expect($iteratedTagNames[2])->toBe($tagName3);
        });

        test("maintains order during iteration", function () {
            $tagNames = [
                new TagName("php"),
                new TagName("javascript"),
                new TagName("testing"),
            ];
            $collection = new TagNameCollection(...$tagNames);

            $iteratedTagNames = [];
            foreach ($collection as $tagName) {
                $iteratedTagNames[] = $tagName;
            }

            expect($iteratedTagNames)->toBe($tagNames);
        });
    });

    describe("isEmpty method", function () {
        test("returns true for empty collection", function () {
            $collection = new TagNameCollection();

            expect($collection->isEmpty())->toBeTrue();
        });

        test("returns false for collection with one tag name", function () {
            $tagName = new TagName("php");
            $collection = new TagNameCollection($tagName);

            expect($collection->isEmpty())->toBeFalse();
        });

        test(
            "returns false for collection with multiple tag names",
            function () {
                $collection = new TagNameCollection(
                    new TagName("php"),
                    new TagName("laravel"),
                    new TagName("testing"),
                );

                expect($collection->isEmpty())->toBeFalse();
            },
        );
    });
});
