<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Composite\CategoryCollection;
use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly CategoryServicePipelines $pipelines,
    ) {}

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function getCategory(UuidInterface $categoryId): Category
    {
        return $this->pipelines
            ->getCategory()
            ->run(
                fn(
                    UuidInterface $cid,
                ): Category => $this->categoryRepository->findById($cid),
                $categoryId,
            );
    }

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function createCategory(
        UuidInterface $dashboardId,
        string $title,
        ?string $color = null,
    ): Category {
        // Verify dashboard exists
        $dashboard = $this->dashboardRepository->findById($dashboardId);

        // Get the next sort order
        $sortOrder =
            $this->categoryRepository->getMaxSortOrderForDashboardId(
                $dashboardId,
            ) + 1;

        $category = new Category(
            Uuid::uuid4(),
            $dashboard,
            new Title($title),
            $color !== null ? new HexColor($color) : null,
            $sortOrder,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        return $this->pipelines
            ->createCategory()
            ->run(function (Category $newCategory): Category {
                $this->categoryRepository->save($newCategory);
                return $newCategory;
            }, $category);
    }

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function updateCategory(
        UuidInterface $categoryId,
        string $title,
        ?string $color = null,
    ): Category {
        $category = $this->categoryRepository->findById($categoryId);
        $category->title = new Title($title);
        $category->color = $color !== null ? new HexColor($color) : null;

        return $this->pipelines
            ->updateCategory()
            ->run(function (Category $updatedCategory): Category {
                $this->categoryRepository->save($updatedCategory);
                return $updatedCategory;
            }, $category);
    }

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function deleteCategory(UuidInterface $categoryId): void
    {
        $this->pipelines
            ->deleteCategory()
            ->run(function (UuidInterface $categoryId): void {
                $category = $this->categoryRepository->findById($categoryId);
                $this->categoryRepository->delete($category);
            }, $categoryId);
    }

    #[\Override]
    public function reorderCategories(
        UuidInterface $dashboardId,
        array $categoryIdToSortOrder,
    ): void {
        // Get all categories for the dashboard
        $categories = $this->categoryRepository->findByDashboardId(
            $dashboardId,
        );

        // Update sort orders
        $updatedCategories = [];
        foreach ($categories as $category) {
            $categoryIdString = $category->categoryId->toString();
            if (isset($categoryIdToSortOrder[$categoryIdString])) {
                $category->sortOrder =
                    $categoryIdToSortOrder[$categoryIdString];
                $updatedCategories[] = $category;
            }
        }

        $this->pipelines
            ->reorderCategories()
            ->run(function (CategoryCollection $reorderedCategories): void {
                foreach ($reorderedCategories as $category) {
                    $this->categoryRepository->save($category);
                }
            }, new CategoryCollection(...$updatedCategories));
    }

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function addLinkToCategory(
        UuidInterface $categoryId,
        UuidInterface $linkId,
    ): CategoryLink {
        // Verify category & link exist
        $category = $this->categoryRepository->findById($categoryId);
        $link = $this->linkRepository->findById($linkId);

        // Get the next sort order for links in this category
        $sortOrder =
            $this->categoryRepository->getMaxSortOrderForCategoryId(
                $categoryId,
            ) + 1;

        $tempCategoryLink = new CategoryLink(
            $category,
            $link,
            $sortOrder,
            new DateTimeImmutable(),
        );

        return $this->pipelines
            ->addLinkToCategory()
            ->run(
                fn(
                    CategoryLink $categoryLink,
                ): CategoryLink => $this->categoryRepository->addLink(
                    $categoryLink->category->categoryId,
                    $categoryLink->link->linkId,
                    $categoryLink->sortOrder,
                ),
                $tempCategoryLink,
            );
    }

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function removeLinkFromCategory(
        UuidInterface $categoryId,
        UuidInterface $linkId,
    ): void {
        // Verify category & link exist
        $category = $this->categoryRepository->findById($categoryId);
        $link = $this->linkRepository->findById($linkId);
        $tempCategoryLink = new CategoryLink(
            $category,
            $link,
            0,
            new DateTimeImmutable(),
        );

        $this->pipelines
            ->removeLinkFromCategory()
            ->run(function (CategoryLink $categoryLink): void {
                $this->categoryRepository->removeLink(
                    $categoryLink->category->categoryId,
                    $categoryLink->link->linkId,
                );
            }, $tempCategoryLink);
    }

    #[\Override]
    public function reorderLinksInCategory(
        UuidInterface $categoryId,
        LinkCollection $links,
    ): void {
        $this->pipelines
            ->reorderLinksInCategory()
            ->run(function (LinkCollection $links) use ($categoryId): void {
                $this->categoryRepository->reorderLinks($categoryId, $links);
            }, $links);
    }
}
