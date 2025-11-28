<?php

use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Entity\Mapper\DashboardEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\FavoriteEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\TagEntityMapper;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\PdoDashboardRepository;
use jschreuder\BookmarkBureau\Repository\PdoFavoriteRepository;
use jschreuder\BookmarkBureau\Repository\PdoLinkRepository;
use Ramsey\Uuid\Uuid;

describe("PdoFavoriteRepository", function () {
    function createFavoriteDatabase(): PDO
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

    function insertTestDashboardForFavorite(PDO $pdo, $dashboard): void
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

    function insertTestLinkForFavorite(PDO $pdo, $link): void
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

    function insertFavorite(
        PDO $pdo,
        $dashboardId,
        $linkId,
        int $sortOrder = 0,
    ): void {
        $stmt = $pdo->prepare(
            'INSERT INTO favorites (dashboard_id, link_id, sort_order, created_at)
             VALUES (?, ?, ?, ?)',
        );
        $stmt->execute([
            $dashboardId->getBytes(),
            $linkId->getBytes(),
            $sortOrder,
            "2024-01-01 12:00:00",
        ]);
    }

    function createFavoriteRepositories(PDO $pdo)
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
        $favoriteRepo = new PdoFavoriteRepository(
            $pdo,
            $dashboardRepo,
            $linkRepo,
            new FavoriteEntityMapper(),
            new DashboardEntityMapper(),
            new LinkEntityMapper(),
        );
        return [$dashboardRepo, $linkRepo, $favoriteRepo];
    }

    describe("findByDashboardId", function () {
        test(
            "returns all favorites for a dashboard ordered by sort_order",
            function () {
                $pdo = createFavoriteDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $link1 = TestEntityFactory::createLink();
                $link2 = TestEntityFactory::createLink();
                $link3 = TestEntityFactory::createLink();

                insertTestDashboardForFavorite($pdo, $dashboard);
                insertTestLinkForFavorite($pdo, $link1);
                insertTestLinkForFavorite($pdo, $link2);
                insertTestLinkForFavorite($pdo, $link3);

                insertFavorite(
                    $pdo,
                    $dashboard->dashboardId,
                    $link1->linkId,
                    1,
                );
                insertFavorite(
                    $pdo,
                    $dashboard->dashboardId,
                    $link2->linkId,
                    0,
                );
                insertFavorite(
                    $pdo,
                    $dashboard->dashboardId,
                    $link3->linkId,
                    2,
                );

                $collection = $repo->findByDashboardId($dashboard->dashboardId);

                expect($collection)->toHaveCount(3);
                $favorites = iterator_to_array($collection);
                expect($favorites[0]->link->linkId->toString())->toBe(
                    $link2->linkId->toString(),
                );
                expect($favorites[1]->link->linkId->toString())->toBe(
                    $link1->linkId->toString(),
                );
                expect($favorites[2]->link->linkId->toString())->toBe(
                    $link3->linkId->toString(),
                );
            },
        );

        test(
            "returns empty collection when dashboard has no favorites",
            function () {
                $pdo = createFavoriteDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();

                insertTestDashboardForFavorite($pdo, $dashboard);

                $collection = $repo->findByDashboardId($dashboard->dashboardId);

                expect($collection)->toHaveCount(0);
            },
        );

        test(
            "throws DashboardNotFoundException when dashboard does not exist",
            function () {
                $pdo = createFavoriteDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                    $pdo,
                );
                $nonExistentId = Uuid::uuid4();

                expect(
                    fn() => $repo->findByDashboardId($nonExistentId),
                )->toThrow(DashboardNotFoundException::class);
            },
        );
    });

    describe("getMaxSortOrderForDashboardId", function () {
        test("returns the highest sort_order value", function () {
            $pdo = createFavoriteDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $link3 = TestEntityFactory::createLink();

            insertTestDashboardForFavorite($pdo, $dashboard);
            insertTestLinkForFavorite($pdo, $link1);
            insertTestLinkForFavorite($pdo, $link2);
            insertTestLinkForFavorite($pdo, $link3);

            insertFavorite($pdo, $dashboard->dashboardId, $link1->linkId, 0);
            insertFavorite($pdo, $dashboard->dashboardId, $link2->linkId, 3);
            insertFavorite($pdo, $dashboard->dashboardId, $link3->linkId, 1);

            $maxSort = $repo->getMaxSortOrderForDashboardId(
                $dashboard->dashboardId,
            );

            expect($maxSort)->toBe(3);
        });

        test("returns -1 when dashboard has no favorites", function () {
            $pdo = createFavoriteDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();

            insertTestDashboardForFavorite($pdo, $dashboard);

            $maxSort = $repo->getMaxSortOrderForDashboardId(
                $dashboard->dashboardId,
            );

            expect($maxSort)->toBe(-1);
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

            $repo = new PdoFavoriteRepository(
                $mockPdo,
                $mockDashboardRepo,
                $mockLinkRepo,
                new FavoriteEntityMapper(),
                new DashboardEntityMapper(),
                new LinkEntityMapper(),
            );

            expect(
                fn() => $repo->getMaxSortOrderForDashboardId($dashboardId),
            )->toThrow(RepositoryStorageException::class);
        });
    });

    describe("addFavorite", function () {
        test("adds a link as favorite to a dashboard", function () {
            $pdo = createFavoriteDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $link = TestEntityFactory::createLink();

            insertTestDashboardForFavorite($pdo, $dashboard);
            insertTestLinkForFavorite($pdo, $link);

            $favorite = $repo->addFavorite(
                $dashboard->dashboardId,
                $link->linkId,
                0,
            );

            expect($favorite->dashboard->dashboardId->toString())->toBe(
                $dashboard->dashboardId->toString(),
            );
            expect($favorite->link->linkId->toString())->toBe(
                $link->linkId->toString(),
            );
            expect($favorite->sortOrder)->toBe(0);
        });

        test(
            "throws DashboardNotFoundException when dashboard does not exist",
            function () {
                $pdo = createFavoriteDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                    $pdo,
                );
                $link = TestEntityFactory::createLink();
                $nonExistentDashboardId = Uuid::uuid4();

                insertTestLinkForFavorite($pdo, $link);

                expect(
                    fn() => $repo->addFavorite(
                        $nonExistentDashboardId,
                        $link->linkId,
                        0,
                    ),
                )->toThrow(DashboardNotFoundException::class);
            },
        );

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $pdo = createFavoriteDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $nonExistentLinkId = Uuid::uuid4();

                insertTestDashboardForFavorite($pdo, $dashboard);

                expect(
                    fn() => $repo->addFavorite(
                        $dashboard->dashboardId,
                        $nonExistentLinkId,
                        0,
                    ),
                )->toThrow(LinkNotFoundException::class);
            },
        );

        test(
            "throws DashboardNotFoundException when dashboard foreign key constraint fails",
            function () {
                $mockPdo = Mockery::mock(PDO::class);
                $mockDashboardRepo = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $mockLinkRepo = Mockery::mock(LinkRepositoryInterface::class);
                $insertStmt = Mockery::mock(\PDOStatement::class);

                $dashboard = TestEntityFactory::createDashboard();
                $link = TestEntityFactory::createLink();
                $dashboardId = $dashboard->dashboardId;
                $linkId = $link->linkId;

                // Mock repository lookups to succeed
                $mockDashboardRepo
                    ->shouldReceive("findById")
                    ->with($dashboardId)
                    ->andReturn($dashboard);
                $mockLinkRepo
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->andReturn($link);

                // INSERT statement throws foreign key constraint error for dashboard_id
                $fkException = new \PDOException(
                    "FOREIGN KEY constraint failed: dashboard_id",
                );
                $insertStmt->shouldReceive("execute")->andThrow($fkException);

                $mockPdo
                    ->shouldReceive("prepare")
                    ->once()
                    ->with(
                        'INSERT INTO favorites (dashboard_id, link_id, sort_order, created_at)
                VALUES (:dashboard_id, :link_id, :sort_order, :created_at)',
                    )
                    ->andReturn($insertStmt);

                $repo = new PdoFavoriteRepository(
                    $mockPdo,
                    $mockDashboardRepo,
                    $mockLinkRepo,
                    new FavoriteEntityMapper(),
                    new DashboardEntityMapper(),
                    new LinkEntityMapper(),
                );

                expect(
                    fn() => $repo->addFavorite($dashboardId, $linkId, 0),
                )->toThrow(DashboardNotFoundException::class);
            },
        );

        test(
            "throws LinkNotFoundException when link foreign key constraint fails",
            function () {
                $mockPdo = Mockery::mock(PDO::class);
                $mockDashboardRepo = Mockery::mock(
                    DashboardRepositoryInterface::class,
                );
                $mockLinkRepo = Mockery::mock(LinkRepositoryInterface::class);
                $insertStmt = Mockery::mock(\PDOStatement::class);

                $dashboard = TestEntityFactory::createDashboard();
                $link = TestEntityFactory::createLink();
                $dashboardId = $dashboard->dashboardId;
                $linkId = $link->linkId;

                // Mock repository lookups to succeed
                $mockDashboardRepo
                    ->shouldReceive("findById")
                    ->with($dashboardId)
                    ->andReturn($dashboard);
                $mockLinkRepo
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->andReturn($link);

                // INSERT statement throws foreign key constraint error for link_id
                $fkException = new \PDOException(
                    "FOREIGN KEY constraint failed: link_id",
                );
                $insertStmt->shouldReceive("execute")->andThrow($fkException);

                $mockPdo
                    ->shouldReceive("prepare")
                    ->once()
                    ->with(
                        'INSERT INTO favorites (dashboard_id, link_id, sort_order, created_at)
                VALUES (:dashboard_id, :link_id, :sort_order, :created_at)',
                    )
                    ->andReturn($insertStmt);

                $repo = new PdoFavoriteRepository(
                    $mockPdo,
                    $mockDashboardRepo,
                    $mockLinkRepo,
                    new FavoriteEntityMapper(),
                    new DashboardEntityMapper(),
                    new LinkEntityMapper(),
                );

                expect(
                    fn() => $repo->addFavorite($dashboardId, $linkId, 0),
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

                $dashboard = TestEntityFactory::createDashboard();
                $link = TestEntityFactory::createLink();
                $dashboardId = $dashboard->dashboardId;
                $linkId = $link->linkId;

                // Mock repository lookups to succeed
                $mockDashboardRepo
                    ->shouldReceive("findById")
                    ->with($dashboardId)
                    ->andReturn($dashboard);
                $mockLinkRepo
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->andReturn($link);

                // INSERT statement throws unexpected error
                $unexpectedException = new \PDOException("Disk I/O error");
                $insertStmt
                    ->shouldReceive("execute")
                    ->andThrow($unexpectedException);

                $mockPdo
                    ->shouldReceive("prepare")
                    ->once()
                    ->with(
                        'INSERT INTO favorites (dashboard_id, link_id, sort_order, created_at)
                VALUES (:dashboard_id, :link_id, :sort_order, :created_at)',
                    )
                    ->andReturn($insertStmt);

                $repo = new PdoFavoriteRepository(
                    $mockPdo,
                    $mockDashboardRepo,
                    $mockLinkRepo,
                    new FavoriteEntityMapper(),
                    new DashboardEntityMapper(),
                    new LinkEntityMapper(),
                );

                expect(
                    fn() => $repo->addFavorite($dashboardId, $linkId, 0),
                )->toThrow(\PDOException::class);
            },
        );
    });

    describe("removeFavorite", function () {
        test("removes a favorite from a dashboard", function () {
            $pdo = createFavoriteDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $link = TestEntityFactory::createLink();

            insertTestDashboardForFavorite($pdo, $dashboard);
            insertTestLinkForFavorite($pdo, $link);
            insertFavorite($pdo, $dashboard->dashboardId, $link->linkId);

            $repo->removeFavorite($dashboard->dashboardId, $link->linkId);

            expect(
                $repo->isFavorite($dashboard->dashboardId, $link->linkId),
            )->toBeFalse();
        });

        test(
            "throws FavoriteNotFoundException when favorite does not exist",
            function () {
                $pdo = createFavoriteDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $link = TestEntityFactory::createLink();

                insertTestDashboardForFavorite($pdo, $dashboard);
                insertTestLinkForFavorite($pdo, $link);

                expect(
                    fn() => $repo->removeFavorite(
                        $dashboard->dashboardId,
                        $link->linkId,
                    ),
                )->toThrow(FavoriteNotFoundException::class);
            },
        );
    });

    describe("isFavorite", function () {
        test("returns true when link is favorited on dashboard", function () {
            $pdo = createFavoriteDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $link = TestEntityFactory::createLink();

            insertTestDashboardForFavorite($pdo, $dashboard);
            insertTestLinkForFavorite($pdo, $link);
            insertFavorite($pdo, $dashboard->dashboardId, $link->linkId);

            expect(
                $repo->isFavorite($dashboard->dashboardId, $link->linkId),
            )->toBeTrue();
        });

        test(
            "returns false when link is not favorited on dashboard",
            function () {
                $pdo = createFavoriteDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $link = TestEntityFactory::createLink();

                insertTestDashboardForFavorite($pdo, $dashboard);
                insertTestLinkForFavorite($pdo, $link);

                expect(
                    $repo->isFavorite($dashboard->dashboardId, $link->linkId),
                )->toBeFalse();
            },
        );
    });

    describe("updateSortOrder", function () {
        test("updates sort order for a favorite", function () {
            $pdo = createFavoriteDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $link = TestEntityFactory::createLink();

            insertTestDashboardForFavorite($pdo, $dashboard);
            insertTestLinkForFavorite($pdo, $link);
            insertFavorite($pdo, $dashboard->dashboardId, $link->linkId, 0);

            $repo->updateSortOrder($dashboard->dashboardId, $link->linkId, 5);

            $collection = $repo->findByDashboardId($dashboard->dashboardId);
            $favorites = iterator_to_array($collection);
            expect($favorites[0]->sortOrder)->toBe(5);
        });

        test(
            "throws FavoriteNotFoundException when favorite does not exist",
            function () {
                $pdo = createFavoriteDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $link = TestEntityFactory::createLink();

                insertTestDashboardForFavorite($pdo, $dashboard);
                insertTestLinkForFavorite($pdo, $link);

                expect(
                    fn() => $repo->updateSortOrder(
                        $dashboard->dashboardId,
                        $link->linkId,
                        5,
                    ),
                )->toThrow(FavoriteNotFoundException::class);
            },
        );
    });

    describe("reorderFavorites", function () {
        test(
            "reorders favorites in a dashboard by position in collection",
            function () {
                $pdo = createFavoriteDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                    $pdo,
                );
                $dashboard = TestEntityFactory::createDashboard();
                $link1 = TestEntityFactory::createLink();
                $link2 = TestEntityFactory::createLink();
                $link3 = TestEntityFactory::createLink();

                insertTestDashboardForFavorite($pdo, $dashboard);
                insertTestLinkForFavorite($pdo, $link1);
                insertTestLinkForFavorite($pdo, $link2);
                insertTestLinkForFavorite($pdo, $link3);

                insertFavorite(
                    $pdo,
                    $dashboard->dashboardId,
                    $link1->linkId,
                    0,
                );
                insertFavorite(
                    $pdo,
                    $dashboard->dashboardId,
                    $link2->linkId,
                    1,
                );
                insertFavorite(
                    $pdo,
                    $dashboard->dashboardId,
                    $link3->linkId,
                    2,
                );

                // Get current favorites and reorder them
                $currentFavorites = $repo->findByDashboardId(
                    $dashboard->dashboardId,
                );
                $favorites = iterator_to_array($currentFavorites);
                // Reorder: link3, link1, link2
                $reorderedFavorites = new FavoriteCollection(
                    $favorites[2],
                    $favorites[0],
                    $favorites[1],
                );

                $repo->reorderFavorites(
                    $dashboard->dashboardId,
                    $reorderedFavorites,
                );

                $collection = $repo->findByDashboardId($dashboard->dashboardId);
                $reorderedArray = iterator_to_array($collection);
                expect($reorderedArray[0]->link->linkId->toString())->toBe(
                    $link3->linkId->toString(),
                );
                expect($reorderedArray[1]->link->linkId->toString())->toBe(
                    $link1->linkId->toString(),
                );
                expect($reorderedArray[2]->link->linkId->toString())->toBe(
                    $link2->linkId->toString(),
                );
            },
        );
    });

    describe("countForDashboardId", function () {
        test("returns correct count of favorites in a dashboard", function () {
            $pdo = createFavoriteDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();

            insertTestDashboardForFavorite($pdo, $dashboard);
            insertTestLinkForFavorite($pdo, $link1);
            insertTestLinkForFavorite($pdo, $link2);

            insertFavorite($pdo, $dashboard->dashboardId, $link1->linkId);
            insertFavorite($pdo, $dashboard->dashboardId, $link2->linkId);

            expect($repo->countForDashboardId($dashboard->dashboardId))->toBe(
                2,
            );
        });

        test("returns 0 when dashboard has no favorites", function () {
            $pdo = createFavoriteDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                $pdo,
            );
            $dashboard = TestEntityFactory::createDashboard();

            insertTestDashboardForFavorite($pdo, $dashboard);

            expect($repo->countForDashboardId($dashboard->dashboardId))->toBe(
                0,
            );
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

            $repo = new PdoFavoriteRepository(
                $mockPdo,
                $mockDashboardRepo,
                $mockLinkRepo,
                new FavoriteEntityMapper(),
                new DashboardEntityMapper(),
                new LinkEntityMapper(),
            );

            expect(fn() => $repo->countForDashboardId($dashboardId))->toThrow(
                RepositoryStorageException::class,
            );
        });
    });

    describe("findDashboardsWithLinkAsFavorite", function () {
        test("returns all dashboards where a link is favorited", function () {
            $pdo = createFavoriteDatabase();
            [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                $pdo,
            );
            $dashboard1 = TestEntityFactory::createDashboard();
            $dashboard2 = TestEntityFactory::createDashboard();
            $link = TestEntityFactory::createLink();

            insertTestDashboardForFavorite($pdo, $dashboard1);
            insertTestDashboardForFavorite($pdo, $dashboard2);
            insertTestLinkForFavorite($pdo, $link);

            insertFavorite($pdo, $dashboard1->dashboardId, $link->linkId);
            insertFavorite($pdo, $dashboard2->dashboardId, $link->linkId);

            $collection = $repo->findDashboardsWithLinkAsFavorite(
                $link->linkId,
            );

            expect($collection)->toHaveCount(2);
        });

        test(
            "returns empty collection when link is not favorited anywhere",
            function () {
                $pdo = createFavoriteDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                    $pdo,
                );
                $link = TestEntityFactory::createLink();

                insertTestLinkForFavorite($pdo, $link);

                $collection = $repo->findDashboardsWithLinkAsFavorite(
                    $link->linkId,
                );

                expect($collection)->toHaveCount(0);
            },
        );

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $pdo = createFavoriteDatabase();
                [$dashboardRepo, $linkRepo, $repo] = createFavoriteRepositories(
                    $pdo,
                );
                $nonExistentId = Uuid::uuid4();

                expect(
                    fn() => $repo->findDashboardsWithLinkAsFavorite(
                        $nonExistentId,
                    ),
                )->toThrow(LinkNotFoundException::class);
            },
        );
    });
});
