<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\TagEntityMapper;
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

        /** @var array<int, array{link_id: string, title: string, url: string, icon: string|null, description: string, sort_order: int, created_at: string, updated_at: string, tag_name: string|null, color: string|null}> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            throw LinkNotFoundException::forId($linkId);
        }

        return $this->buildLinkFromRows($rows)[0];
    }

    /**
     * Get all links by category, ordered by sort_order
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function listForCategoryId(UuidInterface $categoryId): LinkCollection
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

        /** @var array<int, array{link_id: string, title: string, url: string, icon: string|null, description: string, sort_order: int, created_at: string, updated_at: string, tag_name: string|null, color: string|null}> $rows */
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
     * Save a new link
     */
    #[\Override]
    public function insert(Link $link): void
    {
        $row = $this->mapper->mapToRow($link);
        $build = SqlBuilder::buildInsert("links", $row);
        $this->pdo->prepare($build["sql"])->execute($build["params"]);
    }

    /**
     * Update existing link
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function update(Link $link): void
    {
        $row = $this->mapper->mapToRow($link);
        $build = SqlBuilder::buildUpdate("links", $row, "link_id");
        $statement = $this->pdo->prepare($build["sql"]);
        $statement->execute($build["params"]);

        if ($statement->rowCount() === 0) {
            throw LinkNotFoundException::forId($link->linkId);
        }
    }

    /**
     * Delete a link (cascades to link_tags, category_links, favorites)
     */
    #[\Override]
    public function delete(Link $link): void
    {
        // Delete cascades are handled by database constraints
        $query = SqlBuilder::buildDelete("links", [
            "link_id" => $link->linkId->getBytes(),
        ]);
        $this->pdo->prepare($query["sql"])->execute($query["params"]);
    }

    /**
     * Build Link entities from query results with tags
     * Handles multiple rows per link (one row per tag) by:
     * 1. Deduplicating link rows
     * 2. Collecting tags for each link into a TagCollection
     * 3. Mapping to Link entities with their tags
     *
     * @param array<int, array{link_id: string, title: string, url: string, icon: string|null, description: string, sort_order: int, created_at: string, updated_at: string, tag_name: string|null, color: string|null}> $rows
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
                    $tagObject = $this->tagMapper->mapToEntity([
                        "tag_name" => $tagName,
                        "color" => $tagRow["color"],
                    ]);
                    $tagObjects[$tagName] = $tagObject;
                } else {
                    $tagObject = $tagObjects[$tagName];
                }

                $tags[] = $tagObject;
            }

            // Map Link entity with its tags
            $linkRow["tags"] = new TagCollection(...$tags);
            unset($linkRow["tag_name"], $linkRow["color"]);
            $link = $this->mapper->mapToEntity($linkRow);
            $links[] = $link;
        }

        return $links;
    }
}
