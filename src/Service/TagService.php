<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Composite\LinkWithTagName;
use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Exception\DuplicateTagException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Exception\TagNotFoundException;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\TagRepositoryInterface;
use Ramsey\Uuid\UuidInterface;

final class TagService implements TagServiceInterface
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly TagServicePipelines $pipelines,
    ) {}

    #[\Override]
    public function getTag(string $tagName): Tag
    {
        return $this->tagRepository->findByName($tagName);
    }

    #[\Override]
    public function listAllTags(): TagCollection
    {
        return $this->pipelines
            ->listAllTags()
            ->run($this->tagRepository->findAll(...));
    }

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function getTagsForLink(UuidInterface $linkId): TagCollection
    {
        return $this->pipelines
            ->getTagsForLink()
            ->run(function (UuidInterface $lid): TagCollection {
                // Verify link exists before fetching tags
                $this->linkRepository->findById($lid);

                return $this->tagRepository->findTagsForLinkId($lid);
            }, $linkId);
    }

    /**
     * @throws DuplicateTagException when tag name already exists
     */
    #[\Override]
    public function createTag(string $tagName, ?string $color = null): Tag
    {
        $newTag = new Tag(
            new TagName($tagName),
            $color !== null ? new HexColor($color) : null,
        );

        return $this->pipelines->createTag()->run(function (Tag $tag): Tag {
            $this->tagRepository->insert($tag);
            return $tag;
        }, $newTag);
    }

    /**
     * @throws TagNotFoundException when tag doesn't exist
     */
    #[\Override]
    public function updateTag(string $tagName, ?string $color = null): Tag
    {
        $updatedTag = $this->tagRepository->findByName($tagName);
        $updatedTag->color = $color !== null ? new HexColor($color) : null;

        return $this->pipelines->updateTag()->run(function (Tag $tag): Tag {
            $this->tagRepository->update($tag);
            return $tag;
        }, $updatedTag);
    }

    /**
     * @throws TagNotFoundException when tag doesn't exist
     */
    #[\Override]
    public function deleteTag(string $tagName): void
    {
        $deleteTag = $this->tagRepository->findByName($tagName);
        $this->pipelines->deleteTag()->run(function (Tag $tag): null {
            $this->tagRepository->delete($tag);
            return null;
        }, $deleteTag);
    }

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function assignTagToLink(
        UuidInterface $linkId,
        string $tagName,
    ): void {
        $newLinkWithTag = new LinkWithTagName(
            $this->linkRepository->findById($linkId),
            new TagName($tagName),
        );

        $this->pipelines
            ->assignTagToLink()
            ->run(function (LinkWithTagName $linkWithTag): null {
                // Check if tag exists
                try {
                    $this->tagRepository->findByName(
                        $linkWithTag->tagName->value,
                    );
                } catch (TagNotFoundException) {
                    // Tag doesn't exist, create it
                    $tag = new Tag($linkWithTag->tagName, null);
                    $this->tagRepository->insert($tag);
                }

                // Assign tag to link (only if not already assigned)
                if (
                    !$this->tagRepository->isAssignedToLinkId(
                        $linkWithTag->link->linkId,
                        $linkWithTag->tagName->value,
                    )
                ) {
                    $this->tagRepository->assignToLinkId(
                        $linkWithTag->link->linkId,
                        $linkWithTag->tagName->value,
                    );
                }
                return null;
            }, $newLinkWithTag);
    }

    #[\Override]
    public function removeTagFromLink(
        UuidInterface $linkId,
        string $tagName,
    ): void {
        $deleteLinkWithTag = new LinkWithTagName(
            $this->linkRepository->findById($linkId),
            new TagName($tagName),
        );

        $this->pipelines
            ->removeTagFromLink()
            ->run(function (LinkWithTagName $linkWithTag): null {
                $this->tagRepository->removeFromLinkId(
                    $linkWithTag->link->linkId,
                    $linkWithTag->tagName->value,
                );
                return null;
            }, $deleteLinkWithTag);
    }

    #[\Override]
    public function searchTags(string $query, int $limit = 20): TagCollection
    {
        return $this->pipelines
            ->searchTags()
            ->run(
                fn(): TagCollection => $this->tagRepository->searchByName(
                    $query,
                    $limit,
                ),
            );
    }
}
