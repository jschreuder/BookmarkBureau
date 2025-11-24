<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Composite\CategoryWithLinks;
use jschreuder\BookmarkBureau\Composite\CategoryWithLinksCollection;
use jschreuder\BookmarkBureau\Composite\DashboardCollection;
use jschreuder\BookmarkBureau\Composite\DashboardWithCategoriesAndFavorites;
use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class DashboardService implements DashboardServiceInterface
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly FavoriteRepositoryInterface $favoriteRepository,
        private readonly DashboardServicePipelines $pipelines,
    ) {}

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function getDashboard(UuidInterface $dashboardId): Dashboard
    {
        return $this->pipelines
            ->getDashboard()
            ->run(
                fn(
                    UuidInterface $did,
                ): Dashboard => $this->dashboardRepository->findById($did),
                $dashboardId,
            );
    }

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function getFullDashboard(
        UuidInterface $dashboardId,
    ): DashboardWithCategoriesAndFavorites {
        return $this->pipelines
            ->getFullDashboard()
            ->run(function (
                UuidInterface $did,
            ): DashboardWithCategoriesAndFavorites {
                $dashboard = $this->dashboardRepository->findById($did);

                // Get all categories for this dashboard
                $categories = $this->categoryRepository->findByDashboardId(
                    $dashboard->dashboardId,
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
                    $categoriesWithLinks[] = new CategoryWithLinks(
                        $category,
                        $links,
                    );
                }

                // Get all favorites for this dashboard
                $favorites = $this->favoriteRepository->findByDashboardId(
                    $dashboard->dashboardId,
                );

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
            }, $dashboardId);
    }

    #[\Override]
    public function listAllDashboards(): DashboardCollection
    {
        return $this->pipelines
            ->listAllDashboards()
            ->run(
                fn(): DashboardCollection => $this->dashboardRepository->findAll(),
            );
    }

    #[\Override]
    public function createDashboard(
        string $title,
        string $description,
        ?string $icon = null,
    ): Dashboard {
        $newDashboard = new Dashboard(
            Uuid::uuid4(),
            new Title($title),
            $description,
            $icon !== null ? new Icon($icon) : null,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        return $this->pipelines
            ->createDashboard()
            ->run(function (Dashboard $dashboard): Dashboard {
                $this->dashboardRepository->save($dashboard);
                return $dashboard;
            }, $newDashboard);
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
        $updatedDashboard = $this->dashboardRepository->findById($dashboardId);
        $updatedDashboard->title = new Title($title);
        $updatedDashboard->description = $description;
        $updatedDashboard->icon = $icon !== null ? new Icon($icon) : null;

        return $this->pipelines
            ->updateDashboard()
            ->run(function (Dashboard $dashboard): Dashboard {
                $this->dashboardRepository->save($dashboard);
                return $dashboard;
            }, $updatedDashboard);
    }

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function deleteDashboard(UuidInterface $dashboardId): void
    {
        $deleteDashboard = $this->dashboardRepository->findById($dashboardId);
        $this->pipelines
            ->deleteDashboard()
            ->run(function (Dashboard $dashboard): null {
                $this->dashboardRepository->delete($dashboard);
                return null;
            }, $deleteDashboard);
    }
}
