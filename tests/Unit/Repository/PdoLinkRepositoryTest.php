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

    describe("findAll", function () {
        test("returns all links ordered by created_at descending", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $link1 = TestEntityFactory::createLink(
                createdAt: new DateTimeImmutable("2024-01-01 10:00:00"),
            );
            $link2 = TestEntityFactory::createLink(
                createdAt: new DateTimeImmutable("2024-01-02 10:00:00"),
            );
            $link3 = TestEntityFactory::createLink(
                createdAt: new DateTimeImmutable("2024-01-03 10:00:00"),
            );

            insertTestLink($pdo, $link1);
            insertTestLink($pdo, $link2);
            insertTestLink($pdo, $link3);

            $collection = $repo->findAll();

            expect($collection)->toHaveCount(3);
            $links = iterator_to_array($collection);
            expect($links[0]->linkId->toString())->toBe(
                $link3->linkId->toString(),
            );
            expect($links[1]->linkId->toString())->toBe(
                $link2->linkId->toString(),
            );
            expect($links[2]->linkId->toString())->toBe(
                $link1->linkId->toString(),
            );
        });

        test("respects limit parameter", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            for ($i = 0; $i < 5; $i++) {
                insertTestLink($pdo, TestEntityFactory::createLink());
            }

            $collection = $repo->findAll(limit: 2);

            expect($collection)->toHaveCount(2);
        });

        test("respects offset parameter", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $link3 = TestEntityFactory::createLink();

            insertTestLink($pdo, $link1);
            insertTestLink($pdo, $link2);
            insertTestLink($pdo, $link3);

            $collection = $repo->findAll(limit: 10, offset: 1);

            expect($collection)->toHaveCount(2);
        });

        test("returns empty collection when no links exist", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $collection = $repo->findAll();

            expect($collection)->toHaveCount(0);
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

            $repo->save($link);

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

            $repo->save($link);

            $link->url = new Url("https://updated.com");
            $link->title = new Title("Updated Title");
            $repo->save($link);

            $found = $repo->findById($link->linkId);
            expect((string) $found->url)->toBe("https://updated.com");
            expect((string) $found->title)->toBe("Updated Title");
        });

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

            $repo->save($link);

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

            $repo->save($link);

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

            $repo->save($link);
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

            $repo->save($link);
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

    describe("count", function () {
        test("returns the total number of links", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            insertTestLink($pdo, TestEntityFactory::createLink());
            insertTestLink($pdo, TestEntityFactory::createLink());
            insertTestLink($pdo, TestEntityFactory::createLink());

            expect($repo->count())->toBe(3);
        });

        test("returns 0 when no links exist", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            expect($repo->count())->toBe(0);
        });
    });

    describe("findByTags", function () {
        test("returns empty collection when given empty tag list", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $collection = $repo->findByTags(new TagNameCollection());

            expect($collection)->toHaveCount(0);
        });

        test("returns empty collection when no tags match", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $link = TestEntityFactory::createLink();
            insertTestLink($pdo, $link);

            $collection = $repo->findByTags(
                new TagNameCollection(new TagName("nonexistent")),
            );

            expect($collection)->toHaveCount(0);
        });

        test(
            "returns links matching all specified tags with AND condition",
            function () {
                $pdo = createLinkDatabase();
                $repo = new PdoLinkRepository(
                    $pdo,
                    new LinkEntityMapper(),
                    new TagEntityMapper(),
                );

                $link1 = TestEntityFactory::createLink();
                $link2 = TestEntityFactory::createLink();
                $link3 = TestEntityFactory::createLink();

                insertTestLink($pdo, $link1);
                insertTestLink($pdo, $link2);
                insertTestLink($pdo, $link3);

                // Create tags
                $pdo->prepare(
                    "INSERT INTO tags (tag_name) VALUES (?)",
                )->execute(["php"]);
                $pdo->prepare(
                    "INSERT INTO tags (tag_name) VALUES (?)",
                )->execute(["middle"]);
                $pdo->prepare(
                    "INSERT INTO tags (tag_name) VALUES (?)",
                )->execute(["testing"]);

                // Link 1: php, middle
                $pdo->prepare(
                    "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
                )->execute([$link1->linkId->getBytes(), "php"]);
                $pdo->prepare(
                    "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
                )->execute([$link1->linkId->getBytes(), "middle"]);

                // Link 2: php, testing
                $pdo->prepare(
                    "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
                )->execute([$link2->linkId->getBytes(), "php"]);
                $pdo->prepare(
                    "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
                )->execute([$link2->linkId->getBytes(), "testing"]);

                // Link 3: php, middle, testing
                $pdo->prepare(
                    "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
                )->execute([$link3->linkId->getBytes(), "php"]);
                $pdo->prepare(
                    "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
                )->execute([$link3->linkId->getBytes(), "middle"]);
                $pdo->prepare(
                    "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
                )->execute([$link3->linkId->getBytes(), "testing"]);

                // Find links with both 'php' and 'middle' (AND condition)
                $collection = $repo->findByTags(
                    new TagNameCollection(
                        new TagName("php"),
                        new TagName("middle"),
                    ),
                );

                expect($collection)->toHaveCount(2);
                $links = iterator_to_array($collection);
                $ids = array_map(
                    fn($link) => $link->linkId->toString(),
                    $links,
                );
                expect(in_array($link1->linkId->toString(), $ids))->toBeTrue();
                expect(in_array($link3->linkId->toString(), $ids))->toBeTrue();
            },
        );

        test("finds links with single tag", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();

            insertTestLink($pdo, $link1);
            insertTestLink($pdo, $link2);

            $pdo->prepare("INSERT INTO tags (tag_name) VALUES (?)")->execute([
                "php",
            ]);
            $pdo->prepare(
                "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
            )->execute([$link1->linkId->getBytes(), "php"]);

            $collection = $repo->findByTags(
                new TagNameCollection(new TagName("php")),
            );

            expect($collection)->toHaveCount(1);
            $links = iterator_to_array($collection);
            expect($links[0]->linkId->toString())->toBe(
                $link1->linkId->toString(),
            );
        });

        test("loads tags with links", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $link = TestEntityFactory::createLink();
            insertTestLink($pdo, $link);

            // Insert tags
            $pdo->prepare(
                "INSERT INTO tags (tag_name, color) VALUES (?, ?)",
            )->execute(["php", "#FF5733"]);
            $pdo->prepare(
                "INSERT INTO tags (tag_name, color) VALUES (?, ?)",
            )->execute(["laravel", "#3498DB"]);

            // Associate tags with link
            $pdo->prepare(
                "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
            )->execute([$link->linkId->getBytes(), "php"]);
            $pdo->prepare(
                "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
            )->execute([$link->linkId->getBytes(), "laravel"]);

            // Find and verify tags are loaded
            $found = $repo->findById($link->linkId);

            expect($found->tags)->toHaveCount(2);
            $tagNames = array_map(
                fn(Tag $tag) => (string) $tag->tagName,
                iterator_to_array($found->tags),
            );
            expect($tagNames)->toContain("php");
            expect($tagNames)->toContain("laravel");
        });

        test(
            "finds links with multiple tags returns tags for each link",
            function () {
                $pdo = createLinkDatabase();
                $repo = new PdoLinkRepository(
                    $pdo,
                    new LinkEntityMapper(),
                    new TagEntityMapper(),
                );

                $link1 = TestEntityFactory::createLink();
                $link2 = TestEntityFactory::createLink();

                insertTestLink($pdo, $link1);
                insertTestLink($pdo, $link2);

                // Create tags
                $pdo->prepare(
                    "INSERT INTO tags (tag_name) VALUES (?)",
                )->execute(["php"]);
                $pdo->prepare(
                    "INSERT INTO tags (tag_name) VALUES (?)",
                )->execute(["laravel"]);
                $pdo->prepare(
                    "INSERT INTO tags (tag_name) VALUES (?)",
                )->execute(["backend"]);

                // Link1 has php and laravel tags
                $pdo->prepare(
                    "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
                )->execute([$link1->linkId->getBytes(), "php"]);
                $pdo->prepare(
                    "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
                )->execute([$link1->linkId->getBytes(), "laravel"]);

                // Link2 has only backend tag
                $pdo->prepare(
                    "INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)",
                )->execute([$link2->linkId->getBytes(), "backend"]);

                // Find all links and verify each has correct tags
                $collection = $repo->findAll();

                expect($collection)->toHaveCount(2);
                $links = iterator_to_array($collection);

                // Find link1 and link2 in results
                $foundLink1 =
                    array_values(
                        array_filter(
                            $links,
                            fn($l) => $l->linkId->toString() ===
                                $link1->linkId->toString(),
                        ),
                    )[0] ?? null;
                $foundLink2 =
                    array_values(
                        array_filter(
                            $links,
                            fn($l) => $l->linkId->toString() ===
                                $link2->linkId->toString(),
                        ),
                    )[0] ?? null;

                expect($foundLink1)->not->toBeNull();
                expect($foundLink2)->not->toBeNull();

                expect($foundLink1->tags)->toHaveCount(2);
                $link1Tags = array_map(
                    fn(Tag $tag) => (string) $tag->tagName,
                    iterator_to_array($foundLink1->tags),
                );
                expect($link1Tags)->toContain("php");
                expect($link1Tags)->toContain("laravel");

                expect($foundLink2->tags)->toHaveCount(1);
                $link2Tags = array_map(
                    fn(Tag $tag) => (string) $tag->tagName,
                    iterator_to_array($foundLink2->tags),
                );
                expect($link2Tags)->toContain("backend");
            },
        );
    });

    describe("findByCategoryId", function () {
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

            $collection = $repo->findByCategoryId($categoryId);

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
                    fn() => $repo->findByCategoryId($nonExistentCategoryId),
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

                $collection = $repo->findByCategoryId($categoryId);

                expect($collection)->toHaveCount(0);
            },
        );
    });

    describe("search", function () {
        test("searches links by title using LIKE", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $link1 = TestEntityFactory::createLink(
                title: new Title("PHP Tutorial"),
                description: "Learn programming",
            );
            $link2 = TestEntityFactory::createLink(
                title: new Title("JavaScript Guide"),
                description: "Learn JavaScript",
            );
            $link3 = TestEntityFactory::createLink(
                title: new Title("Middle Framework"),
                description: "Build web apps with PHP",
            );

            insertTestLink($pdo, $link1);
            insertTestLink($pdo, $link2);
            insertTestLink($pdo, $link3);

            // Search for "PHP" should find link1 and link3
            $collection = $repo->search("PHP");

            expect($collection)->toHaveCount(2);
            $links = iterator_to_array($collection);
            $ids = array_map(fn($link) => $link->linkId->toString(), $links);
            expect(in_array($link1->linkId->toString(), $ids))->toBeTrue();
            expect(in_array($link3->linkId->toString(), $ids))->toBeTrue();
        });

        test("searches links by description using LIKE", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $link1 = TestEntityFactory::createLink(
                title: new Title("Tutorial 1"),
                description: "Learn Middle framework",
            );
            $link2 = TestEntityFactory::createLink(
                title: new Title("Tutorial 2"),
                description: "Learn JavaScript",
            );

            insertTestLink($pdo, $link1);
            insertTestLink($pdo, $link2);

            $collection = $repo->search("Middle");

            expect($collection)->toHaveCount(1);
            $links = iterator_to_array($collection);
            expect($links[0]->linkId->toString())->toBe(
                $link1->linkId->toString(),
            );
        });

        test("search is case-insensitive", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $link = TestEntityFactory::createLink(
                title: new Title("PHP Tutorial"),
                description: "Learn PHP",
            );

            insertTestLink($pdo, $link);

            // Search with different casing
            $collection = $repo->search("php");

            expect($collection)->toHaveCount(1);
        });

        test("returns empty collection when no matches found", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            $link = TestEntityFactory::createLink(
                title: new Title("PHP Tutorial"),
                description: "Learn PHP",
            );

            insertTestLink($pdo, $link);

            $collection = $repo->search("Python");

            expect($collection)->toHaveCount(0);
        });

        test("respects limit parameter", function () {
            $pdo = createLinkDatabase();
            $repo = new PdoLinkRepository(
                $pdo,
                new LinkEntityMapper(),
                new TagEntityMapper(),
            );

            for ($i = 0; $i < 5; $i++) {
                insertTestLink(
                    $pdo,
                    TestEntityFactory::createLink(
                        title: new Title("Test Link " . $i),
                        description: "Test content",
                    ),
                );
            }

            $collection = $repo->search("Test", limit: 2);

            expect($collection)->toHaveCount(2);
        });
    });
});
