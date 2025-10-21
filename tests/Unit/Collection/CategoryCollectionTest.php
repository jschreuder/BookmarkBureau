<?php

use jschreuder\BookmarkBureau\Collection\CategoryCollection;

describe('CategoryCollection', function () {
    describe('construction', function () {
        test('creates an empty collection', function () {
            $collection = new CategoryCollection();

            expect($collection)->toBeInstanceOf(CategoryCollection::class);
        });

        test('creates a collection with single category', function () {
            $category = TestEntityFactory::createCategory();
            $collection = new CategoryCollection($category);

            expect($collection)->toBeInstanceOf(CategoryCollection::class);
            expect($collection->count())->toBe(1);
        });

        test('creates a collection with multiple categories', function () {
            $category1 = TestEntityFactory::createCategory();
            $category2 = TestEntityFactory::createCategory();
            $category3 = TestEntityFactory::createCategory();
            $collection = new CategoryCollection($category1, $category2, $category3);

            expect($collection)->toBeInstanceOf(CategoryCollection::class);
            expect($collection->count())->toBe(3);
        });

        test('stores categories in the collection', function () {
            $category1 = TestEntityFactory::createCategory();
            $category2 = TestEntityFactory::createCategory();
            $collection = new CategoryCollection($category1, $category2);

            $array = $collection->toArray();
            expect($array[0])->toBe($category1);
            expect($array[1])->toBe($category2);
        });
    });

    describe('Countable interface', function () {
        test('count returns zero for empty collection', function () {
            $collection = new CategoryCollection();

            expect($collection->count())->toBe(0);
            expect(count($collection))->toBe(0);
        });

        test('count returns correct number of categories', function () {
            $categories = [
                TestEntityFactory::createCategory(),
                TestEntityFactory::createCategory(),
                TestEntityFactory::createCategory(),
            ];
            $collection = new CategoryCollection(...$categories);

            expect($collection->count())->toBe(3);
            expect(count($collection))->toBe(3);
        });

        test('count works after collection construction with variadic args', function () {
            $collection = new CategoryCollection(
                TestEntityFactory::createCategory(),
                TestEntityFactory::createCategory()
            );

            expect($collection->count())->toBe(2);
        });
    });

    describe('IteratorAggregate interface', function () {
        test('can iterate over empty collection', function () {
            $collection = new CategoryCollection();

            $iterations = 0;
            foreach ($collection as $category) {
                $iterations++;
            }

            expect($iterations)->toBe(0);
        });

        test('can iterate over collection with categories', function () {
            $category1 = TestEntityFactory::createCategory();
            $category2 = TestEntityFactory::createCategory();
            $category3 = TestEntityFactory::createCategory();
            $collection = new CategoryCollection($category1, $category2, $category3);

            $iterations = 0;
            $iteratedCategories = [];
            foreach ($collection as $category) {
                $iterations++;
                $iteratedCategories[] = $category;
            }

            expect($iterations)->toBe(3);
            expect($iteratedCategories[0])->toBe($category1);
            expect($iteratedCategories[1])->toBe($category2);
            expect($iteratedCategories[2])->toBe($category3);
        });

        test('maintains order during iteration', function () {
            $categories = [
                TestEntityFactory::createCategory(),
                TestEntityFactory::createCategory(),
                TestEntityFactory::createCategory(),
            ];
            $collection = new CategoryCollection(...$categories);

            $iteratedCategories = [];
            foreach ($collection as $category) {
                $iteratedCategories[] = $category;
            }

            expect($iteratedCategories)->toBe($categories);
        });
    });

    describe('isEmpty method', function () {
        test('returns true for empty collection', function () {
            $collection = new CategoryCollection();

            expect($collection->isEmpty())->toBeTrue();
        });

        test('returns false for collection with one category', function () {
            $category = TestEntityFactory::createCategory();
            $collection = new CategoryCollection($category);

            expect($collection->isEmpty())->toBeFalse();
        });

        test('returns false for collection with multiple categories', function () {
            $collection = new CategoryCollection(
                TestEntityFactory::createCategory(),
                TestEntityFactory::createCategory(),
                TestEntityFactory::createCategory()
            );

            expect($collection->isEmpty())->toBeFalse();
        });
    });
});
