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

            expect($link->getId())->toBe($id);
            expect($link->getUrl())->toBe($url);
            expect($link->getTitle())->toBe($title);
            expect($link->getDescription())->toBe($description);
            expect($link->getIcon())->toBe($icon);
            expect($link->getCreatedAt())->toBe($createdAt);
            expect($link->getUpdatedAt())->toBe($updatedAt);
        });
    });

    describe('ID getter', function () {
        test('getId returns the UUID', function () {
            $id = UuidV4::uuid4();
            $link = createTestLink(id: $id);

            expect($link->getId())->toBe($id);
            expect($link->getId())->toBeInstanceOf(UuidInterface::class);
        });
    });

    describe('URL getter and setter', function () {
        test('getUrl returns the URL', function () {
            $url = new Url('https://example.com/page');
            $link = createTestLink(url: $url);

            expect($link->getUrl())->toBe($url);
            expect($link->getUrl())->toBeInstanceOf(Url::class);
        });

        test('setUrl updates the URL', function () {
            $link = createTestLink();
            $newUrl = new Url('https://newexample.com');

            $link->setUrl($newUrl);

            expect($link->getUrl())->toBe($newUrl);
        });

        test('setUrl calls markAsUpdated', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->getUpdatedAt();

            $newUrl = new Url('https://newexample.com');
            $link->setUrl($newUrl);

            expect($link->getUpdatedAt())->toBeInstanceOf(DateTimeInterface::class);
            expect($link->getUpdatedAt()->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('title getter and setter', function () {
        test('getTitle returns the title', function () {
            $title = 'My Bookmark Title';
            $link = createTestLink(title: $title);

            expect($link->getTitle())->toBe($title);
        });

        test('setTitle updates the title', function () {
            $link = createTestLink();
            $newTitle = 'Updated Title';

            $link->setTitle($newTitle);

            expect($link->getTitle())->toBe($newTitle);
        });

        test('setTitle calls markAsUpdated', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->getUpdatedAt();

            $link->setTitle('New Title');

            expect($link->getUpdatedAt()->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('description getter and setter', function () {
        test('getDescription returns the description', function () {
            $description = 'A detailed description of the link';
            $link = createTestLink(description: $description);

            expect($link->getDescription())->toBe($description);
        });

        test('setDescription updates the description', function () {
            $link = createTestLink();
            $newDescription = 'Updated description';

            $link->setDescription($newDescription);

            expect($link->getDescription())->toBe($newDescription);
        });

        test('setDescription calls markAsUpdated', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->getUpdatedAt();

            $link->setDescription('New description');

            expect($link->getUpdatedAt()->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setDescription works with empty string', function () {
            $link = createTestLink();

            $link->setDescription('');

            expect($link->getDescription())->toBe('');
        });

        test('setDescription works with long text', function () {
            $link = createTestLink();
            $longDescription = str_repeat('Lorem ipsum dolor sit amet. ', 100);

            $link->setDescription($longDescription);

            expect($link->getDescription())->toBe($longDescription);
        });
    });

    describe('icon getter and setter', function () {
        test('getIcon returns the icon', function () {
            $icon = 'bookmark-icon';
            $link = createTestLink(icon: $icon);

            expect($link->getIcon())->toBe($icon);
        });

        test('setIcon updates the icon', function () {
            $link = createTestLink();
            $newIcon = 'new-icon';

            $link->setIcon($newIcon);

            expect($link->getIcon())->toBe($newIcon);
        });

        test('setIcon calls markAsUpdated', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->getUpdatedAt();

            $link->setIcon('new-icon');

            expect($link->getUpdatedAt()->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setIcon works with empty string', function () {
            $link = createTestLink();

            $link->setIcon('');

            expect($link->getIcon())->toBe('');
        });

        test('setIcon works with URL-like strings', function () {
            $link = createTestLink();
            $iconUrl = 'https://example.com/icon.png';

            $link->setIcon($iconUrl);

            expect($link->getIcon())->toBe($iconUrl);
        });
    });

    describe('createdAt getter', function () {
        test('getCreatedAt returns the creation timestamp', function () {
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $link = createTestLink(createdAt: $createdAt);

            expect($link->getCreatedAt())->toBe($createdAt);
            expect($link->getCreatedAt())->toBeInstanceOf(DateTimeInterface::class);
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

            expect($link->getUpdatedAt())->toBe($updatedAt);
            expect($link->getUpdatedAt())->toBeInstanceOf(DateTimeInterface::class);
        });

        test('updatedAt is updated when properties change', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->getUpdatedAt();

            $link->setTitle('New Title');

            expect($link->getUpdatedAt())
                ->not->toBe($originalUpdatedAt);
            expect($link->getUpdatedAt()->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('markAsUpdated method', function () {
        test('markAsUpdated updates the updatedAt timestamp', function () {
            $link = createTestLink();
            $originalUpdatedAt = $link->getUpdatedAt();

            $link->markAsUpdated();

            expect($link->getUpdatedAt())
                ->not->toBe($originalUpdatedAt);
            expect($link->getUpdatedAt()->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('markAsUpdated sets updatedAt to current time', function () {
            $link = createTestLink();
            $beforeMark = new DateTimeImmutable();

            $link->markAsUpdated();

            $afterMark = new DateTimeImmutable();

            expect($link->getUpdatedAt()->getTimestamp())
                ->toBeGreaterThanOrEqual($beforeMark->getTimestamp())
                ->toBeLessThanOrEqual($afterMark->getTimestamp());
        });

        test('markAsUpdated creates a DateTimeImmutable instance', function () {
            $link = createTestLink();

            $link->markAsUpdated();

            expect($link->getUpdatedAt())->toBeInstanceOf(DateTimeImmutable::class);
        });
    });

    describe('multiple setters', function () {
        test('can update multiple properties in sequence', function () {
            $link = createTestLink();
            $newUrl = new Url('https://updated.com');
            $newTitle = 'Updated Title';
            $newDescription = 'Updated Description';
            $newIcon = 'updated-icon';

            $link->setUrl($newUrl);
            $link->setTitle($newTitle);
            $link->setDescription($newDescription);
            $link->setIcon($newIcon);

            expect($link->getUrl())->toBe($newUrl);
            expect($link->getTitle())->toBe($newTitle);
            expect($link->getDescription())->toBe($newDescription);
            expect($link->getIcon())->toBe($newIcon);
        });
    });

    describe('immutability constraints', function () {
        test('linkId cannot be modified', function () {
            $link = createTestLink();

            expect(fn() => $link->linkId = UuidV4::uuid4())
                ->toThrow(Error::class);
        });

        test('url property cannot be modified directly', function () {
            $link = createTestLink();

            expect(fn() => $link->url = new Url('https://direct-modification.com'))
                ->toThrow(Error::class);
        });
    });
});
