<?php

use jschreuder\BookmarkBureau\Entity\Mapper\CategoryLinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Util\SqlFormat;

describe("CategoryLinkEntityMapper", function () {
    describe("getFields", function () {
        test("returns all category link field names", function () {
            $mapper = new CategoryLinkEntityMapper();
            $fields = $mapper->getFields();

            expect($fields)->toBe([
                "category_id",
                "link_id",
                "sort_order",
                "created_at",
                "category",
                "link",
            ]);
        });
    });

    describe("supports", function () {
        test("returns true for CategoryLink entities", function () {
            $mapper = new CategoryLinkEntityMapper();
            $categoryLink = TestEntityFactory::createCategoryLink();

            expect($mapper->supports($categoryLink))->toBeTrue();
        });

        test("returns false for non-CategoryLink entities", function () {
            $mapper = new CategoryLinkEntityMapper();
            $link = TestEntityFactory::createLink();

            expect($mapper->supports($link))->toBeFalse();
        });
    });

    describe("mapToEntity", function () {
        test("maps row data to CategoryLink entity", function () {
            $mapper = new CategoryLinkEntityMapper();
            $category = TestEntityFactory::createCategory();
            $link = TestEntityFactory::createLink();

            $data = [
                "category_id" => $category->categoryId->getBytes(),
                "link_id" => $link->linkId->getBytes(),
                "sort_order" => 2,
                "created_at" => "2024-03-01 08:00:00",
                "category" => $category,
                "link" => $link,
            ];

            $categoryLink = $mapper->mapToEntity($data);

            expect($categoryLink)->toBeInstanceOf(CategoryLink::class);
            expect($categoryLink->category)->toBe($category);
            expect($categoryLink->link)->toBe($link);
            expect($categoryLink->sortOrder)->toBe(2);
            expect($categoryLink->createdAt->format("Y-m-d H:i:s"))->toBe(
                "2024-03-01 08:00:00",
            );
        });

        test(
            "throws InvalidArgumentException when required fields are missing",
            function () {
                $mapper = new CategoryLinkEntityMapper();

                $data = [
                    "category_id" => "some-id",
                    "link_id" => "some-link-id",
                    // Missing: sort_order, created_at, category, link
                ];

                expect(fn() => $mapper->mapToEntity($data))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("mapToRow", function () {
        test("maps CategoryLink entity to row array", function () {
            $mapper = new CategoryLinkEntityMapper();
            $category = TestEntityFactory::createCategory();
            $link = TestEntityFactory::createLink();
            $categoryLink = TestEntityFactory::createCategoryLink(
                category: $category,
                link: $link,
                sortOrder: 4,
            );

            $row = $mapper->mapToRow($categoryLink);

            expect($row)->toHaveKey("category_id");
            expect($row)->toHaveKey("link_id");
            expect($row)->toHaveKey("sort_order");
            expect($row)->toHaveKey("created_at");

            expect($row["category_id"])->toBe(
                $category->categoryId->getBytes(),
            );
            expect($row["link_id"])->toBe($link->linkId->getBytes());
            expect($row["sort_order"])->toBe("4");
        });

        test("formats timestamps correctly", function () {
            $mapper = new CategoryLinkEntityMapper();
            $createdAt = new DateTimeImmutable("2024-03-01 08:00:45");
            $categoryLink = TestEntityFactory::createCategoryLink(
                createdAt: $createdAt,
            );

            $row = $mapper->mapToRow($categoryLink);

            expect($row["created_at"])->toBe(
                $createdAt->format(SqlFormat::TIMESTAMP),
            );
        });

        test(
            "throws InvalidArgumentException when entity is not supported",
            function () {
                $mapper = new CategoryLinkEntityMapper();
                $dashboard = TestEntityFactory::createDashboard();

                expect(fn() => $mapper->mapToRow($dashboard))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("round-trip mapping", function () {
        test("maps entity to row and back preserves all data", function () {
            $mapper = new CategoryLinkEntityMapper();
            $category = TestEntityFactory::createCategory();
            $link = TestEntityFactory::createLink();
            $originalCategoryLink = TestEntityFactory::createCategoryLink(
                category: $category,
                link: $link,
                sortOrder: 6,
            );

            $row = $mapper->mapToRow($originalCategoryLink);
            // Manually add related entities to data since mapToEntity expects them
            $row["category"] = $category;
            $row["link"] = $link;
            $restoredCategoryLink = $mapper->mapToEntity($row);

            expect($restoredCategoryLink->category)->toBe($category);
            expect($restoredCategoryLink->link)->toBe($link);
            expect($restoredCategoryLink->sortOrder)->toBe(6);
        });

        test("preserves sort order through round-trip", function () {
            $mapper = new CategoryLinkEntityMapper();
            $category = TestEntityFactory::createCategory();
            $link = TestEntityFactory::createLink();
            $originalCategoryLink = TestEntityFactory::createCategoryLink(
                category: $category,
                link: $link,
                sortOrder: 99,
            );

            $row = $mapper->mapToRow($originalCategoryLink);
            $row["category"] = $category;
            $row["link"] = $link;
            $restoredCategoryLink = $mapper->mapToEntity($row);

            expect($restoredCategoryLink->sortOrder)->toBe(99);
        });

        test("preserves created_at timestamp through round-trip", function () {
            $mapper = new CategoryLinkEntityMapper();
            $category = TestEntityFactory::createCategory();
            $link = TestEntityFactory::createLink();
            $createdAt = new DateTimeImmutable("2024-02-15 14:30:00");
            $originalCategoryLink = TestEntityFactory::createCategoryLink(
                category: $category,
                link: $link,
                createdAt: $createdAt,
            );

            $row = $mapper->mapToRow($originalCategoryLink);
            $row["category"] = $category;
            $row["link"] = $link;
            $restoredCategoryLink = $mapper->mapToEntity($row);

            expect(
                $restoredCategoryLink->createdAt->format("Y-m-d H:i:s"),
            )->toBe("2024-02-15 14:30:00");
        });
    });
});
