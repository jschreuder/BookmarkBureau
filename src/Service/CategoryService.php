<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {
    }

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function getCategory(UuidInterface $categoryId): Category
    {
        return $this->categoryRepository->findById($categoryId);
    }

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function createCategory(
        UuidInterface $dashboardId,
        string $title,
        ?string $color = null
    ): Category {
        return $this->unitOfWork->transactional(function () use ($dashboardId, $title, $color): Category {
            // Verify dashboard exists
            $dashboard = $this->dashboardRepository->findById($dashboardId);

            // Get the next sort order
            $sortOrder = $this->categoryRepository->getMaxSortOrderForDashboardId($dashboardId) + 1;

            $category = new Category(
                Uuid::uuid4(),
                $dashboard,
                new Title($title),
                $color !== null ? new HexColor($color) : null,
                $sortOrder,
                new \DateTimeImmutable(),
                new \DateTimeImmutable()
            );

            $this->categoryRepository->save($category);

            return $category;
        });
    }

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function updateCategory(
        UuidInterface $categoryId,
        string $title,
        ?string $color = null
    ): Category {
        return $this->unitOfWork->transactional(function () use ($categoryId, $title, $color): Category {
            $category = $this->categoryRepository->findById($categoryId);

            $category->title = new Title($title);
            $category->color = $color !== null ? new HexColor($color) : null;

            $this->categoryRepository->save($category);

            return $category;
        });
    }

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function deleteCategory(UuidInterface $categoryId): void
    {
        $this->unitOfWork->transactional(function () use ($categoryId): void {
            $category = $this->categoryRepository->findById($categoryId);
            $this->categoryRepository->delete($category);
        });
    }

    #[\Override]
    public function reorderCategories(UuidInterface $dashboardId, array $categoryIdToSortOrder): void
    {
        $this->unitOfWork->transactional(function () use ($dashboardId, $categoryIdToSortOrder): void {
            // Get all categories for the dashboard
            $categories = $this->categoryRepository->findByDashboardId($dashboardId);

            // Update sort orders
            foreach ($categories as $category) {
                $categoryIdString = $category->categoryId->toString();
                if (isset($categoryIdToSortOrder[$categoryIdString])) {
                    $category->sortOrder = $categoryIdToSortOrder[$categoryIdString];
                    $this->categoryRepository->save($category);
                }
            }
        });
    }

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function addLinkToCategory(UuidInterface $categoryId, UuidInterface $linkId): void
    {
        $this->unitOfWork->transactional(function () use ($categoryId, $linkId): void {
            // Verify category exists
            $this->categoryRepository->findById($categoryId);

            // Get the next sort order for links in this category
            $sortOrder = $this->categoryRepository->getMaxSortOrderForCategoryId($categoryId) + 1;

            // Add link to category (will throw LinkNotFoundException if link doesn't exist)
            $this->categoryRepository->addLink($categoryId, $linkId, $sortOrder);
        });
    }

    /**
     * @throws CategoryNotFoundException when category doesn't exist
     */
    #[\Override]
    public function removeLinkFromCategory(UuidInterface $categoryId, UuidInterface $linkId): void
    {
        $this->unitOfWork->transactional(function () use ($categoryId, $linkId): void {
            $this->categoryRepository->removeLink($categoryId, $linkId);
        });
    }

    #[\Override]
    public function reorderLinksInCategory(UuidInterface $categoryId, LinkCollection $links): void
    {
        $this->unitOfWork->transactional(function () use ($categoryId, $links): void {
            $this->categoryRepository->reorderLinks($categoryId, $links);
        });
    }
}
