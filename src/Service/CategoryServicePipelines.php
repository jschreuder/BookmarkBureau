<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;

final readonly class CategoryServicePipelines
{
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $getCategory = null,
        private ?PipelineInterface $createCategory = null,
        private ?PipelineInterface $updateCategory = null,
        private ?PipelineInterface $deleteCategory = null,
        private ?PipelineInterface $reorderCategories = null,
        private ?PipelineInterface $addLinkToCategory = null,
        private ?PipelineInterface $removeLinkFromCategory = null,
        private ?PipelineInterface $reorderLinksInCategory = null,
    ) {}

    public function getCategory(): PipelineInterface
    {
        return $this->getCategory ?? $this->default;
    }

    public function createCategory(): PipelineInterface
    {
        return $this->createCategory ?? $this->default;
    }

    public function updateCategory(): PipelineInterface
    {
        return $this->updateCategory ?? $this->default;
    }

    public function deleteCategory(): PipelineInterface
    {
        return $this->deleteCategory ?? $this->default;
    }

    public function reorderCategories(): PipelineInterface
    {
        return $this->reorderCategories ?? $this->default;
    }

    public function addLinkToCategory(): PipelineInterface
    {
        return $this->addLinkToCategory ?? $this->default;
    }

    public function removeLinkFromCategory(): PipelineInterface
    {
        return $this->removeLinkFromCategory ?? $this->default;
    }

    public function reorderLinksInCategory(): PipelineInterface
    {
        return $this->reorderLinksInCategory ?? $this->default;
    }
}
