<?php

use jschreuder\BookmarkBureau\Collection\DashboardCollection;

describe('DashboardCollection', function () {
    describe('construction', function () {
        test('creates an empty collection', function () {
            $collection = new DashboardCollection();

            expect($collection)->toBeInstanceOf(DashboardCollection::class);
        });

        test('creates a collection with single dashboard', function () {
            $dashboard = TestEntityFactory::createDashboard();
            $collection = new DashboardCollection($dashboard);

            expect($collection)->toBeInstanceOf(DashboardCollection::class);
            expect($collection->count())->toBe(1);
        });

        test('creates a collection with multiple dashboards', function () {
            $dashboard1 = TestEntityFactory::createDashboard();
            $dashboard2 = TestEntityFactory::createDashboard();
            $dashboard3 = TestEntityFactory::createDashboard();
            $collection = new DashboardCollection($dashboard1, $dashboard2, $dashboard3);

            expect($collection)->toBeInstanceOf(DashboardCollection::class);
            expect($collection->count())->toBe(3);
        });

        test('stores dashboards in the collection', function () {
            $dashboard1 = TestEntityFactory::createDashboard();
            $dashboard2 = TestEntityFactory::createDashboard();
            $collection = new DashboardCollection($dashboard1, $dashboard2);

            $array = $collection->toArray();
            expect($array[0])->toBe($dashboard1);
            expect($array[1])->toBe($dashboard2);
        });
    });

    describe('Countable interface', function () {
        test('count returns zero for empty collection', function () {
            $collection = new DashboardCollection();

            expect($collection->count())->toBe(0);
            expect(count($collection))->toBe(0);
        });

        test('count returns correct number of dashboards', function () {
            $dashboards = [
                TestEntityFactory::createDashboard(),
                TestEntityFactory::createDashboard(),
                TestEntityFactory::createDashboard(),
            ];
            $collection = new DashboardCollection(...$dashboards);

            expect($collection->count())->toBe(3);
            expect(count($collection))->toBe(3);
        });

        test('count works after collection construction with variadic args', function () {
            $collection = new DashboardCollection(
                TestEntityFactory::createDashboard(),
                TestEntityFactory::createDashboard()
            );

            expect($collection->count())->toBe(2);
        });
    });

    describe('IteratorAggregate interface', function () {
        test('can iterate over empty collection', function () {
            $collection = new DashboardCollection();

            $iterations = 0;
            foreach ($collection as $dashboard) {
                $iterations++;
            }

            expect($iterations)->toBe(0);
        });

        test('can iterate over collection with dashboards', function () {
            $dashboard1 = TestEntityFactory::createDashboard();
            $dashboard2 = TestEntityFactory::createDashboard();
            $dashboard3 = TestEntityFactory::createDashboard();
            $collection = new DashboardCollection($dashboard1, $dashboard2, $dashboard3);

            $iterations = 0;
            $iteratedDashboards = [];
            foreach ($collection as $dashboard) {
                $iterations++;
                $iteratedDashboards[] = $dashboard;
            }

            expect($iterations)->toBe(3);
            expect($iteratedDashboards[0])->toBe($dashboard1);
            expect($iteratedDashboards[1])->toBe($dashboard2);
            expect($iteratedDashboards[2])->toBe($dashboard3);
        });

        test('maintains order during iteration', function () {
            $dashboards = [
                TestEntityFactory::createDashboard(),
                TestEntityFactory::createDashboard(),
                TestEntityFactory::createDashboard(),
            ];
            $collection = new DashboardCollection(...$dashboards);

            $iteratedDashboards = [];
            foreach ($collection as $dashboard) {
                $iteratedDashboards[] = $dashboard;
            }

            expect($iteratedDashboards)->toBe($dashboards);
        });
    });

    describe('isEmpty method', function () {
        test('returns true for empty collection', function () {
            $collection = new DashboardCollection();

            expect($collection->isEmpty())->toBeTrue();
        });

        test('returns false for collection with one dashboard', function () {
            $dashboard = TestEntityFactory::createDashboard();
            $collection = new DashboardCollection($dashboard);

            expect($collection->isEmpty())->toBeFalse();
        });

        test('returns false for collection with multiple dashboards', function () {
            $collection = new DashboardCollection(
                TestEntityFactory::createDashboard(),
                TestEntityFactory::createDashboard(),
                TestEntityFactory::createDashboard()
            );

            expect($collection->isEmpty())->toBeFalse();
        });
    });
});
