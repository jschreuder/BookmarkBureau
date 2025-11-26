<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Entity\Value\TagName;
use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Mapper\TagEntityMapper;
use jschreuder\BookmarkBureau\Exception\TagNotFoundException;
use jschreuder\BookmarkBureau\Exception\DuplicateTagException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Util\SqlBuilder;

final readonly class PdoTagRepository implements TagRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly TagEntityMapper $mapper,
    ) {}

    /**
     * @throws TagNotFoundException when tag doesn't exist
     */
    #[\Override]
    public function findByName(string $tagName): Tag
    {
        $sql = SqlBuilder::buildSelect(
            "tags",
            $this->mapper->getFields(),
            "tag_name = :tag_name LIMIT 1",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":tag_name" => $tagName]);

        /** @var array{tag_name: string, color: string|null}|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw TagNotFoundException::forName(new TagName($tagName));
        }

        return $this->mapper->mapToEntity($row);
    }

    /**
     * Get all tags ordered alphabetically
     */
    #[\Override]
    public function findAll(): TagCollection
    {
        $sql = SqlBuilder::buildSelect(
            "tags",
            $this->mapper->getFields(),
            null,
            "tag_name ASC",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute();

        $tags = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            /** @var array{tag_name: string, color: string|null} $row */
            $tags[] = $this->mapper->mapToEntity($row);
        }

        return new TagCollection(...$tags);
    }

    /**
     * Get all tags for a specific link
     * @throws LinkNotFoundException when link doesn't exist (FK violation)
     */
    #[\Override]
    public function findTagsForLinkId(UuidInterface $link): TagCollection
    {
        // Verify that the link exists
        $linkCheck = $this->pdo->prepare(
            "SELECT 1 FROM links WHERE link_id = :link_id LIMIT 1",
        );
        $linkCheck->execute([":link_id" => $link->getBytes()]);

        if ($linkCheck->fetch() === false) {
            throw LinkNotFoundException::forId($link);
        }

        $tagFields = SqlBuilder::selectFieldsFromMapper($this->mapper, "t");
        $statement = $this->pdo->prepare(
            "SELECT {$tagFields}
             FROM tags t
             INNER JOIN link_tags lt ON t.tag_name = lt.tag_name
             WHERE lt.link_id = :link_id
             ORDER BY t.tag_name ASC",
        );
        $statement->execute([":link_id" => $link->getBytes()]);

        $tags = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            /** @var array{tag_name: string, color: string|null} $row */
            $tags[] = $this->mapper->mapToEntity($row);
        }

        return new TagCollection(...$tags);
    }

    /**
     * Get tags that match a search query (prefix search)
     */
    #[\Override]
    public function searchByName(string $query, int $limit = 20): TagCollection
    {
        $searchTerm = "{$query}%";

        $sql = SqlBuilder::buildSelect(
            "tags",
            $this->mapper->getFields(),
            "tag_name LIKE ?",
            "tag_name ASC",
            $limit,
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$searchTerm]);

        $tags = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            /** @var array{tag_name: string, color: string|null} $row */
            $tags[] = $this->mapper->mapToEntity($row);
        }

        return new TagCollection(...$tags);
    }

    /**
     * Save a new tag or update existing one
     * @throws DuplicateTagException when tag name already exists (on insert)
     */
    #[\Override]
    public function save(Tag $tag): void
    {
        $row = $this->mapper->mapToRow($tag);
        $tagNameValue = $row["tag_name"];

        // Check if tag exists
        $check = $this->pdo->prepare(
            "SELECT 1 FROM tags WHERE tag_name = :tag_name LIMIT 1",
        );
        $check->execute([":tag_name" => $tagNameValue]);

        if ($check->fetch() === false) {
            // Insert new tag
            try {
                $build = SqlBuilder::buildInsert("tags", $row);
                $this->pdo->prepare($build["sql"])->execute($build["params"]);
            } catch (\PDOException $e) {
                if (
                    str_contains($e->getMessage(), "Duplicate entry") ||
                    str_contains($e->getMessage(), "UNIQUE constraint failed")
                ) {
                    throw DuplicateTagException::forName(
                        new TagName($tagNameValue),
                    );
                }
                throw $e;
            }
        } else {
            // Update existing tag
            $build = SqlBuilder::buildUpdate("tags", $row, "tag_name");
            $this->pdo->prepare($build["sql"])->execute($build["params"]);
        }
    }

    /**
     * Delete a tag (cascades to link_tags)
     */
    #[\Override]
    public function delete(Tag $tag): void
    {
        // Delete cascades are handled by database constraints
        $statement = $this->pdo->prepare(
            "DELETE FROM tags WHERE tag_name = :tag_name",
        );
        $statement->execute([":tag_name" => $tag->tagName->value]);
    }

    /**
     * Assign a tag to a link
     * @throws TagNotFoundException when tag doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function assignToLinkId(UuidInterface $linkId, string $tagName): void
    {
        // Verify tag exists
        $tagCheck = $this->pdo->prepare(
            "SELECT 1 FROM tags WHERE tag_name = :tag_name LIMIT 1",
        );
        $tagCheck->execute([":tag_name" => $tagName]);
        if ($tagCheck->fetch() === false) {
            throw TagNotFoundException::forName(new TagName($tagName));
        }

        // Verify link exists
        $linkCheck = $this->pdo->prepare(
            "SELECT 1 FROM links WHERE link_id = :link_id LIMIT 1",
        );
        $linkCheck->execute([":link_id" => $linkId->getBytes()]);
        if ($linkCheck->fetch() === false) {
            throw LinkNotFoundException::forId($linkId);
        }

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO link_tags (link_id, tag_name)
                 VALUES (:link_id, :tag_name)',
            );
            $statement->execute([
                ":link_id" => $linkId->getBytes(),
                ":tag_name" => $tagName,
            ]);
        } catch (\PDOException $e) {
            // Ignore duplicate entry errors (tag already assigned to link)
            if (
                !str_contains($e->getMessage(), "Duplicate entry") &&
                !str_contains($e->getMessage(), "UNIQUE constraint failed")
            ) {
                throw $e;
            }
        }
    }

    /**
     * Remove a tag from a link
     * @throws LinkNotFoundException when link doesn't exist (FK violation)
     * @throws TagNotFoundException when tag doesn't exist (FK violation)
     */
    #[\Override]
    public function removeFromLinkId(
        UuidInterface $linkId,
        string $tagName,
    ): void {
        try {
            $statement = $this->pdo->prepare(
                "DELETE FROM link_tags WHERE link_id = :link_id AND tag_name = :tag_name",
            );
            $statement->execute([
                ":link_id" => $linkId->getBytes(),
                ":tag_name" => $tagName,
            ]);
        } catch (\PDOException $e) {
            if (
                str_contains(
                    $e->getMessage(),
                    "FOREIGN KEY constraint failed",
                ) ||
                str_contains($e->getMessage(), "foreign key constraint fails")
            ) {
                if (str_contains($e->getMessage(), "link_id")) {
                    throw LinkNotFoundException::forId($linkId);
                } else {
                    throw TagNotFoundException::forName(new TagName($tagName));
                }
            }
            throw $e;
        }
    }

    /**
     * Check if a tag is assigned to a link
     */
    #[\Override]
    public function isAssignedToLinkId(
        UuidInterface $linkId,
        string $tagName,
    ): bool {
        $statement = $this->pdo->prepare(
            "SELECT 1 FROM link_tags WHERE link_id = :link_id AND tag_name = :tag_name LIMIT 1",
        );
        $statement->execute([
            ":link_id" => $linkId->getBytes(),
            ":tag_name" => $tagName,
        ]);

        return $statement->fetch() !== false;
    }
}
