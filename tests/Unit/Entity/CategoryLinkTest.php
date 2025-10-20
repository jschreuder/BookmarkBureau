<?php

use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Entity\Link;

describe('CategoryLink Entity', function () {



    describe('construction', function () {
        test('creates a category link with all properties', function () {
            $category = TestEntityFactory::createCategory();
            $link = TestEntityFactory::createLink();
            $sortOrder = 5;
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');

            $categoryLink = new CategoryLink($category, $link, $sortOrder, $createdAt);

            expect($categoryLink)->toBeInstanceOf(CategoryLink::class);
        });

        test('stores all properties correctly during construction', function () {
            $category = TestEntityFactory::createCategory();
            $link = TestEntityFactory::createLink();
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
            $category = TestEntityFactory::createCategory();
            $categoryLink = TestEntityFactory::createCategoryLink(category: $category);

            expect($categoryLink->category)->toBe($category);
            expect($categoryLink->category)->toBeInstanceOf(Category::class);
        });

        test('category is readonly and cannot be modified', function () {
            $categoryLink = TestEntityFactory::createCategoryLink();

            expect(fn() => $categoryLink->category = TestEntityFactory::createCategory())
                ->toThrow(Error::class);
        });
    });

    describe('link getter', function () {
        test('getLink returns the Link object', function () {
            $link = TestEntityFactory::createLink();
            $categoryLink = TestEntityFactory::createCategoryLink(link: $link);

            expect($categoryLink->link)->toBe($link);
            expect($categoryLink->link)->toBeInstanceOf(Link::class);
        });

        test('link is readonly and cannot be modified', function () {
            $categoryLink = TestEntityFactory::createCategoryLink();

            expect(fn() => $categoryLink->link = TestEntityFactory::createLink())
                ->toThrow(Error::class);
        });
    });

    describe('sortOrder getter and setter', function () {
        test('getSortOrder returns the sort order', function () {
            $sortOrder = 42;
            $categoryLink = TestEntityFactory::createCategoryLink(sortOrder: $sortOrder);

            expect($categoryLink->sortOrder)->toBe($sortOrder);
        });

        test('setSortOrder updates the sort order', function () {
            $categoryLink = TestEntityFactory::createCategoryLink();
            $newSortOrder = 10;

            $categoryLink->sortOrder = $newSortOrder;

            expect($categoryLink->sortOrder)->toBe($newSortOrder);
        });

        test('setSortOrder works with zero', function () {
            $categoryLink = TestEntityFactory::createCategoryLink();

            $categoryLink->sortOrder = 0;

            expect($categoryLink->sortOrder)->toBe(0);
        });

        test('setSortOrder works with negative values', function () {
            $categoryLink = TestEntityFactory::createCategoryLink();

            $categoryLink->sortOrder = -5;

            expect($categoryLink->sortOrder)->toBe(-5);
        });
    });

    describe('createdAt getter', function () {
        test('getCreatedAt returns the creation timestamp', function () {
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $categoryLink = TestEntityFactory::createCategoryLink(createdAt: $createdAt);

            expect($categoryLink->createdAt)->toBe($createdAt);
            expect($categoryLink->createdAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('createdAt is readonly and cannot be modified', function () {
            $categoryLink = TestEntityFactory::createCategoryLink();

            expect(fn() => $categoryLink->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('immutability constraints', function () {
        test('category cannot be modified', function () {
            $categoryLink = TestEntityFactory::createCategoryLink();

            expect(fn() => $categoryLink->category = TestEntityFactory::createCategory())
                ->toThrow(Error::class);
        });

        test('link cannot be modified', function () {
            $categoryLink = TestEntityFactory::createCategoryLink();

            expect(fn() => $categoryLink->link = TestEntityFactory::createLink())
                ->toThrow(Error::class);
        });

        test('createdAt cannot be modified', function () {
            $categoryLink = TestEntityFactory::createCategoryLink();

            expect(fn() => $categoryLink->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('multiple setters', function () {
        test('can update sortOrder multiple times', function () {
            $categoryLink = TestEntityFactory::createCategoryLink();
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
            $category = TestEntityFactory::createCategory();
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();

            $categoryLink1 = TestEntityFactory::createCategoryLink(category: $category, link: $link1);
            $categoryLink2 = TestEntityFactory::createCategoryLink(category: $category, link: $link2);

            expect($categoryLink1->category)->toBe($categoryLink2->category);
            expect($categoryLink1->link)->not->toBe($categoryLink2->link);
        });

        test('can create multiple category links for the same link', function () {
            $category1 = TestEntityFactory::createCategory();
            $category2 = TestEntityFactory::createCategory();
            $link = TestEntityFactory::createLink();

            $categoryLink1 = TestEntityFactory::createCategoryLink(category: $category1, link: $link);
            $categoryLink2 = TestEntityFactory::createCategoryLink(category: $category2, link: $link);

            expect($categoryLink1->link)->toBe($categoryLink2->link);
            expect($categoryLink1->category)->not->toBe($categoryLink2->category);
        });
    });
});
