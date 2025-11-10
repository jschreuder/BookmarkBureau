<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Collection\TagNameCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Util\SqlBuilder;
use Ramsey\Uuid\Uuid;

final readonly class PdoLinkRepository implements LinkRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly LinkEntityMapper $mapper,
    ) {}

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function findById(UuidInterface $linkId): Link
    {
        $sql = SqlBuilder::buildSelect(
            "links",
            $this->mapper->getFields(),
            "link_id = :link_id LIMIT 1",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":link_id" => $linkId->getBytes()]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw LinkNotFoundException::forId($linkId);
        }

        return $this->mapper->mapToEntity($row);
    }

    #[\Override]
    public function findAll(int $limit = 100, int $offset = 0): LinkCollection
    {
        $sql = SqlBuilder::buildSelect(
            "links",
            $this->mapper->getFields(),
            null,
            "created_at DESC",
        );
        $sql .= " LIMIT :limit OFFSET :offset";
        $statement = $this->pdo->prepare($sql);
        $statement->bindValue(":limit", $limit, PDO::PARAM_INT);
        $statement->bindValue(":offset", $offset, PDO::PARAM_INT);
        $statement->execute();

        $links = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $links[] = $this->mapper->mapToEntity($row);
        }

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

        $sql = SqlBuilder::buildSelect(
            "links",
            $this->mapper->getFields(),
            "title LIKE ? OR description LIKE ?",
            "created_at DESC",
        );
        $sql .= " LIMIT ?";
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$searchTerm, $searchTerm, $limit]);

        $links = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $links[] = $this->mapper->mapToEntity($row);
        }

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
        $fields = implode(
            ", ",
            array_map(
                fn(string $field) => "l." . $field,
                $this->mapper->getFields(),
            ),
        );
        $query =
            "SELECT " .
            $fields .
            " FROM links l WHERE l.link_id IN (" .
            implode(" INTERSECT ", $intersectQueries) .
            ") ORDER BY l.created_at DESC";

        $statement = $this->pdo->prepare($query);
        $statement->execute(
            array_map(
                fn(TagName $value) => $value->value,
                $tagNames->toArray(),
            ),
        );

        $links = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $links[] = $this->mapper->mapToEntity($row);
        }

        return new LinkCollection(...$links);
    }

    /**
     * Get all links by category, ordered by sort_order
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function findByCategoryId(UuidInterface $categoryId): LinkCollection
    {
        $fields = implode(
            ", ",
            array_map(
                fn(string $field) => "l." . $field,
                $this->mapper->getFields(),
            ),
        );
        $statement = $this->pdo->prepare(
            "SELECT " .
                $fields .
                ' FROM links l
             INNER JOIN category_links cl ON l.link_id = cl.link_id
             WHERE cl.category_id = :category_id
             ORDER BY cl.sort_order ASC',
        );
        $statement->execute([":category_id" => $categoryId->getBytes()]);

        if ($statement->rowCount() === 0) {
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

        $links = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $links[] = $this->mapper->mapToEntity($row);
        }

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
}
