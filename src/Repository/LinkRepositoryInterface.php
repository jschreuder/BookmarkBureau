<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Category;
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
     * Find links that match any number of tags (using AND condition)
     * @param string[] $tagNames
     */
    public function findByTags(array $tagNames): LinkCollection;

    /**
     * Get all links by category, ordered by sort_order
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function findByCategory(Category $category): LinkCollection;

    /**
     * Save a new link or update existing one
     */
    public function save(Link $link): void;

    /**
     * Delete a link (cascades to link_tags, category_links, favorites)
     */
    public function delete(Link $link): void;

    /**
     * Count total number of links
     */
    public function count(): int;
}
