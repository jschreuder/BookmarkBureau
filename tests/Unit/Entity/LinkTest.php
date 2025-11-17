<?php

use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

describe("Link Entity", function () {
    describe("construction", function () {
        test("creates a link with all properties", function () {
            $id = Uuid::uuid4();
            $url = new Url("https://example.com");
            $title = new Title("Test Title");
            $description = "Test Description";
            $icon = new Icon("test-icon");
            $createdAt = new DateTimeImmutable("2024-01-01 10:00:00");
            $updatedAt = new DateTimeImmutable("2024-01-01 12:00:00");

            $link = new Link(
                $id,
                $url,
                $title,
                $description,
                $icon,
                $createdAt,
                $updatedAt,
                new TagCollection(),
            );

            expect($link)->toBeInstanceOf(Link::class);
        });

        test(
            "stores all properties correctly during construction",
            function () {
                $id = Uuid::uuid4();
                $url = new Url("https://example.com");
                $title = new Title("Test Title");
                $description = "Test Description";
                $icon = new Icon("test-icon");
                $createdAt = new DateTimeImmutable("2024-01-01 10:00:00");
                $updatedAt = new DateTimeImmutable("2024-01-01 12:00:00");

                $link = new Link(
                    $id,
                    $url,
                    $title,
                    $description,
                    $icon,
                    $createdAt,
                    $updatedAt,
                    new TagCollection(),
                );

                expect($link->linkId)->toBe($id);
                expect($link->url)->toBe($url);
                expect($link->title)->toBe($title);
                expect($link->description)->toBe($description);
                expect($link->icon)->toBe($icon);
                expect($link->createdAt)->toBe($createdAt);
                expect($link->updatedAt)->toBe($updatedAt);
            },
        );
    });

    describe("ID getter", function () {
        test("getId returns the UUID", function () {
            $id = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $id);

            expect($link->linkId)->toBe($id);
            expect($link->linkId)->toBeInstanceOf(UuidInterface::class);
        });
    });

    describe("URL getter and setter", function () {
        test("getUrl returns the URL", function () {
            $url = new Url("https://example.com/page");
            $link = TestEntityFactory::createLink(url: $url);

            expect($link->url)->toBe($url);
            expect($link->url)->toBeInstanceOf(Url::class);
        });

        test("setUrl updates the URL", function () {
            $link = TestEntityFactory::createLink();
            $newUrl = new Url("https://newexample.com");

            $link->url = $newUrl;

            expect($link->url)->toBe($newUrl);
        });

        test("setUrl calls markAsUpdated", function () {
            $link = TestEntityFactory::createLink();
            $originalUpdatedAt = $link->updatedAt;

            $newUrl = new Url("https://newexample.com");
            $link->url = $newUrl;

            expect($link->updatedAt)->toBeInstanceOf(DateTimeInterface::class);
            expect($link->updatedAt->getTimestamp())->toBeGreaterThan(
                $originalUpdatedAt->getTimestamp(),
            );
        });
    });

    describe("title getter and setter", function () {
        test("getTitle returns the title", function () {
            $title = new Title("My Bookmark Title");
            $link = TestEntityFactory::createLink(title: $title);

            expect($link->title)->toBe($title);
        });

        test("setTitle updates the title", function () {
            $link = TestEntityFactory::createLink();
            $newTitle = new Title("Updated Title");

            $link->title = $newTitle;

            expect($link->title)->toBe($newTitle);
        });

        test("setTitle calls markAsUpdated", function () {
            $link = TestEntityFactory::createLink();
            $originalUpdatedAt = $link->updatedAt;

            $link->title = new Title("New Title");

            expect($link->updatedAt->getTimestamp())->toBeGreaterThan(
                $originalUpdatedAt->getTimestamp(),
            );
        });
    });

    describe("description getter and setter", function () {
        test("getting description returns the description", function () {
            $description = "A detailed description of the link";
            $link = TestEntityFactory::createLink(description: $description);

            expect($link->description)->toBe($description);
        });

        test("setting description updates the description", function () {
            $link = TestEntityFactory::createLink();
            $newDescription = "Updated description";

            $link->description = $newDescription;

            expect($link->description)->toBe($newDescription);
        });

        test("setting description calls markAsUpdated", function () {
            $link = TestEntityFactory::createLink();
            $originalUpdatedAt = $link->updatedAt;

            $link->description = "New description";

            expect($link->updatedAt->getTimestamp())->toBeGreaterThan(
                $originalUpdatedAt->getTimestamp(),
            );
        });

        test("setting description works with empty string", function () {
            $link = TestEntityFactory::createLink();

            $link->description = "";

            expect($link->description)->toBe("");
        });

        test("setting description works with long text", function () {
            $link = TestEntityFactory::createLink();
            $longDescription = str_repeat("Lorem ipsum dolor sit amet. ", 100);

            $link->description = $longDescription;

            expect($link->description)->toBe($longDescription);
        });
    });

    describe("icon getter and setter", function () {
        test("getting icon returns the icon", function () {
            $icon = new Icon("bookmark-icon");
            $link = TestEntityFactory::createLink(icon: $icon);

            expect($link->icon)->toBe($icon);
        });

        test("setting icon updates the icon", function () {
            $link = TestEntityFactory::createLink();
            $newIcon = new Icon("new-icon");

            $link->icon = $newIcon;

            expect($link->icon)->toBe($newIcon);
        });

        test("setting icon calls markAsUpdated", function () {
            $link = TestEntityFactory::createLink();
            $originalUpdatedAt = $link->updatedAt;

            $link->icon = new Icon("new-icon");

            expect($link->updatedAt->getTimestamp())->toBeGreaterThan(
                $originalUpdatedAt->getTimestamp(),
            );
        });

        test("setting icon works with null", function () {
            $link = TestEntityFactory::createLink();

            $link->icon = null;

            expect($link->icon)->toBeNull();
        });

        test("setIcon works with URL-like strings", function () {
            $link = TestEntityFactory::createLink();
            $iconUrl = new Icon("https://example.com/icon.png");

            $link->icon = $iconUrl;

            expect($link->icon)->toBe($iconUrl);
        });
    });

    describe("createdAt getter", function () {
        test("getCreatedAt returns the creation timestamp", function () {
            $createdAt = new DateTimeImmutable("2024-01-01 10:00:00");
            $link = TestEntityFactory::createLink(createdAt: $createdAt);

            expect($link->createdAt)->toBe($createdAt);
            expect($link->createdAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test("createdAt is readonly and cannot be modified", function () {
            $link = TestEntityFactory::createLink();

            expect(
                fn() => ($link->createdAt = new DateTimeImmutable()),
            )->toThrow(Error::class);
        });
    });

    describe("updatedAt getter", function () {
        test("getUpdatedAt returns the update timestamp", function () {
            $updatedAt = new DateTimeImmutable("2024-01-01 12:00:00");
            $link = TestEntityFactory::createLink(updatedAt: $updatedAt);

            expect($link->updatedAt)->toBe($updatedAt);
            expect($link->updatedAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test("updatedAt is updated when properties change", function () {
            $link = TestEntityFactory::createLink();
            $originalUpdatedAt = $link->updatedAt;

            $link->title = new Title("New Title");

            expect($link->updatedAt)->not->toBe($originalUpdatedAt);
            expect($link->updatedAt->getTimestamp())->toBeGreaterThan(
                $originalUpdatedAt->getTimestamp(),
            );
        });
    });

    describe("markAsUpdated method", function () {
        test("markAsUpdated updates the updatedAt timestamp", function () {
            $link = TestEntityFactory::createLink();
            $originalUpdatedAt = $link->updatedAt;

            $link->markAsUpdated();

            expect($link->updatedAt)->not->toBe($originalUpdatedAt);
            expect($link->updatedAt->getTimestamp())->toBeGreaterThan(
                $originalUpdatedAt->getTimestamp(),
            );
        });

        test("markAsUpdated sets updatedAt to current time", function () {
            $link = TestEntityFactory::createLink();
            $beforeMark = new DateTimeImmutable();

            $link->markAsUpdated();

            $afterMark = new DateTimeImmutable();

            expect($link->updatedAt->getTimestamp())
                ->toBeGreaterThanOrEqual($beforeMark->getTimestamp())
                ->toBeLessThanOrEqual($afterMark->getTimestamp());
        });

        test("markAsUpdated creates a DateTimeImmutable instance", function () {
            $link = TestEntityFactory::createLink();

            $link->markAsUpdated();

            expect($link->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
        });
    });

    describe("multiple setters", function () {
        test("can update multiple properties in sequence", function () {
            $link = TestEntityFactory::createLink();
            $newUrl = new Url("https://updated.com");
            $newTitle = new Title("Updated Title");
            $newDescription = "Updated Description";
            $newIcon = new Icon("updated-icon");

            $link->url = $newUrl;
            $link->title = $newTitle;
            $link->description = $newDescription;
            $link->icon = $newIcon;

            expect($link->url)->toBe($newUrl);
            expect($link->title)->toBe($newTitle);
            expect($link->description)->toBe($newDescription);
            expect($link->icon)->toBe($newIcon);
        });
    });

    describe("immutability constraints", function () {
        test("linkId cannot be modified", function () {
            $link = TestEntityFactory::createLink();

            expect(fn() => ($link->linkId = Uuid::uuid4()))->toThrow(
                Error::class,
            );
        });
    });

    describe("equals method", function () {
        test("equals returns true for same link ID", function () {
            $linkId = Uuid::uuid4();
            $url = new Url("https://example.com");
            $title = new Title("Test Link");

            $link1 = new Link(
                $linkId,
                $url,
                $title,
                "Description 1",
                new Icon("icon1"),
                new DateTimeImmutable("2024-01-01 10:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new \jschreuder\BookmarkBureau\Composite\TagCollection(),
            );

            $link2 = new Link(
                $linkId,
                new Url("https://different.com"),
                new Title("Different Title"),
                "Description 2",
                new Icon("icon2"),
                new DateTimeImmutable("2024-01-02 10:00:00"),
                new DateTimeImmutable("2024-01-02 12:00:00"),
                new \jschreuder\BookmarkBureau\Composite\TagCollection(),
            );

            expect($link1->equals($link2))->toBeTrue();
        });

        test("equals returns false for different link IDs", function () {
            $linkId1 = Uuid::uuid4();
            $linkId2 = Uuid::uuid4();
            $url = new Url("https://example.com");
            $title = new Title("Test Link");

            $link1 = new Link(
                $linkId1,
                $url,
                $title,
                "Description",
                new Icon("icon"),
                new DateTimeImmutable("2024-01-01 10:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new \jschreuder\BookmarkBureau\Composite\TagCollection(),
            );

            $link2 = new Link(
                $linkId2,
                $url,
                $title,
                "Description",
                new Icon("icon"),
                new DateTimeImmutable("2024-01-01 10:00:00"),
                new DateTimeImmutable("2024-01-01 12:00:00"),
                new \jschreuder\BookmarkBureau\Composite\TagCollection(),
            );

            expect($link1->equals($link2))->toBeFalse();
        });

        test(
            "equals returns false when comparing with different type",
            function () {
                $link = TestEntityFactory::createLink();
                $category = TestEntityFactory::createCategory();

                expect($link->equals($category))->toBeFalse();
            },
        );

        test(
            "equals returns false when comparing with non-entity object",
            function () {
                $link = TestEntityFactory::createLink();
                $stdObject = new stdClass();

                expect($link->equals($stdObject))->toBeFalse();
            },
        );
    });
});
