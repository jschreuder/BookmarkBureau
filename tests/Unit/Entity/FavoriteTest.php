<?php

use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Entity\Link;

describe('Favorite Entity', function () {
    describe('construction', function () {
        test('creates a favorite with all properties', function () {
            $dashboard = TestEntityFactory::createDashboard();
            $link = TestEntityFactory::createLink();
            $sortOrder = 5;
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');

            $favorite = new Favorite($dashboard, $link, $sortOrder, $createdAt);

            expect($favorite)->toBeInstanceOf(Favorite::class);
        });

        test('stores all properties correctly during construction', function () {
            $dashboard = TestEntityFactory::createDashboard();
            $link = TestEntityFactory::createLink();
            $sortOrder = 5;
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');

            $favorite = new Favorite($dashboard, $link, $sortOrder, $createdAt);

            expect($favorite->dashboard)->toBe($dashboard);
            expect($favorite->link)->toBe($link);
            expect($favorite->sortOrder)->toBe($sortOrder);
            expect($favorite->createdAt)->toBe($createdAt);
        });
    });

    describe('dashboard getter', function () {
        test('getDashboard returns the Dashboard object', function () {
            $dashboard = TestEntityFactory::createDashboard();
            $favorite = TestEntityFactory::createFavorite(dashboard: $dashboard);

            expect($favorite->dashboard)->toBe($dashboard);
            expect($favorite->dashboard)->toBeInstanceOf(Dashboard::class);
        });

        test('dashboard is readonly and cannot be modified', function () {
            $favorite = TestEntityFactory::createFavorite();

            expect(fn() => $favorite->dashboard = TestEntityFactory::createDashboard())
                ->toThrow(Error::class);
        });
    });

    describe('link getter', function () {
        test('getLink returns the Link object', function () {
            $link = TestEntityFactory::createLink();
            $favorite = TestEntityFactory::createFavorite(link: $link);

            expect($favorite->link)->toBe($link);
            expect($favorite->link)->toBeInstanceOf(Link::class);
        });

        test('link is readonly and cannot be modified', function () {
            $favorite = TestEntityFactory::createFavorite();

            expect(fn() => $favorite->link = TestEntityFactory::createLink())
                ->toThrow(Error::class);
        });
    });

    describe('sortOrder getter and setter', function () {
        test('getSortOrder returns the sort order', function () {
            $sortOrder = 42;
            $favorite = TestEntityFactory::createFavorite(sortOrder: $sortOrder);

            expect($favorite->sortOrder)->toBe($sortOrder);
        });

        test('setSortOrder updates the sort order', function () {
            $favorite = TestEntityFactory::createFavorite();
            $newSortOrder = 10;

            $favorite->sortOrder = $newSortOrder;

            expect($favorite->sortOrder)->toBe($newSortOrder);
        });

        test('setSortOrder works with zero', function () {
            $favorite = TestEntityFactory::createFavorite();

            $favorite->sortOrder = 0;

            expect($favorite->sortOrder)->toBe(0);
        });

        test('setSortOrder works with negative values', function () {
            $favorite = TestEntityFactory::createFavorite();

            $favorite->sortOrder = -5;

            expect($favorite->sortOrder)->toBe(-5);
        });
    });

    describe('createdAt getter', function () {
        test('getCreatedAt returns the creation timestamp', function () {
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $favorite = TestEntityFactory::createFavorite(createdAt: $createdAt);

            expect($favorite->createdAt)->toBe($createdAt);
            expect($favorite->createdAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('createdAt is readonly and cannot be modified', function () {
            $favorite = TestEntityFactory::createFavorite();

            expect(fn() => $favorite->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('immutability constraints', function () {
        test('dashboard cannot be modified', function () {
            $favorite = TestEntityFactory::createFavorite();

            expect(fn() => $favorite->dashboard = TestEntityFactory::createDashboard())
                ->toThrow(Error::class);
        });

        test('link cannot be modified', function () {
            $favorite = TestEntityFactory::createFavorite();

            expect(fn() => $favorite->link = TestEntityFactory::createLink())
                ->toThrow(Error::class);
        });

        test('createdAt cannot be modified', function () {
            $favorite = TestEntityFactory::createFavorite();

            expect(fn() => $favorite->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('multiple setters', function () {
        test('can update sortOrder multiple times', function () {
            $favorite = TestEntityFactory::createFavorite();
            $sortOrder1 = 5;
            $sortOrder2 = 15;
            $sortOrder3 = 25;

            $favorite->sortOrder = $sortOrder1;
            expect($favorite->sortOrder)->toBe($sortOrder1);

            $favorite->sortOrder = $sortOrder2;
            expect($favorite->sortOrder)->toBe($sortOrder2);

            $favorite->sortOrder = $sortOrder3;
            expect($favorite->sortOrder)->toBe($sortOrder3);
        });
    });
});
