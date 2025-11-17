<?php

use jschreuder\BookmarkBureau\Composite\CategoryLinkCollection;

describe("CategoryLinkCollection", function () {
    describe("construction", function () {
        test("creates an empty collection", function () {
            $collection = new CategoryLinkCollection();

            expect($collection)->toBeInstanceOf(CategoryLinkCollection::class);
        });

        test("creates a collection with single category link", function () {
            $categoryLink = TestEntityFactory::createCategoryLink();
            $collection = new CategoryLinkCollection($categoryLink);

            expect($collection)->toBeInstanceOf(CategoryLinkCollection::class);
            expect($collection->count())->toBe(1);
        });

        test("creates a collection with multiple category links", function () {
            $categoryLink1 = TestEntityFactory::createCategoryLink();
            $categoryLink2 = TestEntityFactory::createCategoryLink();
            $categoryLink3 = TestEntityFactory::createCategoryLink();
            $collection = new CategoryLinkCollection(
                $categoryLink1,
                $categoryLink2,
                $categoryLink3,
            );

            expect($collection)->toBeInstanceOf(CategoryLinkCollection::class);
            expect($collection->count())->toBe(3);
        });

        test("stores category links in the collection", function () {
            $categoryLink1 = TestEntityFactory::createCategoryLink();
            $categoryLink2 = TestEntityFactory::createCategoryLink();
            $collection = new CategoryLinkCollection(
                $categoryLink1,
                $categoryLink2,
            );

            $array = $collection->toArray();
            expect($array[0])->toBe($categoryLink1);
            expect($array[1])->toBe($categoryLink2);
        });
    });

    describe("Countable interface", function () {
        test("count returns zero for empty collection", function () {
            $collection = new CategoryLinkCollection();

            expect($collection->count())->toBe(0);
            expect(count($collection))->toBe(0);
        });

        test("count returns correct number of category links", function () {
            $categoryLinks = [
                TestEntityFactory::createCategoryLink(),
                TestEntityFactory::createCategoryLink(),
                TestEntityFactory::createCategoryLink(),
            ];
            $collection = new CategoryLinkCollection(...$categoryLinks);

            expect($collection->count())->toBe(3);
            expect(count($collection))->toBe(3);
        });

        test(
            "count works after collection construction with variadic args",
            function () {
                $collection = new CategoryLinkCollection(
                    TestEntityFactory::createCategoryLink(),
                    TestEntityFactory::createCategoryLink(),
                );

                expect($collection->count())->toBe(2);
            },
        );
    });

    describe("IteratorAggregate interface", function () {
        test("can iterate over empty collection", function () {
            $collection = new CategoryLinkCollection();

            $iterations = 0;
            foreach ($collection as $categoryLink) {
                $iterations++;
            }

            expect($iterations)->toBe(0);
        });

        test("can iterate over collection with category links", function () {
            $categoryLink1 = TestEntityFactory::createCategoryLink();
            $categoryLink2 = TestEntityFactory::createCategoryLink();
            $categoryLink3 = TestEntityFactory::createCategoryLink();
            $collection = new CategoryLinkCollection(
                $categoryLink1,
                $categoryLink2,
                $categoryLink3,
            );

            $iterations = 0;
            $iteratedCategoryLinks = [];
            foreach ($collection as $categoryLink) {
                $iterations++;
                $iteratedCategoryLinks[] = $categoryLink;
            }

            expect($iterations)->toBe(3);
            expect($iteratedCategoryLinks[0])->toBe($categoryLink1);
            expect($iteratedCategoryLinks[1])->toBe($categoryLink2);
            expect($iteratedCategoryLinks[2])->toBe($categoryLink3);
        });

        test("maintains order during iteration", function () {
            $categoryLinks = [
                TestEntityFactory::createCategoryLink(),
                TestEntityFactory::createCategoryLink(),
                TestEntityFactory::createCategoryLink(),
            ];
            $collection = new CategoryLinkCollection(...$categoryLinks);

            $iteratedCategoryLinks = [];
            foreach ($collection as $categoryLink) {
                $iteratedCategoryLinks[] = $categoryLink;
            }

            expect($iteratedCategoryLinks)->toBe($categoryLinks);
        });
    });

    describe("isEmpty method", function () {
        test("returns true for empty collection", function () {
            $collection = new CategoryLinkCollection();

            expect($collection->isEmpty())->toBeTrue();
        });

        test(
            "returns false for collection with one category link",
            function () {
                $categoryLink = TestEntityFactory::createCategoryLink();
                $collection = new CategoryLinkCollection($categoryLink);

                expect($collection->isEmpty())->toBeFalse();
            },
        );

        test(
            "returns false for collection with multiple category links",
            function () {
                $collection = new CategoryLinkCollection(
                    TestEntityFactory::createCategoryLink(),
                    TestEntityFactory::createCategoryLink(),
                    TestEntityFactory::createCategoryLink(),
                );

                expect($collection->isEmpty())->toBeFalse();
            },
        );
    });
});
