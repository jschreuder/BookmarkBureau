<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
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
     * @throws FavoriteNotFoundException when link is already favorited
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
        if ($this->favoriteRepository->isFavorite($dashboardId, $linkId)) {
            throw FavoriteNotFoundException::forDashboardAndLink(
                $dashboardId,
                $linkId,
            );
        }

        // Get the next sort order
        $sortOrder =
            $this->favoriteRepository->getMaxSortOrderForDashboardId(
                $dashboardId,
            ) + 1;

        $tempFavorite = new Favorite(
            $dashboard,
            $link,
            $sortOrder,
            new DateTimeImmutable(),
        );

        return $this->pipelines
            ->addFavorite()
            ->run(
                fn(
                    Favorite $favorite,
                ): Favorite => $this->favoriteRepository->addFavorite(
                    $favorite->dashboard->dashboardId,
                    $favorite->link->linkId,
                    $favorite->sortOrder,
                ),
                $tempFavorite,
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

        $removeFavorite = new Favorite(
            $dashboard,
            $link,
            0,
            new DateTimeImmutable(),
        );

        $this->pipelines
            ->removeFavorite()
            ->run(function (Favorite $favorite): void {
                $this->favoriteRepository->removeFavorite(
                    $favorite->dashboard->dashboardId,
                    $favorite->link->linkId,
                );
            }, $removeFavorite);
    }

    #[\Override]
    public function reorderFavorites(
        UuidInterface $dashboardId,
        array $linkIdToSortOrder,
    ): FavoriteCollection {
        return $this->pipelines
            ->reorderFavorites()
            ->run(function () use (
                $dashboardId,
                $linkIdToSortOrder,
            ): FavoriteCollection {
                $this->favoriteRepository->reorderFavorites(
                    $dashboardId,
                    $linkIdToSortOrder,
                );
                return $this->favoriteRepository->findByDashboardId(
                    $dashboardId,
                );
            });
    }
}
