<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;
use Ramsey\Uuid\UuidInterface;

final class FavoriteService implements FavoriteServiceInterface
{
    public function __construct(
        private readonly FavoriteRepositoryInterface $favoriteRepository,
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {
    }

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     */
    public function addFavorite(UuidInterface $dashboardId, UuidInterface $linkId): void
    {
        $this->unitOfWork->transactional(function () use ($dashboardId, $linkId): void {
            // Verify dashboard exists
            $this->dashboardRepository->findById($dashboardId);

            // Verify link exists
            $this->linkRepository->findById($linkId);

            // Check if already favorited
            if (!$this->favoriteRepository->isFavorite($dashboardId, $linkId)) {
                // Get the next sort order
                $sortOrder = $this->favoriteRepository->getMaxSortOrderForDashboardId($dashboardId) + 1;

                // Add the favorite
                $this->favoriteRepository->addFavorite($dashboardId, $linkId, $sortOrder);
            }
        });
    }

    /**
     * @throws FavoriteNotFoundException when favorite doesn't exist
     */
    public function removeFavorite(UuidInterface $dashboardId, UuidInterface $linkId): void
    {
        $this->unitOfWork->transactional(function () use ($dashboardId, $linkId): void {
            $this->favoriteRepository->removeFavorite($dashboardId, $linkId);
        });
    }

    public function reorderFavorites(UuidInterface $dashboardId, array $linkIdToSortOrder): void
    {
        $this->unitOfWork->transactional(function () use ($dashboardId, $linkIdToSortOrder): void {
            $this->favoriteRepository->reorderFavorites($dashboardId, $linkIdToSortOrder);
        });
    }
}
