<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Collection\TagCollection;
use jschreuder\BookmarkBureau\Collection\TagNameCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\TagEntityMapper;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Util\SqlBuilder;

final readonly class PdoLinkRepository implements LinkRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly LinkEntityMapper $mapper,
        private readonly TagEntityMapper $tagMapper,
    ) {}

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function findById(UuidInterface $linkId): Link
    {
        $fields = SqlBuilder::selectFieldsFromMapper($this->mapper, "l");
        $tagFields = SqlBuilder::selectFieldsFromMapper($this->tagMapper, "t");
        $statement = $this->pdo->prepare(
            "SELECT {$fields}, {$tagFields} FROM links l
             LEFT JOIN link_tags lt ON l.link_id = lt.link_id
             LEFT JOIN tags t ON lt.tag_name = t.tag_name
             WHERE l.link_id = :link_id",
        );
        $statement->execute([":link_id" => $linkId->getBytes()]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            throw LinkNotFoundException::forId($linkId);
        }

        return $this->buildLinkFromRows($rows)[0];
    }

    #[\Override]
    public function findAll(int $limit = 100, int $offset = 0): LinkCollection
    {
        $fields = SqlBuilder::selectFieldsFromMapper($this->mapper, "l");
        $tagFields = SqlBuilder::selectFieldsFromMapper($this->tagMapper, "t");
        $statement = $this->pdo->prepare(
            "SELECT {$fields}, {$tagFields} FROM links l
             LEFT JOIN link_tags lt ON l.link_id = lt.link_id
             LEFT JOIN tags t ON lt.tag_name = t.tag_name
             ORDER BY l.created_at DESC
             LIMIT ? OFFSET ?",
        );
        $statement->execute([$limit, $offset]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $links = $this->buildLinkFromRows($rows);

        return new LinkCollection(...$links);
    }

    /**
     * Search links using fulltext index on title and description
     * Uses LIKE queries for cross-database compatibility (MySQL and SQLite)
     */
    #[\Override]
    public function search(string $query, int $limit = 100): LinkCollection
    {
        $searchTerm = "%{$query}%";
        $fields = SqlBuilder::selectFieldsFromMapper($this->mapper, "l");
        $tagFields = SqlBuilder::selectFieldsFromMapper($this->tagMapper, "t");
        $statement = $this->pdo->prepare(
            "SELECT {$fields}, {$tagFields} FROM links l
             LEFT JOIN link_tags lt ON l.link_id = lt.link_id
             LEFT JOIN tags t ON lt.tag_name = t.tag_name
             WHERE l.title LIKE ? OR l.description LIKE ?
             ORDER BY l.created_at DESC
             LIMIT ?",
        );
        $statement->execute([$searchTerm, $searchTerm, $limit]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $links = $this->buildLinkFromRows($rows);

        return new LinkCollection(...$links);
    }

    /**
     * Find links that match any number of tags (using AND condition)
     * Uses INTERSECT queries for cross-database compatibility (MySQL 8.0+ and SQLite)
     */
    #[\Override]
    public function findByTags(TagNameCollection $tagNames): LinkCollection
    {
        if ($tagNames->isEmpty()) {
            return new LinkCollection();
        }

        // Build INTERSECT query that works in both MySQL 8.0+ and SQLite
        // For each tag, we select link_ids that have that tag,
        // then intersect all results to get links with ALL tags
        $intersectQueries = array_map(
            fn() => "SELECT link_id FROM link_tags WHERE tag_name = ?",
            $tagNames->toArray(),
        );
        $fields = SqlBuilder::selectFieldsFromMapper($this->mapper, "l");
        $tagFields = SqlBuilder::selectFieldsFromMapper($this->tagMapper, "t");
        $query =
            "SELECT {$fields}, {$tagFields}
             FROM links l
             LEFT JOIN link_tags lt ON l.link_id = lt.link_id
             LEFT JOIN tags t ON lt.tag_name = t.tag_name
             WHERE l.link_id IN (" .
            implode(" INTERSECT ", $intersectQueries) .
            ") ORDER BY l.created_at DESC";

        $statement = $this->pdo->prepare($query);
        $statement->execute(
            array_map(
                fn(TagName $value) => $value->value,
                $tagNames->toArray(),
            ),
        );

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $links = $this->buildLinkFromRows($rows);

        return new LinkCollection(...$links);
    }

    /**
     * Get all links by category, ordered by sort_order
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function findByCategoryId(UuidInterface $categoryId): LinkCollection
    {
        $fields = SqlBuilder::selectFieldsFromMapper($this->mapper, "l");
        $tagFields = SqlBuilder::selectFieldsFromMapper($this->tagMapper, "t");
        $statement = $this->pdo->prepare(
            "SELECT {$fields}, {$tagFields} FROM links l
             INNER JOIN category_links cl ON l.link_id = cl.link_id
             LEFT JOIN link_tags lt ON l.link_id = lt.link_id
             LEFT JOIN tags t ON lt.tag_name = t.tag_name
             WHERE cl.category_id = :category_id
             ORDER BY cl.sort_order ASC",
        );
        $statement->execute([":category_id" => $categoryId->getBytes()]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            // Verify the category exists
            $categoryCheck = $this->pdo->prepare(
                "SELECT 1 FROM categories WHERE category_id = :category_id LIMIT 1",
            );
            $categoryCheck->execute([
                ":category_id" => $categoryId->getBytes(),
            ]);
            if ($categoryCheck->fetch() === false) {
                throw CategoryNotFoundException::forId($categoryId);
            }
        }

        $links = $this->buildLinkFromRows($rows);

        return new LinkCollection(...$links);
    }

    /**
     * Save a new link or update existing one
     */
    #[\Override]
    public function save(Link $link): void
    {
        $row = $this->mapper->mapToRow($link);
        $linkIdBytes = $row["link_id"];

        // Check if link exists
        $check = $this->pdo->prepare(
            "SELECT 1 FROM links WHERE link_id = :link_id LIMIT 1",
        );
        $check->execute([":link_id" => $linkIdBytes]);

        if ($check->fetch() === false) {
            // Insert new link
            $build = SqlBuilder::buildInsert("links", $row);
            $this->pdo->prepare($build["sql"])->execute($build["params"]);
        } else {
            // Update existing link
            $build = SqlBuilder::buildUpdate("links", $row, "link_id");
            $this->pdo->prepare($build["sql"])->execute($build["params"]);
        }
    }

    /**
     * Delete a link (cascades to link_tags, category_links, favorites)
     */
    #[\Override]
    public function delete(Link $link): void
    {
        // Delete cascades are handled by database constraints
        $statement = $this->pdo->prepare(
            "DELETE FROM links WHERE link_id = :link_id",
        );
        $statement->execute([":link_id" => $link->linkId->getBytes()]);
    }

    /**
     * Count total number of links
     */
    #[\Override]
    public function count(): int
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) as count FROM links");
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (int) $result["count"];
    }

    /**
     * Build Link entities from query results with tags
     * Handles multiple rows per link (one row per tag) by:
     * 1. Deduplicating link rows
     * 2. Collecting tags for each link into a TagCollection
     * 3. Mapping to Link entities with their tags
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, Link>
     */
    private function buildLinkFromRows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        // Group rows by link_id to handle multiple tags per link
        $groupedByLink = [];
        foreach ($rows as $row) {
            $linkId = $row["link_id"];
            if (!isset($groupedByLink[$linkId])) {
                $groupedByLink[$linkId] = [
                    "linkRow" => $row,
                    "tagRows" => [],
                ];
            }
            // Only collect tag data if a tag was actually joined
            if ($row["tag_name"] !== null) {
                $groupedByLink[$linkId]["tagRows"][] = $row;
            }
        }

        // Build Link entities with their tags
        $links = [];
        $tagObjects = [];
        foreach ($groupedByLink as $group) {
            $linkRow = $group["linkRow"];
            $tagRows = $group["tagRows"];

            // Map tags to Tag entities
            $tags = [];
            foreach ($tagRows as $tagRow) {
                $tagName = $tagRow["tag_name"];

                if (!\array_key_exists($tagName, $tagObjects)) {
                    $tagObject = $this->tagMapper->mapToEntity($tagRow);
                    $tagObjects[$tagName] = $tagObject;
                } else {
                    $tagObject = $tagObjects[$tagName];
                }

                $tags[] = $tagObject;
            }

            // Map Link entity with its tags
            $linkRow["tags"] = new TagCollection(...$tags);
            $link = $this->mapper->mapToEntity($linkRow);
            $links[] = $link;
        }

        return $links;
    }
}
