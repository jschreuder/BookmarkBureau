<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Composite\LinkCollection;
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

    /**
     * Get all links by category, ordered by sort_order
     * @throws CategoryNotFoundException when category doesn't exist
     */
    public function listForCategoryId(
        UuidInterface $categoryId,
    ): LinkCollection;

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
}
