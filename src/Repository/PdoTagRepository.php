<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Collection\TagCollection;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Exception\TagNotFoundException;
use jschreuder\BookmarkBureau\Exception\DuplicateTagException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;

final readonly class PdoTagRepository implements TagRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    /**
     * @throws TagNotFoundException when tag doesn't exist
     */
    public function findByName(string $tagName): Tag
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM tags WHERE tag_name = :tag_name LIMIT 1'
        );
        $statement->execute([':tag_name' => $tagName]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new TagNotFoundException('Tag not found: ' . $tagName);
        }

        return $this->mapRowToTag($row);
    }

    /**
     * Get all tags ordered alphabetically
     */
    public function findAll(): TagCollection
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM tags ORDER BY tag_name ASC'
        );
        $statement->execute();

        $tags = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $tags[] = $this->mapRowToTag($row);
        }

        return new TagCollection(...$tags);
    }

    /**
     * Get all tags for a specific link
     * @throws LinkNotFoundException when link doesn't exist (FK violation)
     */
    public function findTagsForLinkId(UuidInterface $link): TagCollection
    {
        // Verify that the link exists
        $linkCheck = $this->pdo->prepare('SELECT 1 FROM links WHERE link_id = :link_id LIMIT 1');
        $linkCheck->execute([':link_id' => $link->getBytes()]);

        if ($linkCheck->fetch() === false) {
            throw new LinkNotFoundException('Link not found: ' . $link->toString());
        }

        $statement = $this->pdo->prepare(
            'SELECT t.* FROM tags t
             INNER JOIN link_tags lt ON t.tag_name = lt.tag_name
             WHERE lt.link_id = :link_id
             ORDER BY t.tag_name ASC'
        );
        $statement->execute([':link_id' => $link->getBytes()]);

        $tags = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $tags[] = $this->mapRowToTag($row);
        }

        return new TagCollection(...$tags);
    }

    /**
     * Get tags that match a search query (prefix search)
     */
    public function searchByName(string $query, int $limit = 20): TagCollection
    {
        $searchTerm = $query . '%';

        $statement = $this->pdo->prepare(
            'SELECT * FROM tags
             WHERE tag_name LIKE ?
             ORDER BY tag_name ASC
             LIMIT ?'
        );
        $statement->execute([$searchTerm, $limit]);

        $tags = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $tags[] = $this->mapRowToTag($row);
        }

        return new TagCollection(...$tags);
    }

    /**
     * Save a new tag or update existing one
     * @throws DuplicateTagException when tag name already exists (on insert)
     */
    public function save(Tag $tag): void
    {
        $tagNameValue = $tag->tagName->value;

        // Check if tag exists
        $check = $this->pdo->prepare('SELECT 1 FROM tags WHERE tag_name = :tag_name LIMIT 1');
        $check->execute([':tag_name' => $tagNameValue]);

        if ($check->fetch() === false) {
            // Insert new tag
            try {
                $statement = $this->pdo->prepare(
                    'INSERT INTO tags (tag_name, color)
                     VALUES (:tag_name, :color)'
                );
                $statement->execute([
                    ':tag_name' => $tagNameValue,
                    ':color' => $tag->color ? $tag->color->value : null,
                ]);
            } catch (\PDOException $e) {
                if (str_contains($e->getMessage(), 'Duplicate entry') ||
                    str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                    throw new DuplicateTagException('Tag already exists: ' . $tagNameValue);
                }
                throw $e;
            }
        } else {
            // Update existing tag
            $statement = $this->pdo->prepare(
                'UPDATE tags SET color = :color WHERE tag_name = :tag_name'
            );
            $statement->execute([
                ':tag_name' => $tagNameValue,
                ':color' => $tag->color ? $tag->color->value : null,
            ]);
        }
    }

    /**
     * Delete a tag (cascades to link_tags)
     */
    public function delete(Tag $tag): void
    {
        // Delete cascades are handled by database constraints
        $statement = $this->pdo->prepare('DELETE FROM tags WHERE tag_name = :tag_name');
        $statement->execute([':tag_name' => $tag->tagName->value]);
    }

    /**
     * Assign a tag to a link
     * @throws TagNotFoundException when tag doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function assignToLinkId(UuidInterface $linkId, string $tagName): void
    {
        // Verify tag exists
        $tagCheck = $this->pdo->prepare('SELECT 1 FROM tags WHERE tag_name = :tag_name LIMIT 1');
        $tagCheck->execute([':tag_name' => $tagName]);
        if ($tagCheck->fetch() === false) {
            throw new TagNotFoundException('Tag not found: ' . $tagName);
        }

        // Verify link exists
        $linkCheck = $this->pdo->prepare('SELECT 1 FROM links WHERE link_id = :link_id LIMIT 1');
        $linkCheck->execute([':link_id' => $linkId->getBytes()]);
        if ($linkCheck->fetch() === false) {
            throw new LinkNotFoundException('Link not found: ' . $linkId->toString());
        }

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO link_tags (link_id, tag_name)
                 VALUES (:link_id, :tag_name)'
            );
            $statement->execute([
                ':link_id' => $linkId->getBytes(),
                ':tag_name' => $tagName,
            ]);
        } catch (\PDOException $e) {
            // Ignore duplicate entry errors (tag already assigned to link)
            if (!str_contains($e->getMessage(), 'Duplicate entry') &&
                !str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                throw $e;
            }
        }
    }

    /**
     * Remove a tag from a link
     * @throws LinkNotFoundException when link doesn't exist (FK violation)
     * @throws TagNotFoundException when tag doesn't exist (FK violation)
     */
    public function removeFromLinkId(UuidInterface $linkId, string $tagName): void
    {
        try {
            $statement = $this->pdo->prepare(
                'DELETE FROM link_tags WHERE link_id = :link_id AND tag_name = :tag_name'
            );
            $statement->execute([
                ':link_id' => $linkId->getBytes(),
                ':tag_name' => $tagName,
            ]);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'FOREIGN KEY constraint failed') ||
                str_contains($e->getMessage(), 'foreign key constraint fails')) {
                if (str_contains($e->getMessage(), 'link_id')) {
                    throw new LinkNotFoundException('Link not found: ' . $linkId->toString());
                } else {
                    throw new TagNotFoundException('Tag not found: ' . $tagName);
                }
            }
            throw $e;
        }
    }

    /**
     * Check if a tag is assigned to a link
     */
    public function isAssignedToLinkId(UuidInterface $linkId, string $tagName): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM link_tags WHERE link_id = :link_id AND tag_name = :tag_name LIMIT 1'
        );
        $statement->execute([
            ':link_id' => $linkId->getBytes(),
            ':tag_name' => $tagName,
        ]);

        return $statement->fetch() !== false;
    }

    /**
     * Map a database row to a Tag entity
     */
    private function mapRowToTag(array $row): Tag
    {
        return new Tag(
            tagName: new TagName($row['tag_name']),
            color: $row['color'] !== null ? new HexColor($row['color']) : null,
        );
    }
}
