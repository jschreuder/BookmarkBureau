<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Collection\TagNameCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;

final readonly class PdoLinkRepository implements LinkRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function findById(UuidInterface $linkId): Link
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM links WHERE link_id = :link_id LIMIT 1'
        );
        $statement->execute([':link_id' => $linkId->getBytes()]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new LinkNotFoundException('Link not found: ' . $linkId->toString());
        }

        return $this->mapRowToLink($row);
    }

    public function findAll(int $limit = 100, int $offset = 0): LinkCollection
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM links ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        $links = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $links[] = $this->mapRowToLink($row);
        }

        return new LinkCollection(...$links);
    }

    /**
     * Search links using fulltext index on title and description
     * Uses LIKE queries for cross-database compatibility (MySQL and SQLite)
     */
    public function search(string $query, int $limit = 100): LinkCollection
    {
        $searchTerm = '%' . $query . '%';

        $statement = $this->pdo->prepare(
            'SELECT * FROM links
             WHERE title LIKE ? OR description LIKE ?
             ORDER BY created_at DESC
             LIMIT ?'
        );
        $statement->execute([$searchTerm, $searchTerm, $limit]);

        $links = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $links[] = $this->mapRowToLink($row);
        }

        return new LinkCollection(...$links);
    }

    /**
     * Find links that match any number of tags (using AND condition)
     * Uses INTERSECT queries for cross-database compatibility (MySQL 8.0+ and SQLite)
     */
    public function findByTags(TagNameCollection $tagNames): LinkCollection
    {
        if ($tagNames->isEmpty()) {
            return new LinkCollection();
        }

        // Build INTERSECT query that works in both MySQL 8.0+ and SQLite
        // For each tag, we select link_ids that have that tag,
        // then intersect all results to get links with ALL tags
        $intersectQueries = array_map(fn() => 'SELECT link_id FROM link_tags WHERE tag_name = ?', $tagNames->toArray());
        $query = 'SELECT l.* FROM links l WHERE l.link_id IN (' .
                 implode(' INTERSECT ', $intersectQueries) .
                 ') ORDER BY l.created_at DESC';

        $statement = $this->pdo->prepare($query);
        $statement->execute(array_map(fn (TagName $value) => $value->value, $tagNames->toArray()));

        $links = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $links[] = $this->mapRowToLink($row);
        }

        return new LinkCollection(...$links);
    }

    /**
     * Get all links by category, ordered by sort_order
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function findByCategoryId(UuidInterface $categoryId): LinkCollection
    {
        $statement = $this->pdo->prepare(
            'SELECT l.* FROM links l
             INNER JOIN category_links cl ON l.link_id = cl.link_id
             WHERE cl.category_id = :category_id
             ORDER BY cl.sort_order ASC'
        );
        $statement->execute([':category_id' => $categoryId->getBytes()]);

        if ($statement->rowCount() === 0) {
            // Verify the category exists
            $categoryCheck = $this->pdo->prepare(
                'SELECT 1 FROM categories WHERE category_id = :category_id LIMIT 1'
            );
            $categoryCheck->execute([':category_id' => $categoryId->getBytes()]);
            if ($categoryCheck->fetch() === false) {
                throw new CategoryNotFoundException('Category not found: ' . $categoryId->toString());
            }
        }

        $links = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $links[] = $this->mapRowToLink($row);
        }

        return new LinkCollection(...$links);
    }

    /**
     * Save a new link or update existing one
     */
    public function save(Link $link): void
    {
        $linkIdBytes = $link->linkId->getBytes();

        // Check if link exists
        $check = $this->pdo->prepare('SELECT 1 FROM links WHERE link_id = :link_id LIMIT 1');
        $check->execute([':link_id' => $linkIdBytes]);

        if ($check->fetch() === false) {
            // Insert new link
            $statement = $this->pdo->prepare(
                'INSERT INTO links (link_id, url, title, description, icon, created_at, updated_at)
                 VALUES (:link_id, :url, :title, :description, :icon, :created_at, :updated_at)'
            );
            $statement->execute([
                ':link_id' => $linkIdBytes,
                ':url' => (string) $link->url,
                ':title' => (string) $link->title,
                ':description' => $link->description,
                ':icon' => $link->icon ? (string) $link->icon : null,
                ':created_at' => $link->createdAt->format('Y-m-d H:i:s'),
                ':updated_at' => $link->updatedAt->format('Y-m-d H:i:s'),
            ]);
        } else {
            // Update existing link
            $statement = $this->pdo->prepare(
                'UPDATE links
                 SET url = :url, title = :title, description = :description,
                     icon = :icon, updated_at = :updated_at
                 WHERE link_id = :link_id'
            );
            $statement->execute([
                ':link_id' => $linkIdBytes,
                ':url' => (string) $link->url,
                ':title' => (string) $link->title,
                ':description' => $link->description,
                ':icon' => $link->icon ? (string) $link->icon : null,
                ':updated_at' => $link->updatedAt->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Delete a link (cascades to link_tags, category_links, favorites)
     */
    public function delete(Link $link): void
    {
        // Delete cascades are handled by database constraints
        $statement = $this->pdo->prepare('DELETE FROM links WHERE link_id = :link_id');
        $statement->execute([':link_id' => $link->linkId->getBytes()]);
    }

    /**
     * Count total number of links
     */
    public function count(): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) as count FROM links');
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    /**
     * Map a database row to a Link entity
     */
    private function mapRowToLink(array $row): Link
    {
        return new Link(
            linkId: \Ramsey\Uuid\Uuid::fromBytes($row['link_id']),
            url: new Url($row['url']),
            title: new Title($row['title']),
            description: $row['description'],
            icon: $row['icon'] !== null ? new Icon($row['icon']) : null,
            createdAt: new DateTimeImmutable($row['created_at']),
            updatedAt: new DateTimeImmutable($row['updated_at']),
        );
    }
}
