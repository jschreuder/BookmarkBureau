<?php

use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use Ramsey\Uuid\Rfc4122\UuidV4;

describe('Favorite Entity', function () {
    function createFavoriteTestDashboard(
        ?string $name = null,
        ?string $description = null,
        ?string $icon = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null
    ): Dashboard {
        return new Dashboard(
            dashboardId: UuidV4::uuid4(),
            title: $name ?? 'Test Dashboard',
            description: $description ?? 'Test Description',
            icon: $icon ?? 'dashboard-icon',
            createdAt: $createdAt ?? new DateTimeImmutable('2024-01-01 12:00:00'),
            updatedAt: $updatedAt ?? new DateTimeImmutable('2024-01-01 12:00:00')
        );
    }

    function createFavorieTestLink(
        ?Url $url = null,
        ?string $title = null,
        ?string $description = null,
        ?string $icon = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null
    ): Link {
        return new Link(
            linkId: UuidV4::uuid4(),
            url: $url ?? new Url('https://example.com'),
            title: $title ?? 'Example Title',
            description: $description ?? 'Example Description',
            icon: $icon ?? 'icon-example',
            createdAt: $createdAt ?? new DateTimeImmutable('2024-01-01 12:00:00'),
            updatedAt: $updatedAt ?? new DateTimeImmutable('2024-01-01 12:00:00')
        );
    }

    function createTestFavorite(
        ?Dashboard $dashboard = null,
        ?Link $link = null,
        ?int $sortOrder = null,
        ?DateTimeInterface $createdAt = null
    ): Favorite {
        return new Favorite(
            dashboard: $dashboard ?? createFavoriteTestDashboard(),
            link: $link ?? createFavorieTestLink(),
            sortOrder: $sortOrder ?? 0,
            createdAt: $createdAt ?? new DateTimeImmutable('2024-01-01 12:00:00')
        );
    }

    describe('construction', function () {
        test('creates a favorite with all properties', function () {
            $dashboard = createFavoriteTestDashboard();
            $link = createFavorieTestLink();
            $sortOrder = 5;
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');

            $favorite = new Favorite($dashboard, $link, $sortOrder, $createdAt);

            expect($favorite)->toBeInstanceOf(Favorite::class);
        });

        test('stores all properties correctly during construction', function () {
            $dashboard = createFavoriteTestDashboard();
            $link = createFavorieTestLink();
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
            $dashboard = createFavoriteTestDashboard();
            $favorite = createTestFavorite(dashboard: $dashboard);

            expect($favorite->dashboard)->toBe($dashboard);
            expect($favorite->dashboard)->toBeInstanceOf(Dashboard::class);
        });

        test('dashboard is readonly and cannot be modified', function () {
            $favorite = createTestFavorite();

            expect(fn() => $favorite->dashboard = createFavoriteTestDashboard())
                ->toThrow(Error::class);
        });
    });

    describe('link getter', function () {
        test('getLink returns the Link object', function () {
            $link = createFavorieTestLink();
            $favorite = createTestFavorite(link: $link);

            expect($favorite->link)->toBe($link);
            expect($favorite->link)->toBeInstanceOf(Link::class);
        });

        test('link is readonly and cannot be modified', function () {
            $favorite = createTestFavorite();

            expect(fn() => $favorite->link = createFavorieTestLink())
                ->toThrow(Error::class);
        });
    });

    describe('sortOrder getter and setter', function () {
        test('getSortOrder returns the sort order', function () {
            $sortOrder = 42;
            $favorite = createTestFavorite(sortOrder: $sortOrder);

            expect($favorite->sortOrder)->toBe($sortOrder);
        });

        test('setSortOrder updates the sort order', function () {
            $favorite = createTestFavorite();
            $newSortOrder = 10;

            $favorite->sortOrder = $newSortOrder;

            expect($favorite->sortOrder)->toBe($newSortOrder);
        });

        test('setSortOrder works with zero', function () {
            $favorite = createTestFavorite();

            $favorite->sortOrder = 0;

            expect($favorite->sortOrder)->toBe(0);
        });

        test('setSortOrder works with negative values', function () {
            $favorite = createTestFavorite();

            $favorite->sortOrder = -5;

            expect($favorite->sortOrder)->toBe(-5);
        });
    });

    describe('createdAt getter', function () {
        test('getCreatedAt returns the creation timestamp', function () {
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $favorite = createTestFavorite(createdAt: $createdAt);

            expect($favorite->createdAt)->toBe($createdAt);
            expect($favorite->createdAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('createdAt is readonly and cannot be modified', function () {
            $favorite = createTestFavorite();

            expect(fn() => $favorite->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('immutability constraints', function () {
        test('dashboard cannot be modified', function () {
            $favorite = createTestFavorite();

            expect(fn() => $favorite->dashboard = createFavoriteTestDashboard())
                ->toThrow(Error::class);
        });

        test('link cannot be modified', function () {
            $favorite = createTestFavorite();

            expect(fn() => $favorite->link = createFavorieTestLink())
                ->toThrow(Error::class);
        });

        test('createdAt cannot be modified', function () {
            $favorite = createTestFavorite();

            expect(fn() => $favorite->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('multiple setters', function () {
        test('can update sortOrder multiple times', function () {
            $favorite = createTestFavorite();
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
