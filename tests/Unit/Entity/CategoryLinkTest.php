<?php

use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use Ramsey\Uuid\Rfc4122\UuidV4;

describe('CategoryLink Entity', function () {
    function createCategoryLinkTestCategory(
        ?Dashboard $dashboard = null,
        ?string $name = null,
        ?HexColor $color = null,
        ?int $sortOrder = null,
        ?DateTimeInterface $createdAt = null
    ): Category {
        return new Category(
            categoryId: UuidV4::uuid4(),
            dashboard: $dashboard ?? new Dashboard(
                dashboardId: UuidV4::uuid4(),
                title: 'Test Dashboard',
                description: 'Test Description',
                icon: 'dashboard-icon',
                createdAt: new DateTimeImmutable('2024-01-01 12:00:00'),
                updatedAt: new DateTimeImmutable('2024-01-01 12:00:00')
            ),
            title: $name ?? 'Test Category',
            color: $color ?? new HexColor('#FF5733'),
            sortOrder: $sortOrder ?? 0,
            createdAt: $createdAt ?? new DateTimeImmutable('2024-01-01 12:00:00'),
            updatedAt: new DateTimeImmutable('2024-01-01 12:00:00')
        );
    }

    function createCategoryLinkTestLink(
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

    function createCategoryLinkTestCategoryLink(
        ?Category $category = null,
        ?Link $link = null,
        ?int $sortOrder = null,
        ?DateTimeInterface $createdAt = null
    ): CategoryLink {
        return new CategoryLink(
            category: $category ?? createCategoryLinkTestCategory(),
            link: $link ?? createCategoryLinkTestLink(),
            sortOrder: $sortOrder ?? 0,
            createdAt: $createdAt ?? new DateTimeImmutable('2024-01-01 12:00:00')
        );
    }

    describe('construction', function () {
        test('creates a category link with all properties', function () {
            $category = createCategoryLinkTestCategory();
            $link = createCategoryLinkTestLink();
            $sortOrder = 5;
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');

            $categoryLink = new CategoryLink($category, $link, $sortOrder, $createdAt);

            expect($categoryLink)->toBeInstanceOf(CategoryLink::class);
        });

        test('stores all properties correctly during construction', function () {
            $category = createCategoryLinkTestCategory();
            $link = createCategoryLinkTestLink();
            $sortOrder = 5;
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');

            $categoryLink = new CategoryLink($category, $link, $sortOrder, $createdAt);

            expect($categoryLink->category)->toBe($category);
            expect($categoryLink->link)->toBe($link);
            expect($categoryLink->sortOrder)->toBe($sortOrder);
            expect($categoryLink->createdAt)->toBe($createdAt);
        });
    });

    describe('category getter', function () {
        test('getCategory returns the Category object', function () {
            $category = createCategoryLinkTestCategory();
            $categoryLink = createCategoryLinkTestCategoryLink(category: $category);

            expect($categoryLink->category)->toBe($category);
            expect($categoryLink->category)->toBeInstanceOf(Category::class);
        });

        test('category is readonly and cannot be modified', function () {
            $categoryLink = createCategoryLinkTestCategoryLink();

            expect(fn() => $categoryLink->category = createCategoryLinkTestCategory())
                ->toThrow(Error::class);
        });
    });

    describe('link getter', function () {
        test('getLink returns the Link object', function () {
            $link = createCategoryLinkTestLink();
            $categoryLink = createCategoryLinkTestCategoryLink(link: $link);

            expect($categoryLink->link)->toBe($link);
            expect($categoryLink->link)->toBeInstanceOf(Link::class);
        });

        test('link is readonly and cannot be modified', function () {
            $categoryLink = createCategoryLinkTestCategoryLink();

            expect(fn() => $categoryLink->link = createCategoryLinkTestLink())
                ->toThrow(Error::class);
        });
    });

    describe('sortOrder getter and setter', function () {
        test('getSortOrder returns the sort order', function () {
            $sortOrder = 42;
            $categoryLink = createCategoryLinkTestCategoryLink(sortOrder: $sortOrder);

            expect($categoryLink->sortOrder)->toBe($sortOrder);
        });

        test('setSortOrder updates the sort order', function () {
            $categoryLink = createCategoryLinkTestCategoryLink();
            $newSortOrder = 10;

            $categoryLink->sortOrder = $newSortOrder;

            expect($categoryLink->sortOrder)->toBe($newSortOrder);
        });

        test('setSortOrder works with zero', function () {
            $categoryLink = createCategoryLinkTestCategoryLink();

            $categoryLink->sortOrder = 0;

            expect($categoryLink->sortOrder)->toBe(0);
        });

        test('setSortOrder works with negative values', function () {
            $categoryLink = createCategoryLinkTestCategoryLink();

            $categoryLink->sortOrder = -5;

            expect($categoryLink->sortOrder)->toBe(-5);
        });
    });

    describe('createdAt getter', function () {
        test('getCreatedAt returns the creation timestamp', function () {
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $categoryLink = createCategoryLinkTestCategoryLink(createdAt: $createdAt);

            expect($categoryLink->createdAt)->toBe($createdAt);
            expect($categoryLink->createdAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('createdAt is readonly and cannot be modified', function () {
            $categoryLink = createCategoryLinkTestCategoryLink();

            expect(fn() => $categoryLink->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('immutability constraints', function () {
        test('category cannot be modified', function () {
            $categoryLink = createCategoryLinkTestCategoryLink();

            expect(fn() => $categoryLink->category = createCategoryLinkTestCategory())
                ->toThrow(Error::class);
        });

        test('link cannot be modified', function () {
            $categoryLink = createCategoryLinkTestCategoryLink();

            expect(fn() => $categoryLink->link = createCategoryLinkTestLink())
                ->toThrow(Error::class);
        });

        test('createdAt cannot be modified', function () {
            $categoryLink = createCategoryLinkTestCategoryLink();

            expect(fn() => $categoryLink->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('multiple setters', function () {
        test('can update sortOrder multiple times', function () {
            $categoryLink = createCategoryLinkTestCategoryLink();
            $sortOrder1 = 5;
            $sortOrder2 = 15;
            $sortOrder3 = 25;

            $categoryLink->sortOrder = $sortOrder1;
            expect($categoryLink->sortOrder)->toBe($sortOrder1);

            $categoryLink->sortOrder = $sortOrder2;
            expect($categoryLink->sortOrder)->toBe($sortOrder2);

            $categoryLink->sortOrder = $sortOrder3;
            expect($categoryLink->sortOrder)->toBe($sortOrder3);
        });
    });

    describe('relationship integrity', function () {
        test('can create multiple category links for the same category', function () {
            $category = createCategoryLinkTestCategory();
            $link1 = createCategoryLinkTestLink();
            $link2 = createCategoryLinkTestLink();

            $categoryLink1 = createCategoryLinkTestCategoryLink(category: $category, link: $link1);
            $categoryLink2 = createCategoryLinkTestCategoryLink(category: $category, link: $link2);

            expect($categoryLink1->category)->toBe($categoryLink2->category);
            expect($categoryLink1->link)->not->toBe($categoryLink2->link);
        });

        test('can create multiple category links for the same link', function () {
            $category1 = createCategoryLinkTestCategory();
            $category2 = createCategoryLinkTestCategory();
            $link = createCategoryLinkTestLink();

            $categoryLink1 = createCategoryLinkTestCategoryLink(category: $category1, link: $link);
            $categoryLink2 = createCategoryLinkTestCategoryLink(category: $category2, link: $link);

            expect($categoryLink1->link)->toBe($categoryLink2->link);
            expect($categoryLink1->category)->not->toBe($categoryLink2->category);
        });
    });
});
