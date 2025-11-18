<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;

final readonly class LinkServicePipelines
{
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $getLink = null,
        private ?PipelineInterface $createLink = null,
        private ?PipelineInterface $updateLink = null,
        private ?PipelineInterface $deleteLink = null,
        private ?PipelineInterface $searchLinks = null,
        private ?PipelineInterface $findLinksByTag = null,
        private ?PipelineInterface $listLinks = null,
    ) {}

    public function getLink(): PipelineInterface
    {
        return $this->getLink ?? $this->default;
    }

    public function createLink(): PipelineInterface
    {
        return $this->createLink ?? $this->default;
    }

    public function updateLink(): PipelineInterface
    {
        return $this->updateLink ?? $this->default;
    }

    public function deleteLink(): PipelineInterface
    {
        return $this->deleteLink ?? $this->default;
    }

    public function searchLinks(): PipelineInterface
    {
        return $this->searchLinks ?? $this->default;
    }

    public function findLinksByTag(): PipelineInterface
    {
        return $this->findLinksByTag ?? $this->default;
    }

    public function listLinks(): PipelineInterface
    {
        return $this->listLinks ?? $this->default;
    }
}
