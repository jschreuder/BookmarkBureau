<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface LinkRepositoryInterface
{
    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function findById(UuidInterface $linkId): Link;

    public function findAll(int $limit = 100, int $offset = 0): LinkCollection;

    /**
     * Search links using fulltext index on title and description
     */
    public function search(string $query, int $limit = 100): LinkCollection;

    /**
     * Find links by tag name
     */
    public function findByTag(string $tagName): LinkCollection;

    /**
     * Find links that match multiple tags (AND condition)
     * @param string[] $tagNames
     */
    public function findByTags(array $tagNames): LinkCollection;

    /**
     * Save a new link or update existing one
     */
    public function save(Link $link): void;

    /**
     * Delete a link (cascades to link_tags, category_links, favorites)
     */
    public function delete(Link $link): void;

    /**
     * Check if a URL already exists in the database
     */
    public function urlExists(string $url): bool;

    /**
     * Count total number of links
     */
    public function count(): int;
}
