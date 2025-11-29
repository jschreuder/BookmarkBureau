<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Composite\FavoriteParams;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use jschreuder\BookmarkBureau\Exception\DuplicateFavoriteException;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use Ramsey\Uuid\UuidInterface;

final class FavoriteService implements FavoriteServiceInterface
{
    public function __construct(
        private readonly FavoriteRepositoryInterface $favoriteRepository,
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly FavoriteServicePipelines $pipelines,
    ) {}

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     * @throws LinkNotFoundException when link doesn't exist
     * @throws DuplicateFavoriteException when link is already favorited
     */
    #[\Override]
    public function addFavorite(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
    ): Favorite {
        // Verify dashboard & link exist
        $dashboard = $this->dashboardRepository->findById($dashboardId);
        $link = $this->linkRepository->findById($linkId);

        // Check if already favorited
        if (
            $this->favoriteRepository->hasLinkAsFavorite($dashboardId, $linkId)
        ) {
            throw DuplicateFavoriteException::forDashboardAndLink(
                $dashboardId,
                $linkId,
            );
        }

        // Get the next sort order
        $sortOrder =
            $this->favoriteRepository->computeMaxSortOrderForDashboardId(
                $dashboardId,
            ) + 1;

        $favoriteParams = new FavoriteParams($dashboard, $link, $sortOrder);

        return $this->pipelines
            ->addFavorite()
            ->run(
                fn(
                    FavoriteParams $favorite,
                ): Favorite => $this->favoriteRepository->addFavorite(
                    $favorite->dashboard->dashboardId,
                    $favorite->link->linkId,
                    $favorite->sortOrder,
                ),
                $favoriteParams,
            );
    }

    /**
     * @throws FavoriteNotFoundException when favorite doesn't exist
     */
    #[\Override]
    public function removeFavorite(
        UuidInterface $dashboardId,
        UuidInterface $linkId,
    ): void {
        // Verify dashboard & link exist
        $dashboard = $this->dashboardRepository->findById($dashboardId);
        $link = $this->linkRepository->findById($linkId);

        $removeFavorite = new FavoriteParams($dashboard, $link);

        $this->pipelines
            ->removeFavorite()
            ->run(function (FavoriteParams $favoriteParams): null {
                $this->favoriteRepository->removeFavorite(
                    $favoriteParams->dashboard->dashboardId,
                    $favoriteParams->link->linkId,
                );
                return null;
            }, $removeFavorite);
    }

    /**
     * @throws DashboardNotFoundException when dashboard doesn't exist
     */
    #[\Override]
    public function getFavoritesForDashboard(
        UuidInterface $dashboardId,
    ): FavoriteCollection {
        // Verify dashboard exists
        $this->dashboardRepository->findById($dashboardId);

        return $this->pipelines
            ->getFavoritesForDashboard()
            ->run(
                $this->favoriteRepository->listForDashboardId(...),
                $dashboardId,
            );
    }

    #[\Override]
    public function reorderFavorites(
        UuidInterface $dashboardId,
        FavoriteCollection $favorites,
    ): void {
        $this->pipelines
            ->reorderFavorites()
            ->run(function (FavoriteCollection $favorites) use (
                $dashboardId,
            ): null {
                $this->favoriteRepository->reorderFavorites(
                    $dashboardId,
                    $favorites,
                );
                return null;
            }, $favorites);
    }
}
