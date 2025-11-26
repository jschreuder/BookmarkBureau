<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Composite\TagNameCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
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
     */
    public function findByTags(TagNameCollection $tagNames): LinkCollection;

    /**
     * Get all links by category, ordered by sort_order
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function findByCategoryId(UuidInterface $categoryId): LinkCollection;

    /**
     * Save a new link
     */
    public function insert(Link $link): void;

    /**
     * Update existing link
     */
    public function update(Link $link): void;

    /**
     * Delete a link (cascades to link_tags, category_links, favorites)
     */
    public function delete(Link $link): void;

    /**
     * Count total number of links
     */
    public function count(): int;
}
