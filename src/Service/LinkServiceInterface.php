<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface LinkServiceInterface
{
    /**
     * Get a link by ID
     *
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function getLink(UuidInterface $linkId): Link;

    /**
     * Create a new link
     */
    public function createLink(
        string $url,
        string $title,
        string $description = "",
        ?string $icon = null,
    ): Link;

    /**
     * Update an existing link
     *
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function updateLink(
        UuidInterface $linkId,
        string $url,
        string $title,
        string $description = "",
        ?string $icon = null,
    ): Link;

    /**
     * Delete a link (cascades to tags, category links, and favorites)
     *
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function deleteLink(UuidInterface $linkId): void;
}
