<?php

use jschreuder\BookmarkBureau\Collection\FavoriteCollection;

describe('FavoriteCollection', function () {
    describe('construction', function () {
        test('creates an empty collection', function () {
            $collection = new FavoriteCollection();

            expect($collection)->toBeInstanceOf(FavoriteCollection::class);
        });

        test('creates a collection with single favorite', function () {
            $favorite = TestEntityFactory::createFavorite();
            $collection = new FavoriteCollection($favorite);

            expect($collection)->toBeInstanceOf(FavoriteCollection::class);
            expect($collection->count())->toBe(1);
        });

        test('creates a collection with multiple favorites', function () {
            $favorite1 = TestEntityFactory::createFavorite();
            $favorite2 = TestEntityFactory::createFavorite();
            $favorite3 = TestEntityFactory::createFavorite();
            $collection = new FavoriteCollection($favorite1, $favorite2, $favorite3);

            expect($collection)->toBeInstanceOf(FavoriteCollection::class);
            expect($collection->count())->toBe(3);
        });

        test('stores favorites in the collection', function () {
            $favorite1 = TestEntityFactory::createFavorite();
            $favorite2 = TestEntityFactory::createFavorite();
            $collection = new FavoriteCollection($favorite1, $favorite2);

            $array = $collection->toArray();
            expect($array[0])->toBe($favorite1);
            expect($array[1])->toBe($favorite2);
        });
    });

    describe('Countable interface', function () {
        test('count returns zero for empty collection', function () {
            $collection = new FavoriteCollection();

            expect($collection->count())->toBe(0);
            expect(count($collection))->toBe(0);
        });

        test('count returns correct number of favorites', function () {
            $favorites = [
                TestEntityFactory::createFavorite(),
                TestEntityFactory::createFavorite(),
                TestEntityFactory::createFavorite(),
            ];
            $collection = new FavoriteCollection(...$favorites);

            expect($collection->count())->toBe(3);
            expect(count($collection))->toBe(3);
        });

        test('count works after collection construction with variadic args', function () {
            $collection = new FavoriteCollection(
                TestEntityFactory::createFavorite(),
                TestEntityFactory::createFavorite()
            );

            expect($collection->count())->toBe(2);
        });
    });

    describe('IteratorAggregate interface', function () {
        test('can iterate over empty collection', function () {
            $collection = new FavoriteCollection();

            $iterations = 0;
            foreach ($collection as $favorite) {
                $iterations++;
            }

            expect($iterations)->toBe(0);
        });

        test('can iterate over collection with favorites', function () {
            $favorite1 = TestEntityFactory::createFavorite();
            $favorite2 = TestEntityFactory::createFavorite();
            $favorite3 = TestEntityFactory::createFavorite();
            $collection = new FavoriteCollection($favorite1, $favorite2, $favorite3);

            $iterations = 0;
            $iteratedFavorites = [];
            foreach ($collection as $favorite) {
                $iterations++;
                $iteratedFavorites[] = $favorite;
            }

            expect($iterations)->toBe(3);
            expect($iteratedFavorites[0])->toBe($favorite1);
            expect($iteratedFavorites[1])->toBe($favorite2);
            expect($iteratedFavorites[2])->toBe($favorite3);
        });

        test('maintains order during iteration', function () {
            $favorites = [
                TestEntityFactory::createFavorite(),
                TestEntityFactory::createFavorite(),
                TestEntityFactory::createFavorite(),
            ];
            $collection = new FavoriteCollection(...$favorites);

            $iteratedFavorites = [];
            foreach ($collection as $favorite) {
                $iteratedFavorites[] = $favorite;
            }

            expect($iteratedFavorites)->toBe($favorites);
        });
    });

    describe('isEmpty method', function () {
        test('returns true for empty collection', function () {
            $collection = new FavoriteCollection();

            expect($collection->isEmpty())->toBeTrue();
        });

        test('returns false for collection with one favorite', function () {
            $favorite = TestEntityFactory::createFavorite();
            $collection = new FavoriteCollection($favorite);

            expect($collection->isEmpty())->toBeFalse();
        });

        test('returns false for collection with multiple favorites', function () {
            $collection = new FavoriteCollection(
                TestEntityFactory::createFavorite(),
                TestEntityFactory::createFavorite(),
                TestEntityFactory::createFavorite()
            );

            expect($collection->isEmpty())->toBeFalse();
        });
    });
});
