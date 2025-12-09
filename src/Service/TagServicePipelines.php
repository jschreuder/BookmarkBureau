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
     * @param PipelineInterface<null, TagCollection>|null $getAllTags
     * @param PipelineInterface<Tag, Tag>|null $createTag
     * @param PipelineInterface<Tag, Tag>|null $updateTag
     * @param PipelineInterface<Tag, null>|null $deleteTag
     * @param PipelineInterface<LinkWithTagName, null>|null $addTagToLink
     * @param PipelineInterface<LinkWithTagName, null>|null $removeTagFromLink
     */
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $getTag = null,
        private ?PipelineInterface $getAllTags = null,
        private ?PipelineInterface $createTag = null,
        private ?PipelineInterface $updateTag = null,
        private ?PipelineInterface $deleteTag = null,
        private ?PipelineInterface $addTagToLink = null,
        private ?PipelineInterface $removeTagFromLink = null,
    ) {}

    /** @return PipelineInterface<TagName, Tag> */
    public function getTag(): PipelineInterface
    {
        return $this->getTag ?? $this->default;
    }

    /** @return PipelineInterface<null, TagCollection> */
    public function getAllTags(): PipelineInterface
    {
        return $this->getAllTags ?? $this->default;
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
    public function addTagToLink(): PipelineInterface
    {
        return $this->addTagToLink ?? $this->default;
    }

    /** @return PipelineInterface<LinkWithTagName, null> */
    public function removeTagFromLink(): PipelineInterface
    {
        return $this->removeTagFromLink ?? $this->default;
    }
}
