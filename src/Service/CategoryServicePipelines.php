<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Composite\CategoryCollection;
use jschreuder\BookmarkBureau\Composite\CategoryLinkParams;
use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class CategoryServicePipelines
{
    /**
     * @param PipelineInterface<UuidInterface, Category>|null $getCategory
     * @param PipelineInterface<UuidInterface, CategoryCollection>|null $getCategoriesForDashboard
     * @param PipelineInterface<Category, Category>|null $createCategory
     * @param PipelineInterface<Category, Category>|null $updateCategory
     * @param PipelineInterface<Category, null>|null $deleteCategory
     * @param PipelineInterface<CategoryCollection, null>|null $reorderCategories
     * @param PipelineInterface<CategoryLinkParams, CategoryLink>|null $addLinkToCategory
     * @param PipelineInterface<CategoryLinkParams, null>|null $removeLinkFromCategory
     * @param PipelineInterface<LinkCollection, null>|null $reorderLinksInCategory
     */
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $getCategory = null,
        private ?PipelineInterface $getCategoriesForDashboard = null,
        private ?PipelineInterface $createCategory = null,
        private ?PipelineInterface $updateCategory = null,
        private ?PipelineInterface $deleteCategory = null,
        private ?PipelineInterface $reorderCategories = null,
        private ?PipelineInterface $addLinkToCategory = null,
        private ?PipelineInterface $removeLinkFromCategory = null,
        private ?PipelineInterface $reorderLinksInCategory = null,
    ) {}

    /** @return PipelineInterface<UuidInterface, Category> */
    public function getCategory(): PipelineInterface
    {
        return $this->getCategory ?? $this->default;
    }

    /** @return PipelineInterface<UuidInterface, CategoryCollection> */
    public function getCategoriesForDashboard(): PipelineInterface
    {
        return $this->getCategoriesForDashboard ?? $this->default;
    }

    /** @return PipelineInterface<Category, Category> */
    public function createCategory(): PipelineInterface
    {
        return $this->createCategory ?? $this->default;
    }

    /** @return PipelineInterface<Category, Category> */
    public function updateCategory(): PipelineInterface
    {
        return $this->updateCategory ?? $this->default;
    }

    /** @return PipelineInterface<Category, null> */
    public function deleteCategory(): PipelineInterface
    {
        return $this->deleteCategory ?? $this->default;
    }

    /** @return PipelineInterface<CategoryCollection, null> */
    public function reorderCategories(): PipelineInterface
    {
        return $this->reorderCategories ?? $this->default;
    }

    /** @return PipelineInterface<CategoryLinkParams, CategoryLink> */
    public function addLinkToCategory(): PipelineInterface
    {
        return $this->addLinkToCategory ?? $this->default;
    }

    /** @return PipelineInterface<CategoryLinkParams, null> */
    public function removeLinkFromCategory(): PipelineInterface
    {
        return $this->removeLinkFromCategory ?? $this->default;
    }

    /** @return PipelineInterface<LinkCollection, null> */
    public function reorderLinksInCategory(): PipelineInterface
    {
        return $this->reorderLinksInCategory ?? $this->default;
    }
}
