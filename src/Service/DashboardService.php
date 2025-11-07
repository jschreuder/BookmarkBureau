<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Collection\CategoryWithLinks;
use jschreuder\BookmarkBureau\Collection\CategoryWithLinksCollection;
use jschreuder\BookmarkBureau\Collection\DashboardCollection;
use jschreuder\BookmarkBureau\Collection\DashboardWithCategoriesAndFavorites;
use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class DashboardService implements DashboardServiceInterface
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly FavoriteRepositoryInterface $favoriteRepository,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {}

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function getFullDashboard(
        UuidInterface $dashboardId,
    ): DashboardWithCategoriesAndFavorites {
        $dashboard = $this->dashboardRepository->findById($dashboardId);

        // Get all categories for this dashboard
        $categories = $this->categoryRepository->findByDashboardId(
            $dashboardId,
        );

        // Build categories with their links
        $categoriesWithLinks = [];
        foreach ($categories as $category) {
            $categoryLinks = $this->categoryRepository->findCategoryLinksForCategoryId(
                $category->categoryId,
            );
            $links = new LinkCollection(
                ...array_map(
                    fn(CategoryLink $cl) => $cl->link,
                    $categoryLinks->toArray(),
                ),
            );
            $categoriesWithLinks[] = new CategoryWithLinks($category, $links);
        }

        // Get all favorites for this dashboard
        $favorites = $this->favoriteRepository->findByDashboardId($dashboardId);

        return new DashboardWithCategoriesAndFavorites(
            $dashboard,
            new CategoryWithLinksCollection(...$categoriesWithLinks),
            new LinkCollection(
                ...array_map(
                    fn($favorite) => $favorite->link,
                    iterator_to_array($favorites),
                ),
            ),
        );
    }

    #[\Override]
    public function listAllDashboards(): DashboardCollection
    {
        return $this->dashboardRepository->findAll();
    }

    #[\Override]
    public function createDashboard(
        string $title,
        string $description,
        ?string $icon = null,
    ): Dashboard {
        return $this->unitOfWork->transactional(function () use (
            $title,
            $description,
            $icon,
        ): Dashboard {
            $dashboard = new Dashboard(
                Uuid::uuid4(),
                new Title($title),
                $description,
                $icon !== null ? new Icon($icon) : null,
                new DateTimeImmutable(),
                new DateTimeImmutable(),
            );

            $this->dashboardRepository->save($dashboard);

            return $dashboard;
        });
    }

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function updateDashboard(
        UuidInterface $dashboardId,
        string $title,
        string $description,
        ?string $icon = null,
    ): Dashboard {
        return $this->unitOfWork->transactional(function () use (
            $dashboardId,
            $title,
            $description,
            $icon,
        ): Dashboard {
            $dashboard = $this->dashboardRepository->findById($dashboardId);

            $dashboard->title = new Title($title);
            $dashboard->description = $description;
            $dashboard->icon = $icon !== null ? new Icon($icon) : null;

            $this->dashboardRepository->save($dashboard);

            return $dashboard;
        });
    }

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function deleteDashboard(UuidInterface $dashboardId): void
    {
        $this->unitOfWork->transactional(function () use ($dashboardId): void {
            $dashboard = $this->dashboardRepository->findById($dashboardId);
            $this->dashboardRepository->delete($dashboard);
        });
    }
}
