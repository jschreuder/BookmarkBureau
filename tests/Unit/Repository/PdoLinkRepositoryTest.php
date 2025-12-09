<?php

use jschreuder\BookmarkBureau\Composite\TagNameCollection;
use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\TagEntityMapper;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Repository\PdoLinkRepository;
use Ramsey\Uuid\Uuid;

describe("PdoLinkRepository", function () {
    function createLinkDatabase(): PDO
    {
        $pdo = new PDO("sqlite::memory:");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("PRAGMA foreign_keys = ON");

        // Create schema
        $pdo->exec('
            CREATE TABLE links (
                link_id BLOB PRIMARY KEY,
                url TEXT NOT NULL,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                icon TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
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

            CREATE TABLE category_links (
                category_id BLOB NOT NULL,
                link_id BLOB NOT NULL,
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (category_id, link_id),
                FOREIGN KEY (link_id) REFERENCES links(link_id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
            );
        ');

        return $pdo;
    }

    function insertTestLink(PDO $pdo, $link): void
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

    describe("findById", function () {
        test("finds and returns a link by ID", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );
            $link = TestEntityFactory::createLink();

            insertTestLink($pdo, $link);

            $found = $repo->findById($link->linkId);

            expect($found->linkId->toString())->toBe($link->linkId->toString());
            expect((string) $found->url)->toBe((string) $link->url);
            expect((string) $found->title)->toBe((string) $link->title);
            expect($found->description)->toBe($link->description);
        });

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $pdo = createLinkDatabase();
                $repo = new PdoLinkRepository(
                    $pdo,
                    new LinkEntityMapper(),
                    new TagEntityMapper(),
                );
                $nonExistentId = Uuid::uuid4();

                expect(fn() => $repo->findById($nonExistentId))->toThrow(
                    LinkNotFoundException::class,
                );
            },
        );

        test("correctly maps nullable icon field", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );
            $linkWithoutIcon = TestEntityFactory::createLink(icon: null);

            insertTestLink($pdo, $linkWithoutIcon);

            $found = $repo->findById($linkWithoutIcon->linkId);

            expect($found->icon)->toBeNull();
        });
    });

    describe("save", function () {
        test("inserts a new link", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );
            $link = TestEntityFactory::createLink();

            $repo->insert($link);

            $found = $repo->findById($link->linkId);
            expect((string) $found->url)->toBe((string) $link->url);
            expect((string) $found->title)->toBe((string) $link->title);
        });

        test("updates an existing link", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );
            $link = TestEntityFactory::createLink();

            $repo->insert($link);

            $link->url = new Url("https://updated.com");
            $link->title = new Title("Updated Title");
            $repo->update($link);

            $found = $repo->findById($link->linkId);
            expect((string) $found->url)->toBe("https://updated.com");
            expect((string) $found->title)->toBe("Updated Title");
        });

        test(
            "throws LinkNotFoundException when updating non-existent link",
            function () {
                $pdo = createLinkDatabase();
                $repo = new PdoLinkRepository(
                    $pdo,
                    new LinkEntityMapper(),
                    new TagEntityMapper(),
                );

                $link = TestEntityFactory::createLink();

                expect(fn() => $repo->update($link))->toThrow(
                    LinkNotFoundException::class,
                );
            },
        );

        test("preserves timestamps on insert", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );
            $createdAt = new DateTimeImmutable("2024-01-01 10:00:00");
            $updatedAt = new DateTimeImmutable("2024-01-01 12:00:00");
            $link = TestEntityFactory::createLink(
                createdAt: $createdAt,
                updatedAt: $updatedAt,
            );

            $repo->insert($link);

            $found = $repo->findById($link->linkId);
            expect($found->createdAt->format("Y-m-d H:i:s"))->toBe(
                "2024-01-01 10:00:00",
            );
            expect($found->updatedAt->format("Y-m-d H:i:s"))->toBe(
                "2024-01-01 12:00:00",
            );
        });

        test("saves links with null icon", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );
            $link = TestEntityFactory::createLink(icon: null);

            $repo->insert($link);

            $found = $repo->findById($link->linkId);
            expect($found->icon)->toBeNull();
        });
    });

    describe("delete", function () {
        test("deletes a link by ID", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );
            $link = TestEntityFactory::createLink();

            $repo->insert($link);
            $repo->delete($link);

            expect(fn() => $repo->findById($link->linkId))->toThrow(
                LinkNotFoundException::class,
            );
        });

        test("cascades delete to link_tags", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );
            $link = TestEntityFactory::createLink();

            $repo->insert($link);
            $pdo->prepare("INSERT INTO tags (tag_name) VALUES (?)")->execute([
                "test-tag",
            ]);
            $pdo->prepare(
                "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
            )->execute([$link->linkId->getBytes(), "test-tag"]);

            // Verify the tag was inserted
            $checkStmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM link_tags WHERE link_id = ?",
            );
            $checkStmt->execute([$link->linkId->getBytes()]);
            $beforeDelete = $checkStmt->fetch(PDO::FETCH_ASSOC);
            expect($beforeDelete["count"])->toBe(1);

            $repo->delete($link);

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM link_tags WHERE link_id = ?",
            );
            $stmt->execute([$link->linkId->getBytes()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            expect($result["count"])->toBe(0);
        });
    });

    describe("listForCategoryId", function () {
        test("returns links in a category ordered by sort_order", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $dashboardId = Uuid::uuid4();
            $categoryId = Uuid::uuid4();
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $link3 = TestEntityFactory::createLink();

            // Create dashboard first
            $pdo->prepare(
                "INSERT INTO dashboards (dashboard_id, title, description) VALUES (?, ?, ?)",
            )->execute([$dashboardId->getBytes(), "Test Dashboard", "Test"]);

            // Create category
            $pdo->prepare(
                "INSERT INTO categories (category_id, dashboard_id, title) VALUES (?, ?, ?)",
            )->execute([
                $categoryId->getBytes(),
                $dashboardId->getBytes(),
                "Test Category",
            ]);

            insertTestLink($pdo, $link1);
            insertTestLink($pdo, $link2);
            insertTestLink($pdo, $link3);

            // Add links to category with different sort orders
            $pdo->prepare(
                "INSERT INTO category_links (category_id, link_id, sort_order) VALUES (?, ?, ?)",
            )->execute([
                $categoryId->getBytes(),
                $link1->linkId->getBytes(),
                3,
            ]);
            $pdo->prepare(
                "INSERT INTO category_links (category_id, link_id, sort_order) VALUES (?, ?, ?)",
            )->execute([
                $categoryId->getBytes(),
                $link2->linkId->getBytes(),
                1,
            ]);
            $pdo->prepare(
                "INSERT INTO category_links (category_id, link_id, sort_order) VALUES (?, ?, ?)",
            )->execute([
                $categoryId->getBytes(),
                $link3->linkId->getBytes(),
                2,
            ]);

            $collection = $repo->listForCategoryId($categoryId);

            expect($collection)->toHaveCount(3);
            $links = iterator_to_array($collection);
            expect($links[0]->linkId->toString())->toBe(
                $link2->linkId->toString(),
            );
            expect($links[1]->linkId->toString())->toBe(
                $link3->linkId->toString(),
            );
            expect($links[2]->linkId->toString())->toBe(
                $link1->linkId->toString(),
            );
        });

        test(
            "throws CategoryNotFoundException when category does not exist",
            function () {
                $pdo = createLinkDatabase();
                $repo = new PdoLinkRepository(
                    $pdo,
                    new LinkEntityMapper(),
                    new TagEntityMapper(),
                );
                $nonExistentCategoryId = Uuid::uuid4();

                expect(
                    fn() => $repo->listForCategoryId($nonExistentCategoryId),
                )->toThrow(CategoryNotFoundException::class);
            },
        );

        test(
            "returns empty collection for category with no links",
            function () {
                $pdo = createLinkDatabase();
                $repo = new PdoLinkRepository(
                    $pdo,
                    new LinkEntityMapper(),
                    new TagEntityMapper(),
                );

                $dashboardId = Uuid::uuid4();
                $categoryId = Uuid::uuid4();

                // Create dashboard first
                $pdo->prepare(
                    "INSERT INTO dashboards (dashboard_id, title, description) VALUES (?, ?, ?)",
                )->execute([
                    $dashboardId->getBytes(),
                    "Test Dashboard",
                    "Test",
                ]);

                $pdo->prepare(
                    "INSERT INTO categories (category_id, dashboard_id, title) VALUES (?, ?, ?)",
                )->execute([
                    $categoryId->getBytes(),
                    $dashboardId->getBytes(),
                    "Empty Category",
                ]);

                $collection = $repo->listForCategoryId($categoryId);

                expect($collection)->toHaveCount(0);
            },
        );
    });
});
