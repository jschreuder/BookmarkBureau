<?php

use jschreuder\BookmarkBureau\Composite\LinkCollection;

describe("LinkCollection", function () {
    describe("construction", function () {
        test("creates an empty collection", function () {
            $collection = new LinkCollection();

            expect($collection)->toBeInstanceOf(LinkCollection::class);
        });

        test("creates a collection with single link", function () {
            $link = TestEntityFactory::createLink();
            $collection = new LinkCollection($link);

            expect($collection)->toBeInstanceOf(LinkCollection::class);
            expect($collection->count())->toBe(1);
        });

        test("creates a collection with multiple links", function () {
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $link3 = TestEntityFactory::createLink();
            $collection = new LinkCollection($link1, $link2, $link3);

            expect($collection)->toBeInstanceOf(LinkCollection::class);
            expect($collection->count())->toBe(3);
        });

        test("stores links in the collection", function () {
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $collection = new LinkCollection($link1, $link2);

            $array = $collection->toArray();
            expect($array[0])->toBe($link1);
            expect($array[1])->toBe($link2);
        });
    });

    describe("Countable interface", function () {
        test("count returns zero for empty collection", function () {
            $collection = new LinkCollection();

            expect($collection->count())->toBe(0);
            expect(count($collection))->toBe(0);
        });

        test("count returns correct number of links", function () {
            $links = [
                TestEntityFactory::createLink(),
                TestEntityFactory::createLink(),
                TestEntityFactory::createLink(),
            ];
            $collection = new LinkCollection(...$links);

            expect($collection->count())->toBe(3);
            expect(count($collection))->toBe(3);
        });

        test(
            "count works after collection construction with variadic args",
            function () {
                $collection = new LinkCollection(
                    TestEntityFactory::createLink(),
                    TestEntityFactory::createLink(),
                );

                expect($collection->count())->toBe(2);
            },
        );
    });

    describe("IteratorAggregate interface", function () {
        test("can iterate over empty collection", function () {
            $collection = new LinkCollection();

            $iterations = 0;
            foreach ($collection as $link) {
                $iterations++;
            }

            expect($iterations)->toBe(0);
        });

        test("can iterate over collection with links", function () {
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $link3 = TestEntityFactory::createLink();
            $collection = new LinkCollection($link1, $link2, $link3);

            $iterations = 0;
            $iteratedLinks = [];
            foreach ($collection as $link) {
                $iterations++;
                $iteratedLinks[] = $link;
            }

            expect($iterations)->toBe(3);
            expect($iteratedLinks[0])->toBe($link1);
            expect($iteratedLinks[1])->toBe($link2);
            expect($iteratedLinks[2])->toBe($link3);
        });

        test("maintains order during iteration", function () {
            $links = [
                TestEntityFactory::createLink(),
                TestEntityFactory::createLink(),
                TestEntityFactory::createLink(),
            ];
            $collection = new LinkCollection(...$links);

            $iteratedLinks = [];
            foreach ($collection as $link) {
                $iteratedLinks[] = $link;
            }

            expect($iteratedLinks)->toBe($links);
        });
    });

    describe("isEmpty method", function () {
        test("returns true for empty collection", function () {
            $collection = new LinkCollection();

            expect($collection->isEmpty())->toBeTrue();
        });

        test("returns false for collection with one link", function () {
            $link = TestEntityFactory::createLink();
            $collection = new LinkCollection($link);

            expect($collection->isEmpty())->toBeFalse();
        });

        test("returns false for collection with multiple links", function () {
            $collection = new LinkCollection(
                TestEntityFactory::createLink(),
                TestEntityFactory::createLink(),
                TestEntityFactory::createLink(),
            );

            expect($collection->isEmpty())->toBeFalse();
        });
    });
});
