<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Composite\LinkWithTagName;
use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class TagServicePipelines
{
    /**
     * @param PipelineInterface<TagName, Tag>|null $getTag
     * @param PipelineInterface<null, TagCollection>|null $listAllTags
     * @param PipelineInterface<UuidInterface, TagCollection>|null $getTagsForLink
     * @param PipelineInterface<Tag, Tag>|null $createTag
     * @param PipelineInterface<Tag, Tag>|null $updateTag
     * @param PipelineInterface<Tag, null>|null $deleteTag
     * @param PipelineInterface<LinkWithTagName, null>|null $assignTagToLink
     * @param PipelineInterface<LinkWithTagName, null>|null $removeTagFromLink
     * @param PipelineInterface<null, TagCollection>|null $searchTags
     */
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $getTag = null,
        private ?PipelineInterface $listAllTags = null,
        private ?PipelineInterface $getTagsForLink = null,
        private ?PipelineInterface $createTag = null,
        private ?PipelineInterface $updateTag = null,
        private ?PipelineInterface $deleteTag = null,
        private ?PipelineInterface $assignTagToLink = null,
        private ?PipelineInterface $removeTagFromLink = null,
        private ?PipelineInterface $searchTags = null,
    ) {}

    /** @return PipelineInterface<TagName, Tag> */
    public function getTag(): PipelineInterface
    {
        return $this->getTag ?? $this->default;
    }

    /** @return PipelineInterface<null, TagCollection> */
    public function listAllTags(): PipelineInterface
    {
        return $this->listAllTags ?? $this->default;
    }

    /** @return PipelineInterface<UuidInterface, TagCollection> */
    public function getTagsForLink(): PipelineInterface
    {
        return $this->getTagsForLink ?? $this->default;
    }

    /** @return PipelineInterface<Tag, Tag> */
    public function createTag(): PipelineInterface
    {
        return $this->createTag ?? $this->default;
    }

    /** @return PipelineInterface<Tag, Tag> */
    public function updateTag(): PipelineInterface
    {
        return $this->updateTag ?? $this->default;
    }

    /** @return PipelineInterface<Tag, null> */
    public function deleteTag(): PipelineInterface
    {
        return $this->deleteTag ?? $this->default;
    }

    /** @return PipelineInterface<LinkWithTagName, null> */
    public function assignTagToLink(): PipelineInterface
    {
        return $this->assignTagToLink ?? $this->default;
    }

    /** @return PipelineInterface<LinkWithTagName, null> */
    public function removeTagFromLink(): PipelineInterface
    {
        return $this->removeTagFromLink ?? $this->default;
    }

    /** @return PipelineInterface<null, TagCollection> */
    public function searchTags(): PipelineInterface
    {
        return $this->searchTags ?? $this->default;
    }
}
