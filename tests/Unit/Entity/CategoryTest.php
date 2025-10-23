<?php

use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

describe('Category Entity', function () {


    describe('construction', function () {
        test('creates a category with all properties', function () {
            $id = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard();
            $title = new Title('Test Category');
            $color = new HexColor('#FF5733');
            $sortOrder = 5;
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');

            $category = new Category($id, $dashboard, $title, $color, $sortOrder, $createdAt, $updatedAt);

            expect($category)->toBeInstanceOf(Category::class);
        });

        test('stores all properties correctly during construction', function () {
            $id = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard();
            $title = new Title('Test Category');
            $color = new HexColor('#FF5733');
            $sortOrder = 5;
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');

            $category = new Category($id, $dashboard, $title, $color, $sortOrder, $createdAt, $updatedAt);

            expect($category->categoryId)->toBe($id);
            expect($category->dashboard)->toBe($dashboard);
            expect($category->title)->toBe($title);
            expect($category->color)->toBe($color);
            expect($category->sortOrder)->toBe($sortOrder);
            expect($category->createdAt)->toBe($createdAt);
            expect($category->updatedAt)->toBe($updatedAt);
        });
    });

    describe('ID getter', function () {
        test('getting categoryId returns the UUID', function () {
            $id = Uuid::uuid4();
            $category = TestEntityFactory::createCategory(id: $id);

            expect($category->categoryId)->toBe($id);
            expect($category->categoryId)->toBeInstanceOf(UuidInterface::class);
        });
    });

    describe('dashboard getter', function () {
        test('getting dashboard returns the Dashboard object', function () {
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(dashboard: $dashboard);

            expect($category->dashboard)->toBe($dashboard);
            expect($category->dashboard)->toBeInstanceOf(Dashboard::class);
        });

        test('dashboard is readonly and cannot be modified', function () {
            $category = TestEntityFactory::createCategory();

            expect(fn() => $category->dashboard = TestEntityFactory::createDashboard())
                ->toThrow(Error::class);
        });
    });

    describe('title getter and setter', function () {
        test('getting title returns the title', function () {
            $title = new Title('My Category');
            $category = TestEntityFactory::createCategory(title: $title);

            expect($category->title)->toBe($title);
        });

        test('setting title updates the title', function () {
            $category = TestEntityFactory::createCategory();
            $newTitle = new Title('Updated Category');

            $category->title = $newTitle;

            expect($category->title)->toBe($newTitle);
        });

        test('setting title calls markAsUpdated', function () {
            $category = TestEntityFactory::createCategory();
            $originalUpdatedAt = $category->updatedAt;

            $category->title = new Title('New Name');

            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('color getter and setter', function () {
        test('getting color returns the color', function () {
            $color = new HexColor('#FF5733');
            $category = TestEntityFactory::createCategory(color: $color);

            expect($category->color)->toBe($color);
        });

        test('setting color updates the color', function () {
            $category = TestEntityFactory::createCategory();
            $newColor = new HexColor('#33FF57');

            $category->color = $newColor;

            expect($category->color)->toBe($newColor);
        });

        test('setting color calls markAsUpdated', function () {
            $category = TestEntityFactory::createCategory();
            $originalUpdatedAt = $category->updatedAt;

            $category->color = new HexColor('#33FF57');

            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setting color works with empty string', function () {
            $category = TestEntityFactory::createCategory();

            $category->color = null;

            expect($category->color)->toBeNull();
        });
    });

    describe('sortOrder getter and setter', function () {
        test('getting sortOrder returns the sort order', function () {
            $sortOrder = 42;
            $category = TestEntityFactory::createCategory(sortOrder: $sortOrder);

            expect($category->sortOrder)->toBe($sortOrder);
        });

        test('setting sortOrder updates the sort order', function () {
            $category = TestEntityFactory::createCategory();
            $newSortOrder = 10;

            $category->sortOrder = $newSortOrder;

            expect($category->sortOrder)->toBe($newSortOrder);
        });

        test('setting sortOrder calls markAsUpdated', function () {
            $category = TestEntityFactory::createCategory();
            $originalUpdatedAt = $category->updatedAt;

            $category->sortOrder = 100;

            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setting sortOrder works with zero', function () {
            $category = TestEntityFactory::createCategory();

            $category->sortOrder = 0;

            expect($category->sortOrder)->toBe(0);
        });

        test('setting sortOrder works with negative values', function () {
            $category = TestEntityFactory::createCategory();

            $category->sortOrder = -5;

            expect($category->sortOrder)->toBe(-5);
        });
    });

    describe('createdAt getter', function () {
        test('getting createdAt returns the creation timestamp', function () {
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $category = TestEntityFactory::createCategory(createdAt: $createdAt);

            expect($category->createdAt)->toBe($createdAt);
            expect($category->createdAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('createdAt is readonly and cannot be modified', function () {
            $category = TestEntityFactory::createCategory();

            expect(fn() => $category->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('updatedAt getter', function () {
        test('getting updatedAt returns the update timestamp', function () {
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');
            $category = TestEntityFactory::createCategory(updatedAt: $updatedAt);

            expect($category->updatedAt)->toBe($updatedAt);
            expect($category->updatedAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('updatedAt is updated when properties change', function () {
            $category = TestEntityFactory::createCategory();
            $originalUpdatedAt = $category->updatedAt;

            $category->title = new Title('New Name');

            expect($category->updatedAt)
                ->not->toBe($originalUpdatedAt);
            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('markAsUpdated method', function () {
        test('markAsUpdated updates the updatedAt timestamp', function () {
            $category = TestEntityFactory::createCategory();
            $originalUpdatedAt = $category->updatedAt;

            $category->markAsUpdated();

            expect($category->updatedAt)
                ->not->toBe($originalUpdatedAt);
            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('markAsUpdated sets updatedAt to current time', function () {
            $category = TestEntityFactory::createCategory();
            $beforeMark = new DateTimeImmutable();

            $category->markAsUpdated();

            $afterMark = new DateTimeImmutable();

            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThanOrEqual($beforeMark->getTimestamp())
                ->toBeLessThanOrEqual($afterMark->getTimestamp());
        });

        test('markAsUpdated creates a DateTimeImmutable instance', function () {
            $category = TestEntityFactory::createCategory();

            $category->markAsUpdated();

            expect($category->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
        });
    });

    describe('multiple setters', function () {
        test('can update multiple properties in sequence', function () {
            $category = TestEntityFactory::createCategory();
            $newTitle = new Title('Updated Category');
            $newColor = new HexColor('#33FF57');
            $newSortOrder = 15;

            $category->title = $newTitle;
            $category->color = $newColor;
            $category->sortOrder = $newSortOrder;

            expect($category->title)->toBe($newTitle);
            expect($category->color)->toBe($newColor);
            expect($category->sortOrder)->toBe($newSortOrder);
        });
    });

    describe('immutability constraints', function () {
        test('categoryId cannot be modified', function () {
            $category = TestEntityFactory::createCategory();

            expect(fn() => $category->categoryId = Uuid::uuid4())
                ->toThrow(Error::class);
        });
    });
});
