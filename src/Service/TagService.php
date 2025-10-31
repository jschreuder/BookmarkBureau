<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Collection\TagCollection;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Exception\DuplicateTagException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Exception\TagNotFoundException;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\TagRepositoryInterface;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;
use Ramsey\Uuid\UuidInterface;

final class TagService implements TagServiceInterface
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {
    }

    #[\Override]
    public function listAllTags(): TagCollection
    {
        return $this->tagRepository->findAll();
    }

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function getTagsForLink(UuidInterface $linkId): TagCollection
    {
        // Verify link exists before fetching tags
        $this->linkRepository->findById($linkId);

        return $this->tagRepository->findTagsForLinkId($linkId);
    }

    /**
     * @throws DuplicateTagException when tag name already exists
     */
    #[\Override]
    public function createTag(string $tagName, ?string $color = null): Tag
    {
        return $this->unitOfWork->transactional(function () use ($tagName, $color): Tag {
            $tag = new Tag(
                new TagName($tagName),
                $color !== null ? new HexColor($color) : null
            );

            $this->tagRepository->save($tag);

            return $tag;
        });
    }

    /**
     * @throws TagNotFoundException when tag doesn't exist
     */
    #[\Override]
    public function updateTag(string $tagName, ?string $color = null): Tag
    {
        return $this->unitOfWork->transactional(function () use ($tagName, $color): Tag {
            $tag = $this->tagRepository->findByName($tagName);

            $tag->color = $color !== null ? new HexColor($color) : null;

            $this->tagRepository->save($tag);

            return $tag;
        });
    }

    /**
     * @throws TagNotFoundException when tag doesn't exist
     */
    #[\Override]
    public function deleteTag(string $tagName): void
    {
        $this->unitOfWork->transactional(function () use ($tagName): void {
            $tag = $this->tagRepository->findByName($tagName);
            $this->tagRepository->delete($tag);
        });
    }

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function assignTagToLink(UuidInterface $linkId, string $tagName, ?string $color = null): void
    {
        $this->unitOfWork->transactional(function () use ($linkId, $tagName, $color): void {
            // Verify link exists
            $this->linkRepository->findById($linkId);

            // Check if tag exists
            try {
                $this->tagRepository->findByName($tagName);
            } catch (TagNotFoundException) {
                // Tag doesn't exist, create it
                $tag = new Tag(
                    new TagName($tagName),
                    $color !== null ? new HexColor($color) : null
                );
                $this->tagRepository->save($tag);
            }

            // Assign tag to link (only if not already assigned)
            if (!$this->tagRepository->isAssignedToLinkId($linkId, $tagName)) {
                $this->tagRepository->assignToLinkId($linkId, $tagName);
            }
        });
    }

    #[\Override]
    public function removeTagFromLink(UuidInterface $linkId, string $tagName): void
    {
        $this->unitOfWork->transactional(function () use ($linkId, $tagName): void {
            $this->tagRepository->removeFromLinkId($linkId, $tagName);
        });
    }

    #[\Override]
    public function searchTags(string $query, int $limit = 20): TagCollection
    {
        return $this->tagRepository->searchByName($query, $limit);
    }
}
