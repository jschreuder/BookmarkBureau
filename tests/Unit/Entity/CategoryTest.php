<?php

use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;

describe('Category Entity', function () {
    function createCategoryTestDashboard(
        ?UuidInterface $id = null,
        ?string $name = null,
        ?string $description = null,
        ?string $icon = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null
    ): Dashboard {
        return new Dashboard(
            dashboardId: $id ?? UuidV4::uuid4(),
            name: $name ?? 'Test Dashboard',
            description: $description ?? 'Test Description',
            icon: $icon ?? 'dashboard-icon',
            createdAt: $createdAt ?? new DateTimeImmutable('2024-01-01 12:00:00'),
            updatedAt: $updatedAt ?? new DateTimeImmutable('2024-01-01 12:00:00')
        );
    }

    function createTestCategory(
        ?UuidInterface $id = null,
        ?Dashboard $dashboard = null,
        ?string $name = null,
        ?HexColor $color = null,
        ?int $sortOrder = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null
    ): Category {
        return new Category(
            categoryId: $id ?? UuidV4::uuid4(),
            dashboard: $dashboard ?? createCategoryTestDashboard(),
            name: $name ?? 'Test Category',
            color: $color,
            sortOrder: $sortOrder ?? 0,
            createdAt: $createdAt ?? new DateTimeImmutable('2024-01-01 12:00:00'),
            updatedAt: $updatedAt ?? new DateTimeImmutable('2024-01-01 12:00:00')
        );
    }

    describe('construction', function () {
        test('creates a category with all properties', function () {
            $id = UuidV4::uuid4();
            $dashboard = createCategoryTestDashboard();
            $name = 'Test Category';
            $color = new HexColor('#FF5733');
            $sortOrder = 5;
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');

            $category = new Category($id, $dashboard, $name, $color, $sortOrder, $createdAt, $updatedAt);

            expect($category)->toBeInstanceOf(Category::class);
        });

        test('stores all properties correctly during construction', function () {
            $id = UuidV4::uuid4();
            $dashboard = createCategoryTestDashboard();
            $name = 'Test Category';
            $color = new HexColor('#FF5733');
            $sortOrder = 5;
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');

            $category = new Category($id, $dashboard, $name, $color, $sortOrder, $createdAt, $updatedAt);

            expect($category->categoryId)->toBe($id);
            expect($category->dashboard)->toBe($dashboard);
            expect($category->name)->toBe($name);
            expect($category->color)->toBe($color);
            expect($category->sortOrder)->toBe($sortOrder);
            expect($category->createdAt)->toBe($createdAt);
            expect($category->updatedAt)->toBe($updatedAt);
        });
    });

    describe('ID getter', function () {
        test('getCategoryId returns the UUID', function () {
            $id = UuidV4::uuid4();
            $category = createTestCategory(id: $id);

            expect($category->categoryId)->toBe($id);
            expect($category->categoryId)->toBeInstanceOf(UuidInterface::class);
        });
    });

    describe('dashboard getter', function () {
        test('getDashboard returns the Dashboard object', function () {
            $dashboard = createCategoryTestDashboard();
            $category = createTestCategory(dashboard: $dashboard);

            expect($category->dashboard)->toBe($dashboard);
            expect($category->dashboard)->toBeInstanceOf(Dashboard::class);
        });

        test('dashboard is readonly and cannot be modified', function () {
            $category = createTestCategory();

            expect(fn() => $category->dashboard = createCategoryTestDashboard())
                ->toThrow(Error::class);
        });
    });

    describe('name getter and setter', function () {
        test('getName returns the name', function () {
            $name = 'My Category';
            $category = createTestCategory(name: $name);

            expect($category->name)->toBe($name);
        });

        test('setName updates the name', function () {
            $category = createTestCategory();
            $newName = 'Updated Category';

            $category->name = $newName;

            expect($category->name)->toBe($newName);
        });

        test('setName calls markAsUpdated', function () {
            $category = createTestCategory();
            $originalUpdatedAt = $category->updatedAt;

            $category->name = 'New Name';

            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('color getter and setter', function () {
        test('getting color returns the color', function () {
            $color = new HexColor('#FF5733');
            $category = createTestCategory(color: $color);

            expect($category->color)->toBe($color);
        });

        test('setting color updates the color', function () {
            $category = createTestCategory();
            $newColor = new HexColor('#33FF57');

            $category->color = $newColor;

            expect($category->color)->toBe($newColor);
        });

        test('setting color calls markAsUpdated', function () {
            $category = createTestCategory();
            $originalUpdatedAt = $category->updatedAt;

            $category->color = new HexColor('#33FF57');

            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setting color works with empty string', function () {
            $category = createTestCategory();

            $category->color = null;

            expect($category->color)->toBeNull();
        });
    });

    describe('sortOrder getter and setter', function () {
        test('getting sortOrder returns the sort order', function () {
            $sortOrder = 42;
            $category = createTestCategory(sortOrder: $sortOrder);

            expect($category->sortOrder)->toBe($sortOrder);
        });

        test('setting sortOrder updates the sort order', function () {
            $category = createTestCategory();
            $newSortOrder = 10;

            $category->sortOrder = $newSortOrder;

            expect($category->sortOrder)->toBe($newSortOrder);
        });

        test('setting sortOrder calls markAsUpdated', function () {
            $category = createTestCategory();
            $originalUpdatedAt = $category->updatedAt;

            $category->sortOrder = 100;

            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setting sortOrder works with zero', function () {
            $category = createTestCategory();

            $category->sortOrder = 0;

            expect($category->sortOrder)->toBe(0);
        });

        test('setting sortOrder works with negative values', function () {
            $category = createTestCategory();

            $category->sortOrder = -5;

            expect($category->sortOrder)->toBe(-5);
        });
    });

    describe('createdAt getter', function () {
        test('getting createdAt returns the creation timestamp', function () {
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $category = createTestCategory(createdAt: $createdAt);

            expect($category->createdAt)->toBe($createdAt);
            expect($category->createdAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('createdAt is readonly and cannot be modified', function () {
            $category = createTestCategory();

            expect(fn() => $category->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('updatedAt getter', function () {
        test('getting updatedAt returns the update timestamp', function () {
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');
            $category = createTestCategory(updatedAt: $updatedAt);

            expect($category->updatedAt)->toBe($updatedAt);
            expect($category->updatedAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('updatedAt is updated when properties change', function () {
            $category = createTestCategory();
            $originalUpdatedAt = $category->updatedAt;

            $category->name = 'New Name';

            expect($category->updatedAt)
                ->not->toBe($originalUpdatedAt);
            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('markAsUpdated method', function () {
        test('markAsUpdated updates the updatedAt timestamp', function () {
            $category = createTestCategory();
            $originalUpdatedAt = $category->updatedAt;

            $category->markAsUpdated();

            expect($category->updatedAt)
                ->not->toBe($originalUpdatedAt);
            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('markAsUpdated sets updatedAt to current time', function () {
            $category = createTestCategory();
            $beforeMark = new DateTimeImmutable();

            $category->markAsUpdated();

            $afterMark = new DateTimeImmutable();

            expect($category->updatedAt->getTimestamp())
                ->toBeGreaterThanOrEqual($beforeMark->getTimestamp())
                ->toBeLessThanOrEqual($afterMark->getTimestamp());
        });

        test('markAsUpdated creates a DateTimeImmutable instance', function () {
            $category = createTestCategory();

            $category->markAsUpdated();

            expect($category->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
        });
    });

    describe('multiple setters', function () {
        test('can update multiple properties in sequence', function () {
            $category = createTestCategory();
            $newName = 'Updated Category';
            $newColor = new HexColor('#33FF57');
            $newSortOrder = 15;

            $category->name = $newName;
            $category->color = $newColor;
            $category->sortOrder = $newSortOrder;

            expect($category->name)->toBe($newName);
            expect($category->color)->toBe($newColor);
            expect($category->sortOrder)->toBe($newSortOrder);
        });
    });

    describe('immutability constraints', function () {
        test('categoryId cannot be modified', function () {
            $category = createTestCategory();

            expect(fn() => $category->categoryId = UuidV4::uuid4())
                ->toThrow(Error::class);
        });
    });
});
