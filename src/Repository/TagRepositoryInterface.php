<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Collection\TagCollection;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Exception\TagNotFoundException;
use jschreuder\BookmarkBureau\Exception\DuplicateTagException;
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
    public function findAll(): TagCollection;

    /**
     * Get all tags for a specific link
     */
    public function findTagsForLink(UuidInterface $linkId): TagCollection;

    /**
     * Get tags that match a search query (prefix search)
     */
    public function searchByName(string $query, int $limit = 20): TagCollection;

    /**
     * Save a new tag or update existing one
     * @throws DuplicateTagException when tag name already exists (on insert)
     */
    public function save(Tag $tag): void;

    /**
     * Delete a tag (cascades to link_tags)
     */
    public function delete(Tag $tag): void;

    /**
     * Assign a tag to a link
     * @throws TagNotFoundException when tag doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function assignToLink(UuidInterface $linkId, string $tagName): void;

    /**
     * Remove a tag from a link
     */
    public function removeFromLink(UuidInterface $linkId, string $tagName): void;

    /**
     * Check if a tag is assigned to a link
     */
    public function isAssignedToLink(UuidInterface $linkId, string $tagName): bool;
}
