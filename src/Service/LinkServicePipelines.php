<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Composite\TagNameCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class LinkServicePipelines
{
    /**
     * @param PipelineInterface<UuidInterface, Link>|null $getLink
     * @param PipelineInterface<Link, Link>|null $createLink
     * @param PipelineInterface<Link, Link>|null $updateLink
     * @param PipelineInterface<Link, null>|null $deleteLink
     * @param PipelineInterface<null, LinkCollection>|null $searchLinks
     * @param PipelineInterface<TagNameCollection, LinkCollection>|null $getLinksByTag
     * @param PipelineInterface<null, LinkCollection>|null $getLinks
     */
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $getLink = null,
        private ?PipelineInterface $createLink = null,
        private ?PipelineInterface $updateLink = null,
        private ?PipelineInterface $deleteLink = null,
        private ?PipelineInterface $searchLinks = null,
        private ?PipelineInterface $getLinksByTag = null,
        private ?PipelineInterface $getLinks = null,
    ) {}

    /** @return PipelineInterface<UuidInterface, Link> */
    public function getLink(): PipelineInterface
    {
        return $this->getLink ?? $this->default;
    }

    /** @return PipelineInterface<Link, Link> */
    public function createLink(): PipelineInterface
    {
        return $this->createLink ?? $this->default;
    }

    /** @return PipelineInterface<Link, Link> */
    public function updateLink(): PipelineInterface
    {
        return $this->updateLink ?? $this->default;
    }

    /** @return PipelineInterface<Link, null> */
    public function deleteLink(): PipelineInterface
    {
        return $this->deleteLink ?? $this->default;
    }

    /** @return PipelineInterface<null, LinkCollection> */
    public function searchLinks(): PipelineInterface
    {
        return $this->searchLinks ?? $this->default;
    }

    /** @return PipelineInterface<TagNameCollection, LinkCollection> */
    public function getLinksByTag(): PipelineInterface
    {
        return $this->getLinksByTag ?? $this->default;
    }

    /** @return PipelineInterface<null, LinkCollection> */
    public function getLinks(): PipelineInterface
    {
        return $this->getLinks ?? $this->default;
    }
}
