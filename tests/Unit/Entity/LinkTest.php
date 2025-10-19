<?php

use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;

describe('Link Entity', function () {
    function createTestLink(
        ?UuidInterface $id = null,
        ?Url $url = null,
        ?string $title = null,
        ?string $description = null,
        ?string $icon = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null
    ): Link {
        return new Link(
            linkId: $id ?? UuidV4::uuid4(),
            url: $url ?? new Url('https://example.com'),
            title: $title ?? 'Example Title',
            description: $description ?? 'Example Description',
            icon: $icon ?? 'icon-example',
            createdAt: $createdAt ?? new DateTimeImmutable('2024-01-01 12:00:00'),
            updatedAt: $updatedAt ?? new DateTimeImmutable('2024-01-01 12:00:00')
        );
    }

    describe('construction', function () {
        test('creates a link with all properties', function () {
            $id = UuidV4::uuid4();
            $url = new Url('https://example.com');
            $title = 'Test Title';
            $description = 'Test Description';
            $icon = 'test-icon';
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');

            $link = new Link($id, $url, $title, $description, $icon, $createdAt, $updatedAt);

            expect($link)->toBeInstanceOf(Link::class);
        });

        test('stores all properties correctly during construction', function () {
            $id = UuidV4::uuid4();
            $url = new Url('https://example.com');
            $title = 'Test Title';
            $description = 'Test Description';
            $icon = 'test-icon';
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');

            $link = new Link($id, $url, $title, $description, $icon, $createdAt, $updatedAt);

            expect($link->linkId)->toBe($id);
            expect($link->url)->toBe($url);
            expect($link->title)->toBe($title);
            expect($link->description)->toBe($description);
            expect($link->icon)->toBe($icon);
            expect($link->createdAt)->toBe($createdAt);
            expect($link->updatedAt)->toBe($updatedAt);
        });
    });

    describe('ID getter', function () {
        test('getId returns the UUID', function () {
            $id = UuidV4::uuid4();
            $link = createTestLink(id: $id);

            expect($link->linkId)->toBe($id);
            expect($link->linkId)->toBeInstanceOf(UuidInterface::class);
        });
    });

    describe('URL getter and setter', function () {
        test('getUrl returns the URL', function () {
            $url = new Url('https://example.com/page');
            $link = createTestLink(url: $url);

            expect($link->url)->toBe($url);
            expect($link->url)->toBeInstanceOf(Url::class);
        });

        test('setUrl updates the URL', function () {
            $link = createTestLink();
            $newUrl = new Url('https://newexample.com');

            $link->url = $newUrl;

            expect($link->url)->toBe($newUrl);
        });

        test('setUrl calls markAsUpdated', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->updatedAt;

            $newUrl = new Url('https://newexample.com');
            $link->url = $newUrl;

            expect($link->updatedAt)->toBeInstanceOf(DateTimeInterface::class);
            expect($link->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('title getter and setter', function () {
        test('getTitle returns the title', function () {
            $title = 'My Bookmark Title';
            $link = createTestLink(title: $title);

            expect($link->title)->toBe($title);
        });

        test('setTitle updates the title', function () {
            $link = createTestLink();
            $newTitle = 'Updated Title';

            $link->title = $newTitle;

            expect($link->title)->toBe($newTitle);
        });

        test('setTitle calls markAsUpdated', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->updatedAt;

            $link->title = 'New Title';

            expect($link->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('description getter and setter', function () {
        test('getDescription returns the description', function () {
            $description = 'A detailed description of the link';
            $link = createTestLink(description: $description);

            expect($link->description)->toBe($description);
        });

        test('setDescription updates the description', function () {
            $link = createTestLink();
            $newDescription = 'Updated description';

            $link->description = $newDescription;

            expect($link->description)->toBe($newDescription);
        });

        test('setDescription calls markAsUpdated', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->updatedAt;

            $link->description = 'New description';

            expect($link->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setDescription works with empty string', function () {
            $link = createTestLink();

            $link->description = '';

            expect($link->description)->toBe('');
        });

        test('setDescription works with long text', function () {
            $link = createTestLink();
            $longDescription = str_repeat('Lorem ipsum dolor sit amet. ', 100);

            $link->description = $longDescription;

            expect($link->description)->toBe($longDescription);
        });
    });

    describe('icon getter and setter', function () {
        test('getIcon returns the icon', function () {
            $icon = 'bookmark-icon';
            $link = createTestLink(icon: $icon);

            expect($link->icon)->toBe($icon);
        });

        test('setIcon updates the icon', function () {
            $link = createTestLink();
            $newIcon = 'new-icon';

            $link->icon = $newIcon;

            expect($link->icon)->toBe($newIcon);
        });

        test('setIcon calls markAsUpdated', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->updatedAt;

            $link->icon = 'new-icon';

            expect($link->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setIcon works with empty string', function () {
            $link = createTestLink();

            $link->icon = '';

            expect($link->icon)->toBe('');
        });

        test('setIcon works with URL-like strings', function () {
            $link = createTestLink();
            $iconUrl = 'https://example.com/icon.png';

            $link->icon = $iconUrl;

            expect($link->icon)->toBe($iconUrl);
        });
    });

    describe('createdAt getter', function () {
        test('getCreatedAt returns the creation timestamp', function () {
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $link = createTestLink(createdAt: $createdAt);

            expect($link->createdAt)->toBe($createdAt);
            expect($link->createdAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('createdAt is readonly and cannot be modified', function () {
            $link = createTestLink();

            expect(fn() => $link->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('updatedAt getter', function () {
        test('getUpdatedAt returns the update timestamp', function () {
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');
            $link = createTestLink(updatedAt: $updatedAt);

            expect($link->updatedAt)->toBe($updatedAt);
            expect($link->updatedAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('updatedAt is updated when properties change', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->updatedAt;

            $link->title = 'New Title';

            expect($link->updatedAt)
                ->not->toBe($originalUpdatedAt);
            expect($link->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('markAsUpdated method', function () {
        test('markAsUpdated updates the updatedAt timestamp', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->updatedAt;

            $link->markAsUpdated();

            expect($link->updatedAt)
                ->not->toBe($originalUpdatedAt);
            expect($link->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('markAsUpdated sets updatedAt to current time', function () {
            $link = createTestLink();
            $beforeMark = new DateTimeImmutable();

            $link->markAsUpdated();

            $afterMark = new DateTimeImmutable();

            expect($link->updatedAt->getTimestamp())
                ->toBeGreaterThanOrEqual($beforeMark->getTimestamp())
                ->toBeLessThanOrEqual($afterMark->getTimestamp());
        });

        test('markAsUpdated creates a DateTimeImmutable instance', function () {
            $link = createTestLink();

            $link->markAsUpdated();

            expect($link->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
        });
    });

    describe('multiple setters', function () {
        test('can update multiple properties in sequence', function () {
            $link = createTestLink();
            $newUrl = new Url('https://updated.com');
            $newTitle = 'Updated Title';
            $newDescription = 'Updated Description';
            $newIcon = 'updated-icon';

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

    describe('immutability constraints', function () {
        test('linkId cannot be modified', function () {
            $link = createTestLink();

            expect(fn() => $link->linkId = UuidV4::uuid4())
                ->toThrow(Error::class);
        });
    });
});
