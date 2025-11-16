<?php

use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Repository\PdoDashboardRepository;
use jschreuder\BookmarkBureau\Entity\Mapper\DashboardEntityMapper;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use Ramsey\Uuid\Uuid;

describe("PdoDashboardRepository", function () {
    function createDashboardDatabase(): PDO
    {
        $pdo = new PDO("sqlite::memory:");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("PRAGMA foreign_keys = ON");

        // Create schema
        $pdo->exec('
            CREATE TABLE dashboards (
                dashboard_id BLOB PRIMARY KEY,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                icon TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE categories (
                category_id BLOB PRIMARY KEY,
                dashboard_id BLOB NOT NULL,
                title TEXT NOT NULL,
                color TEXT,
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (dashboard_id) REFERENCES dashboards(dashboard_id) ON DELETE CASCADE
            );

            CREATE TABLE links (
                link_id BLOB PRIMARY KEY,
                url TEXT NOT NULL,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                icon TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE category_links (
                category_id BLOB NOT NULL,
                link_id BLOB NOT NULL,
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (category_id, link_id),
                FOREIGN KEY (link_id) REFERENCES links(link_id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
            );

            CREATE TABLE favorites (
                dashboard_id BLOB NOT NULL,
                link_id BLOB NOT NULL,
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (dashboard_id, link_id),
                FOREIGN KEY (dashboard_id) REFERENCES dashboards(dashboard_id) ON DELETE CASCADE,
                FOREIGN KEY (link_id) REFERENCES links(link_id) ON DELETE CASCADE
            );
        ');

        return $pdo;
    }

    function insertTestDashboard(PDO $pdo, $dashboard): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO dashboards (dashboard_id, title, description, icon, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $dashboard->dashboardId->getBytes(),
            (string) $dashboard->title,
            $dashboard->description,
            $dashboard->icon ? (string) $dashboard->icon : null,
            $dashboard->createdAt->format("Y-m-d H:i:s"),
            $dashboard->updatedAt->format("Y-m-d H:i:s"),
        ]);
    }

    describe("findById", function () {
        test("finds and returns a dashboard by ID", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $dashboard = TestEntityFactory::createDashboard();

            insertTestDashboard($pdo, $dashboard);

            $found = $repo->findById($dashboard->dashboardId);

            expect($found->dashboardId->toString())->toBe(
                $dashboard->dashboardId->toString(),
            );
            expect((string) $found->title)->toBe((string) $dashboard->title);
            expect($found->description)->toBe($dashboard->description);
        });

        test(
            "throws DashboardNotFoundException when dashboard does not exist",
            function () {
                $pdo = createDashboardDatabase();
                $repo = new PdoDashboardRepository(
                    $pdo,
                    new DashboardEntityMapper(),
                );
                $nonExistentId = Uuid::uuid4();

                expect(fn() => $repo->findById($nonExistentId))->toThrow(
                    DashboardNotFoundException::class,
                );
            },
        );

        test("correctly maps nullable icon field", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $dashboardId = Uuid::uuid4();

            // Insert dashboard directly without icon
            $pdo->prepare(
                'INSERT INTO dashboards (dashboard_id, title, description, created_at, updated_at)
                           VALUES (?, ?, ?, ?, ?)',
            )->execute([
                $dashboardId->getBytes(),
                "Test Dashboard",
                "Test Description",
                "2024-01-01 12:00:00",
                "2024-01-01 12:00:00",
            ]);

            $found = $repo->findById($dashboardId);

            expect($found->icon)->toBeNull();
        });

        test("correctly maps icon field when present", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $icon = new Icon("dashboard-icon");
            $dashboardWithIcon = TestEntityFactory::createDashboard(
                icon: $icon,
            );

            insertTestDashboard($pdo, $dashboardWithIcon);

            $found = $repo->findById($dashboardWithIcon->dashboardId);

            expect($found->icon)->not->toBeNull();
            expect((string) $found->icon)->toBe("dashboard-icon");
        });
    });

    describe("findAll", function () {
        test("returns all dashboards ordered by title ascending", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );

            $dashboard1 = TestEntityFactory::createDashboard(
                title: new Title("Work"),
            );
            $dashboard2 = TestEntityFactory::createDashboard(
                title: new Title("Personal"),
            );
            $dashboard3 = TestEntityFactory::createDashboard(
                title: new Title("Archive"),
            );

            insertTestDashboard($pdo, $dashboard1);
            insertTestDashboard($pdo, $dashboard2);
            insertTestDashboard($pdo, $dashboard3);

            $collection = $repo->findAll();

            expect($collection)->toHaveCount(3);
            $dashboards = iterator_to_array($collection);
            expect($dashboards[0]->dashboardId->toString())->toBe(
                $dashboard3->dashboardId->toString(),
            );
            expect($dashboards[1]->dashboardId->toString())->toBe(
                $dashboard2->dashboardId->toString(),
            );
            expect($dashboards[2]->dashboardId->toString())->toBe(
                $dashboard1->dashboardId->toString(),
            );
        });

        test("returns empty collection when no dashboards exist", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );

            $collection = $repo->findAll();

            expect($collection)->toHaveCount(0);
        });

        test(
            "returns dashboards with all fields populated correctly",
            function () {
                $pdo = createDashboardDatabase();
                $repo = new PdoDashboardRepository(
                    $pdo,
                    new DashboardEntityMapper(),
                );

                $createdAt = new DateTimeImmutable("2024-01-15 10:00:00");
                $updatedAt = new DateTimeImmutable("2024-01-15 12:00:00");
                $dashboard = TestEntityFactory::createDashboard(
                    title: new Title("Test Dashboard"),
                    description: "Test description",
                    createdAt: $createdAt,
                    updatedAt: $updatedAt,
                );

                insertTestDashboard($pdo, $dashboard);

                $collection = $repo->findAll();
                $dashboards = iterator_to_array($collection);

                expect($dashboards[0]->description)->toBe("Test description");
                expect($dashboards[0]->createdAt->format("Y-m-d H:i:s"))->toBe(
                    "2024-01-15 10:00:00",
                );
                expect($dashboards[0]->updatedAt->format("Y-m-d H:i:s"))->toBe(
                    "2024-01-15 12:00:00",
                );
            },
        );
    });

    describe("save", function () {
        test("inserts a new dashboard", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $dashboard = TestEntityFactory::createDashboard();

            $repo->save($dashboard);

            $found = $repo->findById($dashboard->dashboardId);
            expect((string) $found->title)->toBe((string) $dashboard->title);
            expect($found->description)->toBe($dashboard->description);
        });

        test("updates an existing dashboard", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $dashboard = TestEntityFactory::createDashboard();

            $repo->save($dashboard);

            $dashboard->title = new Title("Updated Title");
            $dashboard->description = "Updated Description";
            $repo->save($dashboard);

            $found = $repo->findById($dashboard->dashboardId);
            expect((string) $found->title)->toBe("Updated Title");
            expect($found->description)->toBe("Updated Description");
        });

        test("preserves timestamps on insert", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $createdAt = new DateTimeImmutable("2024-01-01 10:00:00");
            $updatedAt = new DateTimeImmutable("2024-01-01 12:00:00");
            $dashboard = TestEntityFactory::createDashboard(
                createdAt: $createdAt,
                updatedAt: $updatedAt,
            );

            $repo->save($dashboard);

            $found = $repo->findById($dashboard->dashboardId);
            expect($found->createdAt->format("Y-m-d H:i:s"))->toBe(
                "2024-01-01 10:00:00",
            );
            expect($found->updatedAt->format("Y-m-d H:i:s"))->toBe(
                "2024-01-01 12:00:00",
            );
        });

        test("saves dashboards with null icon", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $dashboardId = Uuid::uuid4();

            // Create dashboard and manually set icon to null before saving
            $dashboard = new Dashboard(
                dashboardId: $dashboardId,
                title: new Title("Test Dashboard"),
                description: "Test Description",
                icon: null,
                createdAt: new DateTimeImmutable("2024-01-01 12:00:00"),
                updatedAt: new DateTimeImmutable("2024-01-01 12:00:00"),
            );

            $repo->save($dashboard);

            $found = $repo->findById($dashboard->dashboardId);
            expect($found->icon)->toBeNull();
        });

        test("saves dashboards with icon", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $icon = new Icon("custom-icon");
            $dashboard = TestEntityFactory::createDashboard(icon: $icon);

            $repo->save($dashboard);

            $found = $repo->findById($dashboard->dashboardId);
            expect($found->icon)->not->toBeNull();
            expect((string) $found->icon)->toBe("custom-icon");
        });

        test("updates icon field when saving existing dashboard", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $dashboard = TestEntityFactory::createDashboard(icon: null);

            $repo->save($dashboard);

            $newIcon = new Icon("updated-icon");
            $dashboard->icon = $newIcon;
            $repo->save($dashboard);

            $found = $repo->findById($dashboard->dashboardId);
            expect((string) $found->icon)->toBe("updated-icon");
        });

        test("can nullify icon when updating dashboard", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $icon = new Icon("initial-icon");
            $dashboard = TestEntityFactory::createDashboard(icon: $icon);

            $repo->save($dashboard);

            $dashboard->icon = null;
            $repo->save($dashboard);

            $found = $repo->findById($dashboard->dashboardId);
            expect($found->icon)->toBeNull();
        });
    });

    describe("delete", function () {
        test("deletes a dashboard by ID", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $dashboard = TestEntityFactory::createDashboard();

            $repo->save($dashboard);
            $repo->delete($dashboard);

            expect(fn() => $repo->findById($dashboard->dashboardId))->toThrow(
                DashboardNotFoundException::class,
            );
        });

        test("cascades delete to categories", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $dashboard = TestEntityFactory::createDashboard();
            $categoryId = Uuid::uuid4();

            $repo->save($dashboard);

            $pdo->prepare(
                "INSERT INTO categories (category_id, dashboard_id, title) VALUES (?, ?, ?)",
            )->execute([
                $categoryId->getBytes(),
                $dashboard->dashboardId->getBytes(),
                "Test Category",
            ]);

            // Verify the category was inserted
            $checkStmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM categories WHERE dashboard_id = ?",
            );
            $checkStmt->execute([$dashboard->dashboardId->getBytes()]);
            $beforeDelete = $checkStmt->fetch(PDO::FETCH_ASSOC);
            expect($beforeDelete["count"])->toBe(1);

            $repo->delete($dashboard);

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM categories WHERE dashboard_id = ?",
            );
            $stmt->execute([$dashboard->dashboardId->getBytes()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            expect($result["count"])->toBe(0);
        });

        test("cascades delete to favorites", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $dashboard = TestEntityFactory::createDashboard();
            $linkId = Uuid::uuid4();

            $repo->save($dashboard);

            // Insert a link first
            $pdo->prepare(
                "INSERT INTO links (link_id, url, title, description) VALUES (?, ?, ?, ?)",
            )->execute([
                $linkId->getBytes(),
                "https://example.com",
                "Example",
                "Test link",
            ]);

            // Insert a favorite
            $pdo->prepare(
                "INSERT INTO favorites (dashboard_id, link_id) VALUES (?, ?)",
            )->execute([
                $dashboard->dashboardId->getBytes(),
                $linkId->getBytes(),
            ]);

            // Verify the favorite was inserted
            $checkStmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM favorites WHERE dashboard_id = ?",
            );
            $checkStmt->execute([$dashboard->dashboardId->getBytes()]);
            $beforeDelete = $checkStmt->fetch(PDO::FETCH_ASSOC);
            expect($beforeDelete["count"])->toBe(1);

            $repo->delete($dashboard);

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM favorites WHERE dashboard_id = ?",
            );
            $stmt->execute([$dashboard->dashboardId->getBytes()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            expect($result["count"])->toBe(0);
        });

        test(
            "cascade delete correctly removes nested cascade data",
            function () {
                $pdo = createDashboardDatabase();
                $repo = new PdoDashboardRepository(
                    $pdo,
                    new DashboardEntityMapper(),
                );
                $dashboard = TestEntityFactory::createDashboard();
                $categoryId = Uuid::uuid4();
                $linkId = Uuid::uuid4();

                $repo->save($dashboard);

                // Create category
                $pdo->prepare(
                    "INSERT INTO categories (category_id, dashboard_id, title) VALUES (?, ?, ?)",
                )->execute([
                    $categoryId->getBytes(),
                    $dashboard->dashboardId->getBytes(),
                    "Test Category",
                ]);

                // Create link
                $pdo->prepare(
                    "INSERT INTO links (link_id, url, title, description) VALUES (?, ?, ?, ?)",
                )->execute([
                    $linkId->getBytes(),
                    "https://example.com",
                    "Example",
                    "Test link",
                ]);

                // Add link to category
                $pdo->prepare(
                    "INSERT INTO category_links (category_id, link_id) VALUES (?, ?)",
                )->execute([$categoryId->getBytes(), $linkId->getBytes()]);

                // Verify the category_link was inserted
                $checkStmt = $pdo->prepare(
                    "SELECT COUNT(*) as count FROM category_links WHERE category_id = ?",
                );
                $checkStmt->execute([$categoryId->getBytes()]);
                $beforeDelete = $checkStmt->fetch(PDO::FETCH_ASSOC);
                expect($beforeDelete["count"])->toBe(1);

                $repo->delete($dashboard);

                // Verify category_links were also deleted
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) as count FROM category_links WHERE category_id = ?",
                );
                $stmt->execute([$categoryId->getBytes()]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                expect($result["count"])->toBe(0);
            },
        );
    });

    describe("count", function () {
        test("returns the total number of dashboards", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );

            insertTestDashboard($pdo, TestEntityFactory::createDashboard());
            insertTestDashboard($pdo, TestEntityFactory::createDashboard());
            insertTestDashboard($pdo, TestEntityFactory::createDashboard());

            expect($repo->count())->toBe(3);
        });

        test("returns 0 when no dashboards exist", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );

            expect($repo->count())->toBe(0);
        });

        test("correctly counts after inserts and deletes", function () {
            $pdo = createDashboardDatabase();
            $repo = new PdoDashboardRepository(
                $pdo,
                new DashboardEntityMapper(),
            );
            $dashboard1 = TestEntityFactory::createDashboard();
            $dashboard2 = TestEntityFactory::createDashboard();

            $repo->save($dashboard1);
            expect($repo->count())->toBe(1);

            $repo->save($dashboard2);
            expect($repo->count())->toBe(2);

            $repo->delete($dashboard1);
            expect($repo->count())->toBe(1);
        });
    });
});
