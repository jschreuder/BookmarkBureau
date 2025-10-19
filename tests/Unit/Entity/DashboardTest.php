<?php

use jschreuder\BookmarkBureau\Entity\Dashboard;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;

describe('Dashboard Entity', function () {
    function createTestDashboard(
        ?UuidInterface $id = null,
        ?string $title = null,
        ?string $description = null,
        ?string $icon = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null
    ): Dashboard {
        return new Dashboard(
            dashboardId: $id ?? UuidV4::uuid4(),
            title: $title ?? 'Example Dashboard',
            description: $description ?? 'Example Description',
            icon: $icon,
            createdAt: $createdAt ?? new DateTimeImmutable('2024-01-01 12:00:00'),
            updatedAt: $updatedAt ?? new DateTimeImmutable('2024-01-01 12:00:00')
        );
    }

    describe('construction', function () {
        test('creates a dashboard with all properties', function () {
            $id = UuidV4::uuid4();
            $title = 'Test Dashboard';
            $description = 'Test Description';
            $icon = 'test-icon';
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');

            $dashboard = new Dashboard($id, $title, $description, $icon, $createdAt, $updatedAt);

            expect($dashboard)->toBeInstanceOf(Dashboard::class);
        });

        test('stores all properties correctly during construction', function () {
            $id = UuidV4::uuid4();
            $title = 'Test Dashboard';
            $description = 'Test Description';
            $icon = 'test-icon';
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');

            $dashboard = new Dashboard($id, $title, $description, $icon, $createdAt, $updatedAt);

            expect($dashboard->dashboardId)->toBe($id);
            expect($dashboard->title)->toBe($title);
            expect($dashboard->description)->toBe($description);
            expect($dashboard->icon)->toBe($icon);
            expect($dashboard->createdAt)->toBe($createdAt);
            expect($dashboard->updatedAt)->toBe($updatedAt);
        });
    });

    describe('ID getter', function () {
        test('getting dashboardId returns the UUID', function () {
            $id = UuidV4::uuid4();
            $dashboard = createTestDashboard(id: $id);

            expect($dashboard->dashboardId)->toBe($id);
            expect($dashboard->dashboardId)->toBeInstanceOf(UuidInterface::class);
        });
    });

    describe('name getter and setter', function () {
        test('getting title returns the title', function () {
            $title = 'My Dashboard';
            $dashboard = createTestDashboard(title: $title);

            expect($dashboard->title)->toBe($title);
        });

        test('setting title updates the title', function () {
            $dashboard = createTestDashboard();
            $newTitle = 'Updated Dashboard';

            $dashboard->title = $newTitle;

            expect($dashboard->title)->toBe($newTitle);
        });

        test('setting title calls markAsUpdated', function () {
            $dashboard = createTestDashboard();
            $originalUpdatedAt = $dashboard->updatedAt;

            $dashboard->title = 'New Dashboard';

            expect($dashboard->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('description getter and setter', function () {
        test('getting description returns the description', function () {
            $description = 'A detailed description of the dashboard';
            $dashboard = createTestDashboard(description: $description);

            expect($dashboard->description)->toBe($description);
        });

        test('setting description updates the description', function () {
            $dashboard = createTestDashboard();
            $newDescription = 'Updated description';

            $dashboard->description = $newDescription;

            expect($dashboard->description)->toBe($newDescription);
        });

        test('setting description calls markAsUpdated', function () {
            $dashboard = createTestDashboard();
            $originalUpdatedAt = $dashboard->updatedAt;

            $dashboard->description = 'New description';

            expect($dashboard->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setting description works with empty string', function () {
            $dashboard = createTestDashboard();

            $dashboard->description = '';

            expect($dashboard->description)->toBe('');
        });

        test('setting description works with long text', function () {
            $dashboard = createTestDashboard();
            $longDescription = str_repeat('Lorem ipsum dolor sit amet. ', 100);

            $dashboard->description = $longDescription;

            expect($dashboard->description)->toBe($longDescription);
        });
    });

    describe('icon getter and setter', function () {
        test('getting icon returns the icon', function () {
            $icon = 'dashboard-icon';
            $dashboard = createTestDashboard(icon: $icon);

            expect($dashboard->icon)->toBe($icon);
        });

        test('setting icon updates the icon', function () {
            $dashboard = createTestDashboard();
            $newIcon = 'new-icon';

            $dashboard->icon = $newIcon;

            expect($dashboard->icon)->toBe($newIcon);
        });

        test('setting icon calls markAsUpdated', function () {
            $dashboard = createTestDashboard();
            $originalUpdatedAt = $dashboard->updatedAt;

            $dashboard->icon = 'new-icon';

            expect($dashboard->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setting icon works with null', function () {
            $dashboard = createTestDashboard();

            $dashboard->icon = null;

            expect($dashboard->icon)->toBeNull();
        });

        test('setting icon works with URL-like strings', function () {
            $dashboard = createTestDashboard();
            $iconUrl = 'https://example.com/icon.png';

            $dashboard->icon = $iconUrl;

            expect($dashboard->icon)->toBe($iconUrl);
        });
    });

    describe('createdAt getter', function () {
        test('getting createdAt returns the creation timestamp', function () {
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $dashboard = createTestDashboard(createdAt: $createdAt);

            expect($dashboard->createdAt)->toBe($createdAt);
            expect($dashboard->createdAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('createdAt is readonly and cannot be modified', function () {
            $dashboard = createTestDashboard();

            expect(fn() => $dashboard->createdAt = new DateTimeImmutable())
                ->toThrow(Error::class);
        });
    });

    describe('updatedAt getter', function () {
        test('getting updatedAt returns the update timestamp', function () {
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');
            $dashboard = createTestDashboard(updatedAt: $updatedAt);

            expect($dashboard->updatedAt)->toBe($updatedAt);
            expect($dashboard->updatedAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('updatedAt is updated when properties change', function () {
            $dashboard = createTestDashboard();
            $originalUpdatedAt = $dashboard->updatedAt;

            $dashboard->title = 'New Name';

            expect($dashboard->updatedAt)
                ->not->toBe($originalUpdatedAt);
            expect($dashboard->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('markAsUpdated method', function () {
        test('markAsUpdated updates the updatedAt timestamp', function () {
            $dashboard = createTestDashboard();
            $originalUpdatedAt = $dashboard->updatedAt;

            $dashboard->markAsUpdated();

            expect($dashboard->updatedAt)
                ->not->toBe($originalUpdatedAt);
            expect($dashboard->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('markAsUpdated sets updatedAt to current time', function () {
            $dashboard = createTestDashboard();
            $beforeMark = new DateTimeImmutable();

            $dashboard->markAsUpdated();

            $afterMark = new DateTimeImmutable();

            expect($dashboard->updatedAt->getTimestamp())
                ->toBeGreaterThanOrEqual($beforeMark->getTimestamp())
                ->toBeLessThanOrEqual($afterMark->getTimestamp());
        });

        test('markAsUpdated creates a DateTimeImmutable instance', function () {
            $dashboard = createTestDashboard();

            $dashboard->markAsUpdated();

            expect($dashboard->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
        });
    });

    describe('multiple setters', function () {
        test('can update multiple properties in sequence', function () {
            $dashboard = createTestDashboard();
            $newTitle = 'Updated Dashboard';
            $newDescription = 'Updated Description';
            $newIcon = 'updated-icon';

            $dashboard->title = $newTitle;
            $dashboard->description = $newDescription;
            $dashboard->icon = $newIcon;

            expect($dashboard->title)->toBe($newTitle);
            expect($dashboard->description)->toBe($newDescription);
            expect($dashboard->icon)->toBe($newIcon);
        });
    });

    describe('immutability constraints', function () {
        test('dashboardId cannot be modified', function () {
            $dashboard = createTestDashboard();

            expect(fn() => $dashboard->dashboardId = UuidV4::uuid4())
                ->toThrow(Error::class);
        });
    });
});
