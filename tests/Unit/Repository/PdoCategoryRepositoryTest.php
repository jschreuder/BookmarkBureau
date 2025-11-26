<?php

use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Mapper\CategoryEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\DashboardEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\TagEntityMapper;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\PdoCategoryRepository;
use jschreuder\BookmarkBureau\Repository\PdoDashboardRepository;
use jschreuder\BookmarkBureau\Repository\PdoLinkRepository;
use Ramsey\Uuid\Uuid;

describe("PdoCategoryRepository", function () {
    function createCategoryDatabase(): PDO
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

            CREATE TABLE tags (
                tag_name TEXT PRIMARY KEY,
                color TEXT
            );

            CREATE TABLE link_tags (
                link_id BLOB NOT NULL,
                tag_name TEXT NOT NULL,
                PRIMARY KEY (link_id, tag_name),
                FOREIGN KEY (link_id) REFERENCES links(link_id) ON DELETE CASCADE,
                FOREIGN KEY (tag_name) REFERENCES tags(tag_name) ON DELETE CASCADE
            );
        ');

        return $pdo;
    }

    function insertTestDashboardForCategory(PDO $pdo, $dashboard): void
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

    function insertTestCategoryEntity(PDO $pdo, $category): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO categories (category_id, dashboard_id, title, color, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $category->categoryId->getBytes(),
            $category->dashboard->dashboardId->getBytes(),
            (string) $category->title,
            $category->color ? (string) $category->color : null,
            $category->sortOrder,
            $category->createdAt->format("Y-m-d H:i:s"),
            $category->updatedAt->format("Y-m-d H:i:s"),
        ]);
    }

    function insertTestLinkForCategory(PDO $pdo, $link): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO links (link_id, url, title, description, icon, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $link->linkId->getBytes(),
            (string) $link->url,
            (string) $link->title,
            $link->description,
            $link->icon ? (string) $link->icon : null,
            $link->createdAt->format("Y-m-d H:i:s"),
            $link->updatedAt->format("Y-m-d H:i:s"),
        ]);
    }

    function createCategoryRepositories(PDO $pdo)
    {
        $dashboardRepo = new PdoDashboardRepository(
            $pdo,
            new DashboardEntityMapper(),
        );
        $linkRepo = new PdoLinkRepository(
            $pdo,
            new LinkEntityMapper(),
            new TagEntityMapper(),
        );
        $categoryRepo = new PdoCategoryRepository(
            $pdo,
            $dashboardRepo,
            $linkRepo,
            new CategoryEntityMapper(),
            new LinkEntityMapper(),
        );
        return [$dashboardRepo, $linkRepo, $categoryRepo];
    }

    describe("findById", function () {
        test("finds and returns a category by ID", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );

            insertTestDashboardForCategory($pdo, $dashboard);
            insertTestCategoryEntity($pdo, $category);

            $found = $repo->findById($category->categoryId);

            expect($found->categoryId->toString())->toBe(
                $category->categoryId->toString(),
            );
            expect((string) $found->title)->toBe((string) $category->title);
            expect($found->sortOrder)->toBe($category->sortOrder);
        });

        test(
            "throws CategoryNotFoundException when category does not exist",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $nonExistentId = Uuid::uuid4();

                expect(fn() => $repo->findById($nonExistentId))->toThrow(
                    CategoryNotFoundException::class,
                );
            },
        );

        test("correctly maps nullable color field", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $categoryId = Uuid::uuid4();

            insertTestDashboardForCategory($pdo, $dashboard);
            $pdo->prepare(
                'INSERT INTO categories (category_id, dashboard_id, title, created_at, updated_at)
                           VALUES (?, ?, ?, ?, ?)',
            )->execute([
                $categoryId->getBytes(),
                $dashboard->dashboardId->getBytes(),
                "Test Category",
                "2024-01-01 12:00:00",
                "2024-01-01 12:00:00",
            ]);

            $found = $repo->findById($categoryId);

            expect($found->color)->toBeNull();
        });

        test("correctly maps color field when present", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $color = new HexColor("#FF0000");
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                color: $color,
            );

            insertTestDashboardForCategory($pdo, $dashboard);
            insertTestCategoryEntity($pdo, $category);

            $found = $repo->findById($category->categoryId);

            expect($found->color)->not->toBeNull();
            expect((string) $found->color)->toBe("#FF0000");
        });
    });

    describe("findByDashboardId", function () {
        test(
            "returns all categories for a dashboard ordered by sort_order",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();

                insertTestDashboardForCategory($pdo, $dashboard);

                $category1 = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                    title: new Title("First"),
                    sortOrder: 1,
                );
                $category2 = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                    title: new Title("Second"),
                    sortOrder: 0,
                );
                $category3 = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                    title: new Title("Third"),
                    sortOrder: 2,
                );

                insertTestCategoryEntity($pdo, $category1);
                insertTestCategoryEntity($pdo, $category2);
                insertTestCategoryEntity($pdo, $category3);

                $collection = $repo->findByDashboardId($dashboard->dashboardId);

                expect($collection)->toHaveCount(3);
                $categories = iterator_to_array($collection);
                expect($categories[0]->categoryId->toString())->toBe(
                    $category2->categoryId->toString(),
                );
                expect($categories[1]->categoryId->toString())->toBe(
                    $category1->categoryId->toString(),
                );
                expect($categories[2]->categoryId->toString())->toBe(
                    $category3->categoryId->toString(),
                );
            },
        );

        test(
            "returns empty collection when dashboard has no categories",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();

                insertTestDashboardForCategory($pdo, $dashboard);

                $collection = $repo->findByDashboardId($dashboard->dashboardId);

                expect($collection)->toHaveCount(0);
            },
        );

        test(
            "throws DashboardNotFoundException when dashboard does not exist",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $nonExistentId = Uuid::uuid4();

                expect(
                    fn() => $repo->findByDashboardId($nonExistentId),
                )->toThrow(DashboardNotFoundException::class);
            },
        );
    });

    describe("save", function () {
        test("inserts a new category", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );

            insertTestDashboardForCategory($pdo, $dashboard);

            $repo->insert($category);

            $found = $repo->findById($category->categoryId);
            expect((string) $found->title)->toBe((string) $category->title);
            expect($found->sortOrder)->toBe($category->sortOrder);
        });

        test("updates an existing category", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);

            $category->title = new Title("Updated Title");
            $category->sortOrder = 5;
            $repo->update($category);

            $found = $repo->findById($category->categoryId);
            expect((string) $found->title)->toBe("Updated Title");
            expect($found->sortOrder)->toBe(5);
        });

        test("preserves timestamps on insert", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $createdAt = new DateTimeImmutable("2024-01-01 10:00:00");
            $updatedAt = new DateTimeImmutable("2024-01-01 12:00:00");
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
            );

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);

            $found = $repo->findById($category->categoryId);
            expect($found->createdAt->format("Y-m-d H:i:s"))->toBe(
                "2024-01-01 10:00:00",
            );
            expect($found->updatedAt->format("Y-m-d H:i:s"))->toBe(
                "2024-01-01 12:00:00",
            );
        });

        test("throws DashboardNotFoundException on FK violation", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $nonExistentDashboard = TestEntityFactory::createDashboard(
                id: Uuid::uuid4(),
            );
            $category = TestEntityFactory::createCategory(
                dashboard: $nonExistentDashboard,
            );

            expect(fn() => $repo->insert($category))->toThrow(
                DashboardNotFoundException::class,
            );
        });

        test("saves categories with null color", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                color: null,
            );

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);

            $found = $repo->findById($category->categoryId);
            expect($found->color)->toBeNull();
        });

        test("saves categories with color", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $color = new HexColor("#0000FF");
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                color: $color,
            );

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);

            $found = $repo->findById($category->categoryId);
            expect($found->color)->not->toBeNull();
            expect((string) $found->color)->toBe("#0000FF");
        });

        test(
            "throws DashboardNotFoundException when dashboard foreign key constraint fails on insert",
            function () {
                $mockPdo = Mockery::mock(PDO::class);
                $checkStmt = Mockery::mock(\PDOStatement::class);
                $insertStmt = Mockery::mock(\PDOStatement::class);

                $dashboard = TestEntityFactory::createDashboard();
                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );

                // Check statement returns false (category doesn't exist)
                $checkStmt->shouldReceive("execute")->andReturn(true);
                $checkStmt->shouldReceive("fetch")->andReturn(false);

                // INSERT statement throws foreign key constraint error
                $fkException = new \PDOException(
                    "FOREIGN KEY constraint failed: dashboard_id",
                );
                $insertStmt->shouldReceive("execute")->andThrow($fkException);

                $mockPdo
                    ->shouldReceive("prepare")
                    ->once()
                    ->withArgs(function ($sql) {
                        return str_contains($sql, "INSERT INTO categories") &&
                            str_contains($sql, "VALUES");
                    })
                    ->andReturn($insertStmt);

                $mockDashboardRepo = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $mockLinkRepo = Mockery::mock(LinkRepositoryInterface::class);
                $repo = new PdoCategoryRepository(
                    $mockPdo,
                    $mockDashboardRepo,
                    $mockLinkRepo,
                    new CategoryEntityMapper(),
                    new LinkEntityMapper(),
                );

                expect(fn() => $repo->insert($category))->toThrow(
                    DashboardNotFoundException::class,
                );
            },
        );

        test(
            "re-throws PDOException when not a foreign key constraint error on insert",
            function () {
                $mockPdo = Mockery::mock(PDO::class);
                $checkStmt = Mockery::mock(\PDOStatement::class);
                $insertStmt = Mockery::mock(\PDOStatement::class);

                $dashboard = TestEntityFactory::createDashboard();
                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );

                // Check statement returns false (category doesn't exist)
                $checkStmt->shouldReceive("execute")->andReturn(true);
                $checkStmt->shouldReceive("fetch")->andReturn(false);

                // INSERT statement throws unexpected error
                $unexpectedException = new \PDOException("Disk I/O error");
                $insertStmt
                    ->shouldReceive("execute")
                    ->andThrow($unexpectedException);

                $mockPdo
                    ->shouldReceive("prepare")
                    ->once()
                    ->withArgs(function ($sql) {
                        return str_contains($sql, "INSERT INTO categories") &&
                            str_contains($sql, "VALUES");
                    })
                    ->andReturn($insertStmt);

                $mockDashboardRepo = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $mockLinkRepo = Mockery::mock(LinkRepositoryInterface::class);
                $repo = new PdoCategoryRepository(
                    $mockPdo,
                    $mockDashboardRepo,
                    $mockLinkRepo,
                    new CategoryEntityMapper(),
                    new LinkEntityMapper(),
                );

                expect(fn() => $repo->insert($category))->toThrow(
                    PDOException::class,
                );
            },
        );
    });

    describe("delete", function () {
        test("deletes a category by ID", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);
            $repo->delete($category);

            expect(fn() => $repo->findById($category->categoryId))->toThrow(
                CategoryNotFoundException::class,
            );
        });

        test("cascades delete to category_links", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link = TestEntityFactory::createLink();

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);
            insertTestLinkForCategory($pdo, $link);
            $pdo->prepare(
                "INSERT INTO category_links (category_id, link_id) VALUES (?, ?)",
            )->execute([
                $category->categoryId->getBytes(),
                $link->linkId->getBytes(),
            ]);

            // Verify the link was inserted
            $checkStmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM category_links WHERE category_id = ?",
            );
            $checkStmt->execute([$category->categoryId->getBytes()]);
            $beforeDelete = $checkStmt->fetch(PDO::FETCH_ASSOC);
            expect($beforeDelete["count"])->toBe(1);

            $repo->delete($category);

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM category_links WHERE category_id = ?",
            );
            $stmt->execute([$category->categoryId->getBytes()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            expect($result["count"])->toBe(0);
        });
    });

    describe("addLink", function () {
        test(
            "adds a link to a category with specified sort order",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );
                $link = TestEntityFactory::createLink();

                insertTestDashboardForCategory($pdo, $dashboard);
                $repo->insert($category);
                insertTestLinkForCategory($pdo, $link);

                $categoryLink = $repo->addLink(
                    $category->categoryId,
                    $link->linkId,
                    5,
                );

                expect($categoryLink->category->categoryId->toString())->toBe(
                    $category->categoryId->toString(),
                );
                expect($categoryLink->link->linkId->toString())->toBe(
                    $link->linkId->toString(),
                );
                expect($categoryLink->sortOrder)->toBe(5);
            },
        );

        test(
            "throws CategoryNotFoundException when category does not exist",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $link = TestEntityFactory::createLink();
                $nonExistentCategoryId = Uuid::uuid4();

                insertTestLinkForCategory($pdo, $link);

                expect(
                    fn() => $repo->addLink(
                        $nonExistentCategoryId,
                        $link->linkId,
                        0,
                    ),
                )->toThrow(CategoryNotFoundException::class);
            },
        );

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );
                $nonExistentLinkId = Uuid::uuid4();

                insertTestDashboardForCategory($pdo, $dashboard);
                $repo->insert($category);

                expect(
                    fn() => $repo->addLink(
                        $category->categoryId,
                        $nonExistentLinkId,
                        0,
                    ),
                )->toThrow(LinkNotFoundException::class);
            },
        );

        test(
            "re-throws PDOException when not a foreign key constraint error",
            function () {
                $mockPdo = Mockery::mock(PDO::class);
                $mockDashboardRepo = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $mockLinkRepo = Mockery::mock(LinkRepositoryInterface::class);
                $insertStmt = Mockery::mock(\PDOStatement::class);

                $category = TestEntityFactory::createCategory();
                $link = TestEntityFactory::createLink();
                $categoryId = $category->categoryId;
                $linkId = $link->linkId;

                // Mock repository lookups to succeed
                $mockDashboardRepo
                    ->shouldReceive("findById")
                    ->andReturn($category->dashboard);
                $mockLinkRepo->shouldReceive("findById")->andReturn($link);

                // INSERT statement throws unexpected error
                $unexpectedException = new \PDOException("Disk I/O error");
                $insertStmt
                    ->shouldReceive("execute")
                    ->andThrow($unexpectedException);

                $mockPdo->shouldReceive("prepare")->andReturn($insertStmt);

                $repo = new PdoCategoryRepository(
                    $mockPdo,
                    $mockDashboardRepo,
                    $mockLinkRepo,
                    new CategoryEntityMapper(),
                    new LinkEntityMapper(),
                );

                expect(
                    fn() => $repo->addLink($categoryId, $linkId, 0),
                )->toThrow(\PDOException::class);
            },
        );
    });

    describe("removeLink", function () {
        test("removes a link from a category", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link = TestEntityFactory::createLink();

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);
            insertTestLinkForCategory($pdo, $link);
            $repo->addLink($category->categoryId, $link->linkId, 0);

            $repo->removeLink($category->categoryId, $link->linkId);

            expect(
                $repo->hasLink($category->categoryId, $link->linkId),
            )->toBeFalse();
        });

        test(
            "throws CategoryNotFoundException when category does not exist",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $link = TestEntityFactory::createLink();
                $nonExistentCategoryId = Uuid::uuid4();

                insertTestLinkForCategory($pdo, $link);

                expect(
                    fn() => $repo->removeLink(
                        $nonExistentCategoryId,
                        $link->linkId,
                    ),
                )->toThrow(CategoryNotFoundException::class);
            },
        );

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );
                $nonExistentLinkId = Uuid::uuid4();

                insertTestDashboardForCategory($pdo, $dashboard);
                $repo->insert($category);

                expect(
                    fn() => $repo->removeLink(
                        $category->categoryId,
                        $nonExistentLinkId,
                    ),
                )->toThrow(LinkNotFoundException::class);
            },
        );

        test(
            "throws LinkNotFoundException when link is not in category",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );
                $link = TestEntityFactory::createLink();

                insertTestDashboardForCategory($pdo, $dashboard);
                $repo->insert($category);
                insertTestLinkForCategory($pdo, $link);

                expect(
                    fn() => $repo->removeLink(
                        $category->categoryId,
                        $link->linkId,
                    ),
                )->toThrow(LinkNotFoundException::class);
            },
        );
    });

    describe("hasLink", function () {
        test("returns true when link is in category", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link = TestEntityFactory::createLink();

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);
            insertTestLinkForCategory($pdo, $link);
            $repo->addLink($category->categoryId, $link->linkId, 0);

            expect(
                $repo->hasLink($category->categoryId, $link->linkId),
            )->toBeTrue();
        });

        test("returns false when link is not in category", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link = TestEntityFactory::createLink();

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);
            insertTestLinkForCategory($pdo, $link);

            expect(
                $repo->hasLink($category->categoryId, $link->linkId),
            )->toBeFalse();
        });
    });

    describe("updateLinkSortOrder", function () {
        test("updates sort order for a link in a category", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link = TestEntityFactory::createLink();

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);
            insertTestLinkForCategory($pdo, $link);
            $repo->addLink($category->categoryId, $link->linkId, 0);

            $repo->updateLinkSortOrder(
                $category->categoryId,
                $link->linkId,
                10,
            );

            $stmt = $pdo->prepare(
                "SELECT sort_order FROM category_links WHERE category_id = ? AND link_id = ?",
            );
            $stmt->execute([
                $category->categoryId->getBytes(),
                $link->linkId->getBytes(),
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            expect($result["sort_order"])->toBe(10);
        });

        test(
            "throws CategoryNotFoundException when category does not exist",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $link = TestEntityFactory::createLink();
                $nonExistentCategoryId = Uuid::uuid4();

                insertTestLinkForCategory($pdo, $link);

                expect(
                    fn() => $repo->updateLinkSortOrder(
                        $nonExistentCategoryId,
                        $link->linkId,
                        0,
                    ),
                )->toThrow(CategoryNotFoundException::class);
            },
        );

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );
                $nonExistentLinkId = Uuid::uuid4();

                insertTestDashboardForCategory($pdo, $dashboard);
                $repo->insert($category);

                expect(
                    fn() => $repo->updateLinkSortOrder(
                        $category->categoryId,
                        $nonExistentLinkId,
                        0,
                    ),
                )->toThrow(LinkNotFoundException::class);
            },
        );
    });

    describe("reorderLinks", function () {
        test("reorders links in a category using LinkCollection", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $link3 = TestEntityFactory::createLink();

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);
            insertTestLinkForCategory($pdo, $link1);
            insertTestLinkForCategory($pdo, $link2);
            insertTestLinkForCategory($pdo, $link3);

            $repo->addLink($category->categoryId, $link1->linkId, 0);
            $repo->addLink($category->categoryId, $link2->linkId, 1);
            $repo->addLink($category->categoryId, $link3->linkId, 2);

            // Reorder: link2 (0), link1 (1), link3 (2)
            $repo->reorderLinks(
                $category->categoryId,
                new LinkCollection($link2, $link1, $link3),
            );

            $stmt = $pdo->prepare(
                "SELECT sort_order FROM category_links WHERE category_id = ? AND link_id = ? ORDER BY sort_order",
            );

            $stmt->execute([
                $category->categoryId->getBytes(),
                $link2->linkId->getBytes(),
            ]);
            expect($stmt->fetch(PDO::FETCH_ASSOC)["sort_order"])->toBe(0);

            $stmt->execute([
                $category->categoryId->getBytes(),
                $link1->linkId->getBytes(),
            ]);
            expect($stmt->fetch(PDO::FETCH_ASSOC)["sort_order"])->toBe(1);

            $stmt->execute([
                $category->categoryId->getBytes(),
                $link3->linkId->getBytes(),
            ]);
            expect($stmt->fetch(PDO::FETCH_ASSOC)["sort_order"])->toBe(2);
        });

        test(
            "throws CategoryNotFoundException when category does not exist",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $link = TestEntityFactory::createLink();
                $nonExistentCategoryId = Uuid::uuid4();

                insertTestLinkForCategory($pdo, $link);

                expect(
                    fn() => $repo->reorderLinks(
                        $nonExistentCategoryId,
                        new LinkCollection($link),
                    ),
                )->toThrow(CategoryNotFoundException::class);
            },
        );
    });

    describe("getMaxSortOrderForDashboardId", function () {
        test(
            "returns highest sort_order for categories in a dashboard",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();

                insertTestDashboardForCategory($pdo, $dashboard);

                $category1 = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                    sortOrder: 2,
                );
                $category2 = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                    sortOrder: 5,
                );
                $category3 = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                    sortOrder: 1,
                );

                insertTestCategoryEntity($pdo, $category1);
                insertTestCategoryEntity($pdo, $category2);
                insertTestCategoryEntity($pdo, $category3);

                expect(
                    $repo->getMaxSortOrderForDashboardId(
                        $dashboard->dashboardId,
                    ),
                )->toBe(5);
            },
        );

        test("returns -1 when dashboard has no categories", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();

            insertTestDashboardForCategory($pdo, $dashboard);

            expect(
                $repo->getMaxSortOrderForDashboardId($dashboard->dashboardId),
            )->toBe(-1);
        });

        test("throws RepositoryStorageException when fetch fails", function () {
            $mockPdo = Mockery::mock(PDO::class);
            $mockStmt = Mockery::mock(PDOStatement::class);

            $dashboardId = Uuid::uuid4();

            $mockStmt->shouldReceive("execute")->andReturn(true);
            $mockStmt->shouldReceive("fetch")->andReturn(false);

            $mockPdo->shouldReceive("prepare")->andReturn($mockStmt);

            $mockDashboardRepo = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $mockLinkRepo = Mockery::mock(LinkRepositoryInterface::class);

            $repo = new PdoCategoryRepository(
                $mockPdo,
                $mockDashboardRepo,
                $mockLinkRepo,
                new CategoryEntityMapper(),
                new LinkEntityMapper(),
            );

            expect(
                fn() => $repo->getMaxSortOrderForDashboardId($dashboardId),
            )->toThrow(RepositoryStorageException::class);
        });
    });

    describe("getMaxSortOrderForCategoryId", function () {
        test("returns highest sort_order for links in a category", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $link3 = TestEntityFactory::createLink();

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);
            insertTestLinkForCategory($pdo, $link1);
            insertTestLinkForCategory($pdo, $link2);
            insertTestLinkForCategory($pdo, $link3);

            $pdo->prepare(
                "INSERT INTO category_links (category_id, link_id, sort_order) VALUES (?, ?, ?)",
            )->execute([
                $category->categoryId->getBytes(),
                $link1->linkId->getBytes(),
                3,
            ]);
            $pdo->prepare(
                "INSERT INTO category_links (category_id, link_id, sort_order) VALUES (?, ?, ?)",
            )->execute([
                $category->categoryId->getBytes(),
                $link2->linkId->getBytes(),
                7,
            ]);
            $pdo->prepare(
                "INSERT INTO category_links (category_id, link_id, sort_order) VALUES (?, ?, ?)",
            )->execute([
                $category->categoryId->getBytes(),
                $link3->linkId->getBytes(),
                1,
            ]);

            expect(
                $repo->getMaxSortOrderForCategoryId($category->categoryId),
            )->toBe(7);
        });

        test("returns -1 when category has no links", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);

            expect(
                $repo->getMaxSortOrderForCategoryId($category->categoryId),
            )->toBe(-1);
        });

        test("throws RepositoryStorageException when fetch fails", function () {
            $mockPdo = Mockery::mock(PDO::class);
            $mockStmt = Mockery::mock(PDOStatement::class);

            $categoryId = Uuid::uuid4();

            $mockStmt->shouldReceive("execute")->andReturn(true);
            $mockStmt->shouldReceive("fetch")->andReturn(false);

            $mockPdo->shouldReceive("prepare")->andReturn($mockStmt);

            $mockDashboardRepo = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $mockLinkRepo = Mockery::mock(LinkRepositoryInterface::class);

            $repo = new PdoCategoryRepository(
                $mockPdo,
                $mockDashboardRepo,
                $mockLinkRepo,
                new CategoryEntityMapper(),
                new LinkEntityMapper(),
            );

            expect(
                fn() => $repo->getMaxSortOrderForCategoryId($categoryId),
            )->toThrow(RepositoryStorageException::class);
        });
    });

    describe("count", function () {
        test("returns the total number of categories", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();

            insertTestDashboardForCategory($pdo, $dashboard);

            $repo->insert(
                TestEntityFactory::createCategory(dashboard: $dashboard),
            );
            $repo->insert(
                TestEntityFactory::createCategory(dashboard: $dashboard),
            );
            $repo->insert(
                TestEntityFactory::createCategory(dashboard: $dashboard),
            );

            expect($repo->count())->toBe(3);
        });

        test("returns 0 when no categories exist", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );

            expect($repo->count())->toBe(0);
        });

        test("throws RepositoryStorageException when fetch fails", function () {
            $mockPdo = Mockery::mock(PDO::class);
            $mockStmt = Mockery::mock(PDOStatement::class);

            $mockStmt->shouldReceive("execute")->andReturn(true);
            $mockStmt->shouldReceive("fetch")->andReturn(false);

            $mockPdo->shouldReceive("prepare")->andReturn($mockStmt);

            $mockDashboardRepo = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $mockLinkRepo = Mockery::mock(LinkRepositoryInterface::class);

            $repo = new PdoCategoryRepository(
                $mockPdo,
                $mockDashboardRepo,
                $mockLinkRepo,
                new CategoryEntityMapper(),
                new LinkEntityMapper(),
            );

            expect(fn() => $repo->count())->toThrow(
                RepositoryStorageException::class,
            );
        });
    });

    describe("countLinksInCategory", function () {
        test("returns the count of links in a category", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);
            insertTestLinkForCategory($pdo, $link1);
            insertTestLinkForCategory($pdo, $link2);

            $repo->addLink($category->categoryId, $link1->linkId, 0);
            $repo->addLink($category->categoryId, $link2->linkId, 1);

            expect($repo->countLinksInCategory($category->categoryId))->toBe(2);
        });

        test("returns 0 when category has no links", function () {
            $pdo = createCategoryDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );

            insertTestDashboardForCategory($pdo, $dashboard);
            $repo->insert($category);

            expect($repo->countLinksInCategory($category->categoryId))->toBe(0);
        });

        test("throws RepositoryStorageException when fetch fails", function () {
            $mockPdo = Mockery::mock(PDO::class);
            $mockStmt = Mockery::mock(\PDOStatement::class);

            $categoryId = Uuid::uuid4();

            $mockStmt->shouldReceive("execute")->andReturn(true);
            $mockStmt->shouldReceive("fetch")->andReturn(false);

            $mockPdo->shouldReceive("prepare")->andReturn($mockStmt);

            $mockDashboardRepo = Mockery::mock(
                DashboardRepositoryInterface::class,
            );
            $mockLinkRepo = Mockery::mock(LinkRepositoryInterface::class);

            $repo = new PdoCategoryRepository(
                $mockPdo,
                $mockDashboardRepo,
                $mockLinkRepo,
                new CategoryEntityMapper(),
                new LinkEntityMapper(),
            );

            expect(fn() => $repo->countLinksInCategory($categoryId))->toThrow(
                RepositoryStorageException::class,
            );
        });
    });

    describe("findCategoryLinksForCategoryId", function () {
        test(
            "returns CategoryLink entities for a category ordered by sort_order",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );
                $link1 = TestEntityFactory::createLink();
                $link2 = TestEntityFactory::createLink();
                $link3 = TestEntityFactory::createLink();

                insertTestDashboardForCategory($pdo, $dashboard);
                $repo->insert($category);
                insertTestLinkForCategory($pdo, $link1);
                insertTestLinkForCategory($pdo, $link2);
                insertTestLinkForCategory($pdo, $link3);

                $repo->addLink($category->categoryId, $link1->linkId, 2);
                $repo->addLink($category->categoryId, $link2->linkId, 0);
                $repo->addLink($category->categoryId, $link3->linkId, 1);

                $collection = $repo->findCategoryLinksForCategoryId(
                    $category->categoryId,
                );

                expect($collection)->toHaveCount(3);
                $categoryLinks = iterator_to_array($collection);
                expect($categoryLinks[0]->link->linkId->toString())->toBe(
                    $link2->linkId->toString(),
                );
                expect($categoryLinks[0]->sortOrder)->toBe(0);
                expect($categoryLinks[1]->link->linkId->toString())->toBe(
                    $link3->linkId->toString(),
                );
                expect($categoryLinks[1]->sortOrder)->toBe(1);
                expect($categoryLinks[2]->link->linkId->toString())->toBe(
                    $link1->linkId->toString(),
                );
                expect($categoryLinks[2]->sortOrder)->toBe(2);
            },
        );

        test(
            "throws CategoryNotFoundException when category does not exist",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $nonExistentId = Uuid::uuid4();

                expect(
                    fn() => $repo->findCategoryLinksForCategoryId(
                        $nonExistentId,
                    ),
                )->toThrow(CategoryNotFoundException::class);
            },
        );

        test(
            "returns empty collection for category with no links",
            function () {
                $pdo = createCategoryDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createCategoryRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );

                insertTestDashboardForCategory($pdo, $dashboard);
                $repo->insert($category);

                $collection = $repo->findCategoryLinksForCategoryId(
                    $category->categoryId,
                );

                expect($collection)->toHaveCount(0);
            },
        );
    });
});
