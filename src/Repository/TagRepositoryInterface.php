<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Exception\TagNotFoundException;
use jschreuder\BookmarkBureau\Exception\DuplicateTagException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface TagRepositoryInterface
{
    /**
     * @throws TagNotFoundException when tag doesn't exist
     */
    public function findByName(string $tagName): Tag;

    /**
     * Get all tags ordered alphabetically
     */
    public function listAll(): TagCollection;

    /**
     * Save a new tag
     * @throws DuplicateTagException when tag name already exists (on insert)
     */
    public function insert(Tag $tag): void;

    /**
     * Update existing tag
     */
    public function update(Tag $tag): void;

    /**
     * Delete a tag (cascades to link_tags)
     */
    public function delete(Tag $tag): void;

    /**
     * Assign a tag to a link
     * @throws TagNotFoundException when tag doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function addTagToLinkId(
        UuidInterface $linkId,
        string $tagName,
    ): void;

    /**
     * Remove a tag from a link
     * @throws LinkNotFoundException when link doesn't exist (FK violation)
     * @throws TagNotFoundException when tag doesn't exist (FK violation)
     */
    public function removeTagFromLinkId(
        UuidInterface $linkId,
        string $tagName,
    ): void;

    /**
     * Check if a tag is assigned to a link
     */
    public function hasTagForLinkId(
        UuidInterface $linkId,
        string $tagName,
    ): bool;
}
