<?php

use jschreuder\BookmarkBureau\Entity\Dashboard;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;

describe('Dashboard Entity', function () {
    function createTestDashboard(
        ?UuidInterface $id = null,
        ?string $name = null,
        ?string $description = null,
        ?string $icon = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null
    ): Dashboard {
        return new Dashboard(
            dashboardId: $id ?? UuidV4::uuid4(),
            name: $name ?? 'Example Dashboard',
            description: $description ?? 'Example Description',
            icon: $icon ?? 'dashboard-icon',
            createdAt: $createdAt ?? new DateTimeImmutable('2024-01-01 12:00:00'),
            updatedAt: $updatedAt ?? new DateTimeImmutable('2024-01-01 12:00:00')
        );
    }

    describe('construction', function () {
        test('creates a dashboard with all properties', function () {
            $id = UuidV4::uuid4();
            $name = 'Test Dashboard';
            $description = 'Test Description';
            $icon = 'test-icon';
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');

            $dashboard = new Dashboard($id, $name, $description, $icon, $createdAt, $updatedAt);

            expect($dashboard)->toBeInstanceOf(Dashboard::class);
        });

        test('stores all properties correctly during construction', function () {
            $id = UuidV4::uuid4();
            $name = 'Test Dashboard';
            $description = 'Test Description';
            $icon = 'test-icon';
            $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');

            $dashboard = new Dashboard($id, $name, $description, $icon, $createdAt, $updatedAt);

            expect($dashboard->dashboardId)->toBe($id);
            expect($dashboard->name)->toBe($name);
            expect($dashboard->description)->toBe($description);
            expect($dashboard->icon)->toBe($icon);
            expect($dashboard->createdAt)->toBe($createdAt);
            expect($dashboard->updatedAt)->toBe($updatedAt);
        });
    });

    describe('ID getter', function () {
        test('getDashboardId returns the UUID', function () {
            $id = UuidV4::uuid4();
            $dashboard = createTestDashboard(id: $id);

            expect($dashboard->dashboardId)->toBe($id);
            expect($dashboard->dashboardId)->toBeInstanceOf(UuidInterface::class);
        });
    });

    describe('name getter and setter', function () {
        test('getName returns the name', function () {
            $name = 'My Dashboard';
            $dashboard = createTestDashboard(name: $name);

            expect($dashboard->name)->toBe($name);
        });

        test('setName updates the name', function () {
            $dashboard = createTestDashboard();
            $newName = 'Updated Dashboard';

            $dashboard->name = $newName;

            expect($dashboard->name)->toBe($newName);
        });

        test('setName calls markAsUpdated', function () {
            $dashboard = createTestDashboard();
            $originalUpdatedAt = $dashboard->updatedAt;

            $dashboard->name = 'New Dashboard';

            expect($dashboard->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });
    });

    describe('description getter and setter', function () {
        test('getDescription returns the description', function () {
            $description = 'A detailed description of the dashboard';
            $dashboard = createTestDashboard(description: $description);

            expect($dashboard->description)->toBe($description);
        });

        test('setDescription updates the description', function () {
            $dashboard = createTestDashboard();
            $newDescription = 'Updated description';

            $dashboard->description = $newDescription;

            expect($dashboard->description)->toBe($newDescription);
        });

        test('setDescription calls markAsUpdated', function () {
            $dashboard = createTestDashboard();
            $originalUpdatedAt = $dashboard->updatedAt;

            $dashboard->description = 'New description';

            expect($dashboard->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setDescription works with empty string', function () {
            $dashboard = createTestDashboard();

            $dashboard->description = '';

            expect($dashboard->description)->toBe('');
        });

        test('setDescription works with long text', function () {
            $dashboard = createTestDashboard();
            $longDescription = str_repeat('Lorem ipsum dolor sit amet. ', 100);

            $dashboard->description = $longDescription;

            expect($dashboard->description)->toBe($longDescription);
        });
    });

    describe('icon getter and setter', function () {
        test('getIcon returns the icon', function () {
            $icon = 'dashboard-icon';
            $dashboard = createTestDashboard(icon: $icon);

            expect($dashboard->icon)->toBe($icon);
        });

        test('setIcon updates the icon', function () {
            $dashboard = createTestDashboard();
            $newIcon = 'new-icon';

            $dashboard->icon = $newIcon;

            expect($dashboard->icon)->toBe($newIcon);
        });

        test('setIcon calls markAsUpdated', function () {
            $dashboard = createTestDashboard();
            $originalUpdatedAt = $dashboard->updatedAt;

            $dashboard->icon = 'new-icon';

            expect($dashboard->updatedAt->getTimestamp())
                ->toBeGreaterThan($originalUpdatedAt->getTimestamp());
        });

        test('setIcon works with empty string', function () {
            $dashboard = createTestDashboard();

            $dashboard->icon = '';

            expect($dashboard->icon)->toBe('');
        });

        test('setIcon works with URL-like strings', function () {
            $dashboard = createTestDashboard();
            $iconUrl = 'https://example.com/icon.png';

            $dashboard->icon = $iconUrl;

            expect($dashboard->icon)->toBe($iconUrl);
        });
    });

    describe('createdAt getter', function () {
        test('getCreatedAt returns the creation timestamp', function () {
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
        test('getUpdatedAt returns the update timestamp', function () {
            $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');
            $dashboard = createTestDashboard(updatedAt: $updatedAt);

            expect($dashboard->updatedAt)->toBe($updatedAt);
            expect($dashboard->updatedAt)->toBeInstanceOf(DateTimeInterface::class);
        });

        test('updatedAt is updated when properties change', function () {
            $dashboard = createTestDashboard();
            $originalUpdatedAt = $dashboard->updatedAt;

            $dashboard->name = 'New Name';

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
            $newName = 'Updated Dashboard';
            $newDescription = 'Updated Description';
            $newIcon = 'updated-icon';

            $dashboard->name = $newName;
            $dashboard->description = $newDescription;
            $dashboard->icon = $newIcon;

            expect($dashboard->name)->toBe($newName);
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
