<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;

final readonly class TagServicePipelines
{
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $listAllTags = null,
        private ?PipelineInterface $getTagsForLink = null,
        private ?PipelineInterface $createTag = null,
        private ?PipelineInterface $updateTag = null,
        private ?PipelineInterface $deleteTag = null,
        private ?PipelineInterface $assignTagToLink = null,
        private ?PipelineInterface $removeTagFromLink = null,
        private ?PipelineInterface $searchTags = null,
    ) {}

    public function listAllTags(): PipelineInterface
    {
        return $this->listAllTags ?? $this->default;
    }

    public function getTagsForLink(): PipelineInterface
    {
        return $this->getTagsForLink ?? $this->default;
    }

    public function createTag(): PipelineInterface
    {
        return $this->createTag ?? $this->default;
    }

    public function updateTag(): PipelineInterface
    {
        return $this->updateTag ?? $this->default;
    }

    public function deleteTag(): PipelineInterface
    {
        return $this->deleteTag ?? $this->default;
    }

    public function assignTagToLink(): PipelineInterface
    {
        return $this->assignTagToLink ?? $this->default;
    }

    public function removeTagFromLink(): PipelineInterface
    {
        return $this->removeTagFromLink ?? $this->default;
    }

    public function searchTags(): PipelineInterface
    {
        return $this->searchTags ?? $this->default;
    }
}
