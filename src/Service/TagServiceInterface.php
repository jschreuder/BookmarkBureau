<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Collection\TagCollection;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Exception\DuplicateTagException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Exception\TagNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface TagServiceInterface
{
    /**
     * Get all tags
     */
    public function listAllTags(): TagCollection;

    /**
     * Get tags for a specific link
     *
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function getTagsForLink(UuidInterface $linkId): TagCollection;

    /**
     * Create a new tag
     *
     * @throws DuplicateTagException when tag name already exists
     */
    public function createTag(string $tagName, ?string $color = null): Tag;

    /**
     * Update an existing tag's color
     *
     * @throws TagNotFoundException when tag doesn't exist
     */
    public function updateTag(string $tagName, ?string $color = null): Tag;

    /**
     * Delete a tag (cascades to link associations)
     *
     * @throws TagNotFoundException when tag doesn't exist
     */
    public function deleteTag(string $tagName): void;

    /**
     * Assign a tag to a link (creates tag if it doesn't exist)
     *
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function assignTagToLink(UuidInterface $linkId, string $tagName, ?string $color = null): void;

    /**
     * Remove a tag from a link
     */
    public function removeTagFromLink(UuidInterface $linkId, string $tagName): void;

    /**
     * Search tags by name prefix
     */
    public function searchTags(string $query, int $limit = 20): TagCollection;
}
