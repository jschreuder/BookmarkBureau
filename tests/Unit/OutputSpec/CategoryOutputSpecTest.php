<?php

use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\OutputSpec\CategoryOutputSpec;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;

describe('CategoryOutputSpec', function () {
    describe('initialization', function () {
        test('creates OutputSpec instance', function () {
            $spec = new CategoryOutputSpec();

            expect($spec)->toBeInstanceOf(CategoryOutputSpec::class);
        });

        test('implements OutputSpecInterface', function () {
            $spec = new CategoryOutputSpec();

            expect($spec)->toBeInstanceOf(\jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface::class);
        });

        test('is readonly', function () {
            $spec = new CategoryOutputSpec();

            expect($spec)->toBeInstanceOf(CategoryOutputSpec::class);
        });
    });

    describe('supports method', function () {
        test('supports Category objects', function () {
            $spec = new CategoryOutputSpec();
            $category = TestEntityFactory::createCategory();

            expect($spec->supports($category))->toBeTrue();
        });

        test('does not support Link objects', function () {
            $spec = new CategoryOutputSpec();
            $link = TestEntityFactory::createLink();

            expect($spec->supports($link))->toBeFalse();
        });

        test('does not support stdClass objects', function () {
            $spec = new CategoryOutputSpec();

            expect($spec->supports(new stdClass()))->toBeFalse();
        });
    });

    describe('transform method', function () {
        test('transforms Category to array with all fields', function () {
            $spec = new CategoryOutputSpec();
            $category = TestEntityFactory::createCategory();

            $result = $spec->transform($category);

            expect($result)->toBeArray();
            expect($result)->toHaveKeys(['id', 'dashboard_id', 'title', 'color', 'sort_order', 'created_at', 'updated_at']);
        });

        test('returns category ID as string', function () {
            $spec = new CategoryOutputSpec();
            $category = TestEntityFactory::createCategory();

            $result = $spec->transform($category);

            expect($result['id'])->toBeString();
            expect($result['id'])->toBe($category->categoryId->toString());
        });

        test('returns dashboard ID as string', function () {
            $spec = new CategoryOutputSpec();
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(dashboard: $dashboard);

            $result = $spec->transform($category);

            expect($result['dashboard_id'])->toBeString();
            expect($result['dashboard_id'])->toBe($dashboard->dashboardId->toString());
        });

        test('returns category title as string', function () {
            $spec = new CategoryOutputSpec();
            $title = new Title('Useful Resources');
            $category = TestEntityFactory::createCategory(title: $title);

            $result = $spec->transform($category);

            expect($result['title'])->toBeString();
            expect($result['title'])->toBe('Useful Resources');
        });

        test('returns category color as string when present', function () {
            $spec = new CategoryOutputSpec();
            $color = new HexColor('#FF5733');
            $category = TestEntityFactory::createCategory(color: $color);

            $result = $spec->transform($category);

            expect($result['color'])->toBeString();
            expect($result['color'])->toBe('#FF5733');
        });

        test('returns category color as null when not present', function () {
            $spec = new CategoryOutputSpec();
            $category = TestEntityFactory::createCategory(color: null);

            $result = $spec->transform($category);

            expect($result['color'])->toBeNull();
        });

        test('returns sort_order as integer', function () {
            $spec = new CategoryOutputSpec();
            $category = TestEntityFactory::createCategory(sortOrder: 42);

            $result = $spec->transform($category);

            expect($result['sort_order'])->toBeInt();
            expect($result['sort_order'])->toBe(42);
        });

        test('returns sort_order as zero', function () {
            $spec = new CategoryOutputSpec();
            $category = TestEntityFactory::createCategory(sortOrder: 0);

            $result = $spec->transform($category);

            expect($result['sort_order'])->toBeInt();
            expect($result['sort_order'])->toBe(0);
        });

        test('returns sort_order with negative values', function () {
            $spec = new CategoryOutputSpec();
            $category = TestEntityFactory::createCategory(sortOrder: -10);

            $result = $spec->transform($category);

            expect($result['sort_order'])->toBeInt();
            expect($result['sort_order'])->toBe(-10);
        });

        test('returns created_at in ATOM format', function () {
            $spec = new CategoryOutputSpec();
            $createdAt = new DateTimeImmutable('2024-03-05 09:15:00', new DateTimeZone('UTC'));
            $category = TestEntityFactory::createCategory(createdAt: $createdAt);

            $result = $spec->transform($category);

            expect($result['created_at'])->toBeString();
            expect($result['created_at'])->toBe($createdAt->format(DateTimeInterface::ATOM));
        });

        test('returns updated_at in ATOM format', function () {
            $spec = new CategoryOutputSpec();
            $updatedAt = new DateTimeImmutable('2024-03-05 14:22:00', new DateTimeZone('UTC'));
            $category = TestEntityFactory::createCategory(updatedAt: $updatedAt);

            $result = $spec->transform($category);

            expect($result['updated_at'])->toBeString();
            expect($result['updated_at'])->toBe($updatedAt->format(DateTimeInterface::ATOM));
        });

        test('throws InvalidArgumentException when transforming unsupported object', function () {
            $spec = new CategoryOutputSpec();
            $link = TestEntityFactory::createLink();

            expect(fn() => $spec->transform($link))
                ->toThrow(InvalidArgumentException::class);
        });

        test('exception message contains class name and unsupported type', function () {
            $spec = new CategoryOutputSpec();
            $link = TestEntityFactory::createLink();

            expect(fn() => $spec->transform($link))
                ->toThrow(InvalidArgumentException::class)
                ->and(fn() => $spec->transform($link))
                ->toThrow(function (InvalidArgumentException $e) {
                    return str_contains($e->getMessage(), CategoryOutputSpec::class)
                        && str_contains($e->getMessage(), Link::class);
                });
        });
    });

    describe('edge cases', function () {
        test('handles category with different color values', function () {
            $spec = new CategoryOutputSpec();
            $colors = ['#000000', '#FFFFFF', '#FF5733', '#33FF57', '#3357FF'];

            foreach ($colors as $colorValue) {
                $color = new HexColor($colorValue);
                $category = TestEntityFactory::createCategory(color: $color);

                $result = $spec->transform($category);

                expect($result['color'])->toBe($colorValue);
            }
        });

        test('handles category with large sort order values', function () {
            $spec = new CategoryOutputSpec();
            $category = TestEntityFactory::createCategory(sortOrder: 999999);

            $result = $spec->transform($category);

            expect($result['sort_order'])->toBe(999999);
        });

        test('handles category with different dashboards', function () {
            $spec = new CategoryOutputSpec();
            $dashboard1 = TestEntityFactory::createDashboard();
            $dashboard2 = TestEntityFactory::createDashboard();

            $category1 = TestEntityFactory::createCategory(dashboard: $dashboard1);
            $category2 = TestEntityFactory::createCategory(dashboard: $dashboard2);

            $result1 = $spec->transform($category1);
            $result2 = $spec->transform($category2);

            expect($result1['dashboard_id'])->not->toBe($result2['dashboard_id']);
            expect($result1['dashboard_id'])->toBe($dashboard1->dashboardId->toString());
            expect($result2['dashboard_id'])->toBe($dashboard2->dashboardId->toString());
        });

        test('handles multiple categories independently', function () {
            $spec = new CategoryOutputSpec();
            $category1 = TestEntityFactory::createCategory();
            $category2 = TestEntityFactory::createCategory();

            $result1 = $spec->transform($category1);
            $result2 = $spec->transform($category2);

            expect($result1['id'])->not->toBe($result2['id']);
            expect($result1['id'])->toBe($category1->categoryId->toString());
            expect($result2['id'])->toBe($category2->categoryId->toString());
        });

        test('handles category with special characters in title', function () {
            $spec = new CategoryOutputSpec();
            $title = new Title('Category with "quotes", \'apostrophes\' & symbols!');
            $category = TestEntityFactory::createCategory(title: $title);

            $result = $spec->transform($category);

            expect($result['title'])->toBe('Category with "quotes", \'apostrophes\' & symbols!');
        });

        test('handles category with unicode characters in title', function () {
            $spec = new CategoryOutputSpec();
            $title = new Title('文件 Documents 文件');
            $category = TestEntityFactory::createCategory(title: $title);

            $result = $spec->transform($category);

            expect($result['title'])->toBe('文件 Documents 文件');
        });

        test('handles category with different datetime zones', function () {
            $spec = new CategoryOutputSpec();
            $createdAt = new DateTimeImmutable('2024-03-05 09:15:00', new DateTimeZone('America/New_York'));
            $category = TestEntityFactory::createCategory(createdAt: $createdAt);

            $result = $spec->transform($category);

            expect($result['created_at'])->toBe($createdAt->format(DateTimeInterface::ATOM));
        });
    });

    describe('integration with OutputSpecInterface', function () {
        test('transform method signature matches interface', function () {
            $spec = new CategoryOutputSpec();
            $category = TestEntityFactory::createCategory();

            $result = $spec->transform($category);

            expect($result)->toBeArray();
        });

        test('can be used polymorphically through interface', function () {
            $spec = new CategoryOutputSpec();
            $interface = $spec;
            $category = TestEntityFactory::createCategory();

            expect($interface)->toBeInstanceOf(\jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface::class);

            $result = $interface->transform($category);

            expect($result)->toBeArray();
        });
    });
});
