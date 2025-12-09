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
use jschreuder\BookmarkBureau\Util\SqlExceptionHandler;

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
            $this->mapper->getDbFields(),
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
    public function listAll(): TagCollection
    {
        $sql = SqlBuilder::buildSelect(
            "tags",
            $this->mapper->getDbFields(),
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
     * Save a new tag
     * @throws DuplicateTagException when tag name already exists (on insert)
     */
    #[\Override]
    public function insert(Tag $tag): void
    {
        $row = $this->mapper->mapToRow($tag);
        try {
            $build = SqlBuilder::buildInsert("tags", $row);
            $this->pdo->prepare($build["sql"])->execute($build["params"]);
        } catch (\PDOException $e) {
            if (SqlExceptionHandler::isDuplicateEntry($e)) {
                throw DuplicateTagException::forName(
                    new TagName($tag->tagName->value),
                );
            }
            throw $e;
        }
    }

    /**
     * Update existing tag
     * @throws TagNotFoundException when tag doesn't exist
     */
    #[\Override]
    public function update(Tag $tag): void
    {
        $row = $this->mapper->mapToRow($tag);
        $build = SqlBuilder::buildUpdate("tags", $row, "tag_name");
        $statement = $this->pdo->prepare($build["sql"]);
        $statement->execute($build["params"]);

        if ($statement->rowCount() === 0) {
            throw TagNotFoundException::forName($tag->tagName);
        }
    }

    /**
     * Delete a tag (cascades to link_tags)
     */
    #[\Override]
    public function delete(Tag $tag): void
    {
        // Delete cascades are handled by database constraints
        $query = SqlBuilder::buildDelete("tags", [
            "tag_name" => $tag->tagName->value,
        ]);
        $this->pdo->prepare($query["sql"])->execute($query["params"]);
    }

    /**
     * Assign a tag to a link
     * @throws TagNotFoundException when tag doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function addTagToLinkId(UuidInterface $linkId, string $tagName): void
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
            $query = SqlBuilder::buildInsert("link_tags", [
                "link_id" => $linkId->getBytes(),
                "tag_name" => $tagName,
            ]);
            $this->pdo->prepare($query["sql"])->execute($query["params"]);
        } catch (\PDOException $e) {
            // Ignore duplicate entry errors (tag already assigned to link)
            if (!SqlExceptionHandler::isDuplicateEntry($e)) {
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
    public function removeTagFromLinkId(
        UuidInterface $linkId,
        string $tagName,
    ): void {
        try {
            $query = SqlBuilder::buildDelete("link_tags", [
                "link_id" => $linkId->getBytes(),
                "tag_name" => $tagName,
            ]);
            $this->pdo->prepare($query["sql"])->execute($query["params"]);
        } catch (\PDOException $e) {
            if (SqlExceptionHandler::isForeignKeyViolation($e)) {
                $field = SqlExceptionHandler::getForeignKeyField($e);
                if ($field === "link_id") {
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
    public function hasTagForLinkId(
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
