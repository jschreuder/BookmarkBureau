<?php

use jschreuder\BookmarkBureau\Collection\TagCollection;

describe('TagCollection', function () {
    describe('construction', function () {
        test('creates an empty collection', function () {
            $collection = new TagCollection();

            expect($collection)->toBeInstanceOf(TagCollection::class);
        });

        test('creates a collection with single tag', function () {
            $tag = TestEntityFactory::createTag();
            $collection = new TagCollection($tag);

            expect($collection)->toBeInstanceOf(TagCollection::class);
            expect($collection->count())->toBe(1);
        });

        test('creates a collection with multiple tags', function () {
            $tag1 = TestEntityFactory::createTag();
            $tag2 = TestEntityFactory::createTag();
            $tag3 = TestEntityFactory::createTag();
            $collection = new TagCollection($tag1, $tag2, $tag3);

            expect($collection)->toBeInstanceOf(TagCollection::class);
            expect($collection->count())->toBe(3);
        });

        test('stores tags in the collection', function () {
            $tag1 = TestEntityFactory::createTag();
            $tag2 = TestEntityFactory::createTag();
            $collection = new TagCollection($tag1, $tag2);

            $array = $collection->toArray();
            expect($array[0])->toBe($tag1);
            expect($array[1])->toBe($tag2);
        });
    });

    describe('Countable interface', function () {
        test('count returns zero for empty collection', function () {
            $collection = new TagCollection();

            expect($collection->count())->toBe(0);
            expect(count($collection))->toBe(0);
        });

        test('count returns correct number of tags', function () {
            $tags = [
                TestEntityFactory::createTag(),
                TestEntityFactory::createTag(),
                TestEntityFactory::createTag(),
            ];
            $collection = new TagCollection(...$tags);

            expect($collection->count())->toBe(3);
            expect(count($collection))->toBe(3);
        });

        test('count works after collection construction with variadic args', function () {
            $collection = new TagCollection(
                TestEntityFactory::createTag(),
                TestEntityFactory::createTag()
            );

            expect($collection->count())->toBe(2);
        });
    });

    describe('IteratorAggregate interface', function () {
        test('can iterate over empty collection', function () {
            $collection = new TagCollection();

            $iterations = 0;
            foreach ($collection as $tag) {
                $iterations++;
            }

            expect($iterations)->toBe(0);
        });

        test('can iterate over collection with tags', function () {
            $tag1 = TestEntityFactory::createTag();
            $tag2 = TestEntityFactory::createTag();
            $tag3 = TestEntityFactory::createTag();
            $collection = new TagCollection($tag1, $tag2, $tag3);

            $iterations = 0;
            $iteratedTags = [];
            foreach ($collection as $tag) {
                $iterations++;
                $iteratedTags[] = $tag;
            }

            expect($iterations)->toBe(3);
            expect($iteratedTags[0])->toBe($tag1);
            expect($iteratedTags[1])->toBe($tag2);
            expect($iteratedTags[2])->toBe($tag3);
        });

        test('maintains order during iteration', function () {
            $tags = [
                TestEntityFactory::createTag(),
                TestEntityFactory::createTag(),
                TestEntityFactory::createTag(),
            ];
            $collection = new TagCollection(...$tags);

            $iteratedTags = [];
            foreach ($collection as $tag) {
                $iteratedTags[] = $tag;
            }

            expect($iteratedTags)->toBe($tags);
        });
    });

    describe('isEmpty method', function () {
        test('returns true for empty collection', function () {
            $collection = new TagCollection();

            expect($collection->isEmpty())->toBeTrue();
        });

        test('returns false for collection with one tag', function () {
            $tag = TestEntityFactory::createTag();
            $collection = new TagCollection($tag);

            expect($collection->isEmpty())->toBeFalse();
        });

        test('returns false for collection with multiple tags', function () {
            $collection = new TagCollection(
                TestEntityFactory::createTag(),
                TestEntityFactory::createTag(),
                TestEntityFactory::createTag()
            );

            expect($collection->isEmpty())->toBeFalse();
        });
    });
});
