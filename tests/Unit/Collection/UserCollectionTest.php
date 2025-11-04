<?php

use jschreuder\BookmarkBureau\Collection\UserCollection;

describe('UserCollection', function () {
    describe('construction', function () {
        test('creates an empty collection', function () {
            $collection = new UserCollection();

            expect($collection)->toBeInstanceOf(UserCollection::class);
        });

        test('creates a collection with single user', function () {
            $user = TestEntityFactory::createUser();
            $collection = new UserCollection($user);

            expect($collection)->toBeInstanceOf(UserCollection::class);
            expect($collection->count())->toBe(1);
        });

        test('creates a collection with multiple users', function () {
            $user1 = TestEntityFactory::createUser();
            $user2 = TestEntityFactory::createUser();
            $user3 = TestEntityFactory::createUser();
            $collection = new UserCollection($user1, $user2, $user3);

            expect($collection)->toBeInstanceOf(UserCollection::class);
            expect($collection->count())->toBe(3);
        });

        test('stores users in the collection', function () {
            $user1 = TestEntityFactory::createUser();
            $user2 = TestEntityFactory::createUser();
            $collection = new UserCollection($user1, $user2);

            $array = $collection->toArray();
            expect($array[0])->toBe($user1);
            expect($array[1])->toBe($user2);
        });
    });

    describe('Countable interface', function () {
        test('count returns zero for empty collection', function () {
            $collection = new UserCollection();

            expect($collection->count())->toBe(0);
            expect(count($collection))->toBe(0);
        });

        test('count returns correct number of users', function () {
            $users = [
                TestEntityFactory::createUser(),
                TestEntityFactory::createUser(),
                TestEntityFactory::createUser(),
            ];
            $collection = new UserCollection(...$users);

            expect($collection->count())->toBe(3);
            expect(count($collection))->toBe(3);
        });

        test('count works after collection construction with variadic args', function () {
            $collection = new UserCollection(
                TestEntityFactory::createUser(),
                TestEntityFactory::createUser()
            );

            expect($collection->count())->toBe(2);
        });
    });

    describe('IteratorAggregate interface', function () {
        test('can iterate over empty collection', function () {
            $collection = new UserCollection();

            $iterations = 0;
            foreach ($collection as $user) {
                $iterations++;
            }

            expect($iterations)->toBe(0);
        });

        test('can iterate over collection with users', function () {
            $user1 = TestEntityFactory::createUser();
            $user2 = TestEntityFactory::createUser();
            $user3 = TestEntityFactory::createUser();
            $collection = new UserCollection($user1, $user2, $user3);

            $iterations = 0;
            $iteratedUsers = [];
            foreach ($collection as $user) {
                $iterations++;
                $iteratedUsers[] = $user;
            }

            expect($iterations)->toBe(3);
            expect($iteratedUsers[0])->toBe($user1);
            expect($iteratedUsers[1])->toBe($user2);
            expect($iteratedUsers[2])->toBe($user3);
        });

        test('maintains order during iteration', function () {
            $users = [
                TestEntityFactory::createUser(),
                TestEntityFactory::createUser(),
                TestEntityFactory::createUser(),
            ];
            $collection = new UserCollection(...$users);

            $iteratedUsers = [];
            foreach ($collection as $user) {
                $iteratedUsers[] = $user;
            }

            expect($iteratedUsers)->toBe($users);
        });
    });

    describe('isEmpty method', function () {
        test('returns true for empty collection', function () {
            $collection = new UserCollection();

            expect($collection->isEmpty())->toBeTrue();
        });

        test('returns false for collection with one user', function () {
            $user = TestEntityFactory::createUser();
            $collection = new UserCollection($user);

            expect($collection->isEmpty())->toBeFalse();
        });

        test('returns false for collection with multiple users', function () {
            $collection = new UserCollection(
                TestEntityFactory::createUser(),
                TestEntityFactory::createUser(),
                TestEntityFactory::createUser()
            );

            expect($collection->isEmpty())->toBeFalse();
        });
    });
});
