<?php

use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Exception\TagNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Repository\PdoTagRepository;
use Ramsey\Uuid\Uuid;

describe('PdoTagRepository', function () {

    function createTagDatabase(): PDO {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

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
        ');

        return $pdo;
    }

    function insertTestTag(PDO $pdo, $tag): void {
        $stmt = $pdo->prepare('INSERT INTO tags (tag_name, color) VALUES (?, ?)');
        $stmt->execute([
            $tag->tagName->value,
            $tag->color ? $tag->color->value : null,
        ]);
    }

    function insertTestLinkForTag(PDO $pdo, $linkId): void {
        $stmt = $pdo->prepare(
            'INSERT INTO links (link_id, url, title, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $linkId->getBytes(),
            'https://example.com',
            'Example Title',
            'Example Description',
            '2024-01-01 12:00:00',
            '2024-01-01 12:00:00',
        ]);
    }

    describe('findByName', function () {
        test('finds and returns a tag by name', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);
            $tag = TestEntityFactory::createTag(tagName: new TagName('example-tag'));

            insertTestTag($pdo, $tag);

            $found = $repo->findByName('example-tag');

            expect($found->tagName->value)->toBe('example-tag');
        });

        test('returns tag with color when color is set', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);
            $color = new HexColor('#FF0000');
            $tag = TestEntityFactory::createTag(tagName: new TagName('red-tag'), color: $color);

            insertTestTag($pdo, $tag);

            $found = $repo->findByName('red-tag');

            expect((string) $found->color)->toBe('#FF0000');
        });

        test('returns tag with null color when color is not set', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);
            $tag = TestEntityFactory::createTag(tagName: new TagName('no-color-tag'), color: null);

            insertTestTag($pdo, $tag);

            $found = $repo->findByName('no-color-tag');

            expect($found->color)->toBeNull();
        });

        test('throws TagNotFoundException when tag does not exist', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            expect(fn() => $repo->findByName('nonexistent'))
                ->toThrow(TagNotFoundException::class);
        });
    });

    describe('findAll', function () {
        test('returns all tags ordered alphabetically', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $tag1 = TestEntityFactory::createTag(tagName: new TagName('zebra'));
            $tag2 = TestEntityFactory::createTag(tagName: new TagName('alpha'));
            $tag3 = TestEntityFactory::createTag(tagName: new TagName('beta'));

            insertTestTag($pdo, $tag1);
            insertTestTag($pdo, $tag2);
            insertTestTag($pdo, $tag3);

            $collection = $repo->findAll();

            expect($collection)->toHaveCount(3);
            $tags = iterator_to_array($collection);
            expect($tags[0]->tagName->value)->toBe('alpha');
            expect($tags[1]->tagName->value)->toBe('beta');
            expect($tags[2]->tagName->value)->toBe('zebra');
        });

        test('returns empty collection when no tags exist', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $collection = $repo->findAll();

            expect($collection)->toHaveCount(0);
        });
    });

    describe('findTagsForLinkId', function () {
        test('returns all tags for a specific link ordered alphabetically', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $linkId = Uuid::uuid4();
            insertTestLinkForTag($pdo, $linkId);

            $tag1 = TestEntityFactory::createTag(tagName: new TagName('php'));
            $tag2 = TestEntityFactory::createTag(tagName: new TagName('middle'));
            $tag3 = TestEntityFactory::createTag(tagName: new TagName('framework'));

            insertTestTag($pdo, $tag1);
            insertTestTag($pdo, $tag2);
            insertTestTag($pdo, $tag3);

            // Assign tags in non-alphabetical order
            $pdo->prepare('INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)')
                ->execute([$linkId->getBytes(), 'php']);
            $pdo->prepare('INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)')
                ->execute([$linkId->getBytes(), 'framework']);
            $pdo->prepare('INSERT INTO link_tags (link_id, tag_name) VALUES (?, ?)')
                ->execute([$linkId->getBytes(), 'middle']);

            $collection = $repo->findTagsForLinkId($linkId);

            expect($collection)->toHaveCount(3);
            $tags = iterator_to_array($collection);
            expect($tags[0]->tagName->value)->toBe('framework');
            expect($tags[1]->tagName->value)->toBe('middle');
            expect($tags[2]->tagName->value)->toBe('php');
        });

        test('returns empty collection when link has no tags', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $linkId = Uuid::uuid4();
            insertTestLinkForTag($pdo, $linkId);

            $collection = $repo->findTagsForLinkId($linkId);

            expect($collection)->toHaveCount(0);
        });

        test('throws LinkNotFoundException when link does not exist', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $nonExistentLinkId = Uuid::uuid4();

            expect(fn() => $repo->findTagsForLinkId($nonExistentLinkId))
                ->toThrow(LinkNotFoundException::class);
        });
    });

    describe('searchByName', function () {
        test('returns tags matching prefix search', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            insertTestTag($pdo, TestEntityFactory::createTag(tagName: new TagName('php')));
            insertTestTag($pdo, TestEntityFactory::createTag(tagName: new TagName('python')));
            insertTestTag($pdo, TestEntityFactory::createTag(tagName: new TagName('javascript')));

            $collection = $repo->searchByName('p');

            expect($collection)->toHaveCount(2);
            $tags = iterator_to_array($collection);
            $names = array_map(fn($tag) => $tag->tagName->value, $tags);
            expect(in_array('php', $names))->toBeTrue();
            expect(in_array('python', $names))->toBeTrue();
        });

        test('returns tags ordered alphabetically', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            insertTestTag($pdo, TestEntityFactory::createTag(tagName: new TagName('zebra')));
            insertTestTag($pdo, TestEntityFactory::createTag(tagName: new TagName('zulu')));
            insertTestTag($pdo, TestEntityFactory::createTag(tagName: new TagName('alpha')));

            $collection = $repo->searchByName('z');

            expect($collection)->toHaveCount(2);
            $tags = iterator_to_array($collection);
            expect($tags[0]->tagName->value)->toBe('zebra');
            expect($tags[1]->tagName->value)->toBe('zulu');
        });

        test('respects limit parameter', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            for ($i = 0; $i < 5; $i++) {
                insertTestTag($pdo, TestEntityFactory::createTag(tagName: new TagName('test-' . $i)));
            }

            $collection = $repo->searchByName('test', limit: 2);

            expect($collection)->toHaveCount(2);
        });

        test('returns empty collection when no matches found', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            insertTestTag($pdo, TestEntityFactory::createTag(tagName: new TagName('php')));

            $collection = $repo->searchByName('python');

            expect($collection)->toHaveCount(0);
        });
    });

    describe('save', function () {
        test('inserts a new tag', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);
            $tag = TestEntityFactory::createTag(tagName: new TagName('new-tag'));

            $repo->save($tag);

            $found = $repo->findByName('new-tag');
            expect($found->tagName->value)->toBe('new-tag');
        });

        test('inserts a new tag with color', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);
            $color = new HexColor('#FF0000');
            $tag = TestEntityFactory::createTag(tagName: new TagName('red-tag'), color: $color);

            $repo->save($tag);

            $found = $repo->findByName('red-tag');
            expect((string) $found->color)->toBe('#FF0000');
        });

        test('updates an existing tag color', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $tag = TestEntityFactory::createTag(tagName: new TagName('mutable-tag'), color: new HexColor('#FF0000'));
            $repo->save($tag);

            // Update with new color
            $updatedTag = new \jschreuder\BookmarkBureau\Entity\Tag(
                tagName: new TagName('mutable-tag'),
                color: new HexColor('#00FF00')
            );
            $repo->save($updatedTag);

            $found = $repo->findByName('mutable-tag');
            expect((string) $found->color)->toBe('#00FF00');
        });

        test('silently updates an existing tag when saved again', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $tag = TestEntityFactory::createTag(tagName: new TagName('duplicate'));
            $repo->save($tag);

            // Save again - should not throw, but update instead
            $repo->save($tag);

            $found = $repo->findByName('duplicate');
            expect($found->tagName->value)->toBe('duplicate');
        });
    });

    describe('delete', function () {
        test('deletes a tag', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);
            $tag = TestEntityFactory::createTag(tagName: new TagName('deletable'));

            $repo->save($tag);
            $repo->delete($tag);

            expect(fn() => $repo->findByName('deletable'))
                ->toThrow(TagNotFoundException::class);
        });

        test('cascades delete to link_tags', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $linkId = Uuid::uuid4();
            insertTestLinkForTag($pdo, $linkId);

            $tag = TestEntityFactory::createTag(tagName: new TagName('cascadeable'));
            $repo->save($tag);
            $repo->assignToLinkId($linkId, 'cascadeable');

            // Verify tag was assigned
            $checkStmt = $pdo->prepare('SELECT COUNT(*) as count FROM link_tags WHERE tag_name = ?');
            $checkStmt->execute(['cascadeable']);
            $beforeDelete = $checkStmt->fetch(PDO::FETCH_ASSOC);
            expect($beforeDelete['count'])->toBe(1);

            $repo->delete($tag);

            // Verify cascade deleted link_tags entry
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM link_tags WHERE tag_name = ?');
            $stmt->execute(['cascadeable']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            expect($result['count'])->toBe(0);
        });
    });

    describe('assignToLinkId', function () {
        test('assigns a tag to a link', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $linkId = Uuid::uuid4();
            insertTestLinkForTag($pdo, $linkId);
            $tag = TestEntityFactory::createTag(tagName: new TagName('assignable'));
            $repo->save($tag);

            $repo->assignToLinkId($linkId, 'assignable');

            expect($repo->isAssignedToLinkId($linkId, 'assignable'))->toBeTrue();
        });

        test('throws TagNotFoundException when tag does not exist', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $linkId = Uuid::uuid4();
            insertTestLinkForTag($pdo, $linkId);

            expect(fn() => $repo->assignToLinkId($linkId, 'nonexistent'))
                ->toThrow(TagNotFoundException::class);
        });

        test('throws LinkNotFoundException when link does not exist', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $tag = TestEntityFactory::createTag(tagName: new TagName('orphan'));
            $repo->save($tag);

            $nonExistentLinkId = Uuid::uuid4();

            expect(fn() => $repo->assignToLinkId($nonExistentLinkId, 'orphan'))
                ->toThrow(LinkNotFoundException::class);
        });

        test('idempotent when assigning same tag twice', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $linkId = Uuid::uuid4();
            insertTestLinkForTag($pdo, $linkId);
            $tag = TestEntityFactory::createTag(tagName: new TagName('idempotent'));
            $repo->save($tag);

            $repo->assignToLinkId($linkId, 'idempotent');
            $repo->assignToLinkId($linkId, 'idempotent');

            expect($repo->isAssignedToLinkId($linkId, 'idempotent'))->toBeTrue();
        });
    });

    describe('removeFromLinkId', function () {
        test('removes a tag from a link', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $linkId = Uuid::uuid4();
            insertTestLinkForTag($pdo, $linkId);
            $tag = TestEntityFactory::createTag(tagName: new TagName('removable'));
            $repo->save($tag);
            $repo->assignToLinkId($linkId, 'removable');

            $repo->removeFromLinkId($linkId, 'removable');

            expect($repo->isAssignedToLinkId($linkId, 'removable'))->toBeFalse();
        });

        test('silently succeeds when removing non-assigned tag', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $linkId = Uuid::uuid4();
            insertTestLinkForTag($pdo, $linkId);
            $tag = TestEntityFactory::createTag(tagName: new TagName('unassigned'));
            $repo->save($tag);

            // Should not throw
            $repo->removeFromLinkId($linkId, 'unassigned');

            expect($repo->isAssignedToLinkId($linkId, 'unassigned'))->toBeFalse();
        });
    });

    describe('isAssignedToLinkId', function () {
        test('returns true when tag is assigned to link', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $linkId = Uuid::uuid4();
            insertTestLinkForTag($pdo, $linkId);
            $tag = TestEntityFactory::createTag(tagName: new TagName('assigned'));
            $repo->save($tag);
            $repo->assignToLinkId($linkId, 'assigned');

            expect($repo->isAssignedToLinkId($linkId, 'assigned'))->toBeTrue();
        });

        test('returns false when tag is not assigned to link', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $linkId = Uuid::uuid4();
            insertTestLinkForTag($pdo, $linkId);
            $tag = TestEntityFactory::createTag(tagName: new TagName('unassigned'));
            $repo->save($tag);

            expect($repo->isAssignedToLinkId($linkId, 'unassigned'))->toBeFalse();
        });

        test('returns false when link does not exist', function () {
            $pdo = createTagDatabase();
            $repo = new PdoTagRepository($pdo);

            $tag = TestEntityFactory::createTag(tagName: new TagName('ghost'));
            $repo->save($tag);

            $nonExistentLinkId = Uuid::uuid4();

            expect($repo->isAssignedToLinkId($nonExistentLinkId, 'ghost'))->toBeFalse();
        });
    });

});
