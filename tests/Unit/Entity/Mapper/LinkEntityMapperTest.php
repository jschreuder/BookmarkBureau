<?php

use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\TagEntityMapper;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use Ramsey\Uuid\Uuid;

describe("LinkEntityMapper", function () {
    describe("getFields", function () {
        test("returns all link field names", function () {
            $mapper = new LinkEntityMapper();
            $fields = $mapper->getFields();

            expect($fields)->toBe([
                "link_id",
                "url",
                "title",
                "description",
                "icon",
                "created_at",
                "updated_at",
            ]);
        });
    });

    describe("getDbFields", function () {
        test(
            "returns same fields as getFields since no entity references",
            function () {
                $mapper = new LinkEntityMapper();
                $fields = $mapper->getFields();
                $dbFields = $mapper->getDbFields();

                expect($dbFields)->toBe($fields);
                expect($dbFields)->toBe([
                    "link_id",
                    "url",
                    "title",
                    "description",
                    "icon",
                    "created_at",
                    "updated_at",
                ]);
            },
        );
    });

    describe("supports", function () {
        test("returns true for Link entities", function () {
            $mapper = new LinkEntityMapper();
            $link = TestEntityFactory::createLink();

            expect($mapper->supports($link))->toBeTrue();
        });

        test("returns false for non-Link entities", function () {
            $mapper = new LinkEntityMapper();
            $dashboard = TestEntityFactory::createDashboard();

            expect($mapper->supports($dashboard))->toBeFalse();
        });
    });

    describe("mapToEntity", function () {
        test("maps row data to Link entity", function () {
            $mapper = new LinkEntityMapper();
            $linkId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable("2024-01-15 10:30:00");
            $updatedAt = new DateTimeImmutable("2024-01-20 14:45:00");

            $data = [
                "link_id" => $linkId->getBytes(),
                "url" => "https://example.com",
                "title" => "Example Site",
                "description" => "A sample website",
                "icon" => "https://example.com/icon.png",
                "created_at" => "2024-01-15 10:30:00",
                "updated_at" => "2024-01-20 14:45:00",
            ];

            $link = $mapper->mapToEntity($data);

            expect($link)->toBeInstanceOf(Link::class);
            expect($link->linkId->equals($linkId))->toBeTrue();
            expect((string) $link->url)->toBe("https://example.com");
            expect((string) $link->title)->toBe("Example Site");
            expect($link->description)->toBe("A sample website");
            expect((string) $link->icon)->toBe("https://example.com/icon.png");
            expect($link->createdAt->format("Y-m-d H:i:s"))->toBe(
                "2024-01-15 10:30:00",
            );
            expect($link->updatedAt->format("Y-m-d H:i:s"))->toBe(
                "2024-01-20 14:45:00",
            );
        });

        test("maps row data with null icon to Link entity", function () {
            $mapper = new LinkEntityMapper();
            $linkId = Uuid::uuid4();

            $data = [
                "link_id" => $linkId->getBytes(),
                "url" => "https://example.com",
                "title" => "Example Site",
                "description" => "A sample website",
                "icon" => null,
                "created_at" => "2024-01-15 10:30:00",
                "updated_at" => "2024-01-20 14:45:00",
            ];

            $link = $mapper->mapToEntity($data);

            expect($link->icon)->toBeNull();
        });

        test(
            "throws InvalidArgumentException when required fields are missing",
            function () {
                $mapper = new LinkEntityMapper();

                $data = [
                    "link_id" => Uuid::uuid4()->getBytes(),
                    "url" => "https://example.com",
                    // Missing: title, description, icon, created_at, updated_at
                ];

                expect(fn() => $mapper->mapToEntity($data))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );

        test(
            "throws InvalidArgumentException with helpful message about missing fields",
            function () {
                $mapper = new LinkEntityMapper();

                $data = [
                    "link_id" => Uuid::uuid4()->getBytes(),
                ];

                expect(fn() => $mapper->mapToEntity($data))->toThrow(
                    InvalidArgumentException::class,
                    "requires fields",
                );
            },
        );
    });

    describe("mapToRow", function () {
        test("maps Link entity to row array", function () {
            $mapper = new LinkEntityMapper();
            $link = TestEntityFactory::createLink(
                url: new Url("https://test.com"),
                title: new Title("Test Title"),
                description: "Test Description",
                icon: new Icon("test-icon"),
            );

            $row = $mapper->mapToRow($link);

            expect($row)->toHaveKey("link_id");
            expect($row)->toHaveKey("url");
            expect($row)->toHaveKey("title");
            expect($row)->toHaveKey("description");
            expect($row)->toHaveKey("icon");
            expect($row)->toHaveKey("created_at");
            expect($row)->toHaveKey("updated_at");

            expect($row["link_id"])->toBe($link->linkId->getBytes());
            expect($row["url"])->toBe("https://test.com");
            expect($row["title"])->toBe("Test Title");
            expect($row["description"])->toBe("Test Description");
            expect($row["icon"])->toBe("test-icon");
        });

        test("maps Link entity with null icon to row array", function () {
            $mapper = new LinkEntityMapper();
            $link = TestEntityFactory::createLink(icon: null);

            $row = $mapper->mapToRow($link);

            expect($row["icon"])->toBeNull();
        });

        test("formats timestamps correctly", function () {
            $mapper = new LinkEntityMapper();
            $createdAt = new DateTimeImmutable("2024-01-15 10:30:45");
            $updatedAt = new DateTimeImmutable("2024-01-20 14:45:30");
            $link = TestEntityFactory::createLink(
                createdAt: $createdAt,
                updatedAt: $updatedAt,
            );

            $row = $mapper->mapToRow($link);

            expect($row["created_at"])->toBe(
                $createdAt->format(SqlFormat::TIMESTAMP),
            );
            expect($row["updated_at"])->toBe(
                $updatedAt->format(SqlFormat::TIMESTAMP),
            );
        });

        test(
            "throws InvalidArgumentException when entity is not supported",
            function () {
                $mapper = new LinkEntityMapper();
                $dashboard = TestEntityFactory::createDashboard();

                expect(fn() => $mapper->mapToRow($dashboard))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );
    });

    describe("round-trip mapping", function () {
        test("maps entity to row and back preserves all data", function () {
            $mapper = new LinkEntityMapper();
            $originalLink = TestEntityFactory::createLink(
                url: new Url("https://roundtrip.test"),
                title: new Title("Round Trip Test"),
                description: "Testing round-trip mapping",
                icon: new Icon("rt-icon"),
            );

            $row = $mapper->mapToRow($originalLink);
            $restoredLink = $mapper->mapToEntity($row);

            expect(
                $restoredLink->linkId->equals($originalLink->linkId),
            )->toBeTrue();
            expect((string) $restoredLink->url)->toBe(
                (string) $originalLink->url,
            );
            expect((string) $restoredLink->title)->toBe(
                (string) $originalLink->title,
            );
            expect($restoredLink->description)->toBe(
                $originalLink->description,
            );
            expect((string) $restoredLink->icon)->toBe(
                (string) $originalLink->icon,
            );
        });

        test("round-trip mapping with null icon preserves null", function () {
            $mapper = new LinkEntityMapper();
            $originalLink = TestEntityFactory::createLink(icon: null);

            $row = $mapper->mapToRow($originalLink);
            $restoredLink = $mapper->mapToEntity($row);

            expect($restoredLink->icon)->toBeNull();
        });
    });
});
