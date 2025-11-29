<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Handles bulk reordering of favorites within a dashboard via PUT request.
 * Expects ReorderFavoritesInputSpec, but it can be replaced to modify filtering and validation.
 */
final readonly class FavoriteReorderAction implements ActionInterface
{
    /** @param  OutputSpecInterface<Favorite> $outputSpec */
    public function __construct(
        private FavoriteServiceInterface $favoriteService,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec,
    ) {}

    #[\Override]
    public function filter(array $rawData): array
    {
        return $this->inputSpec->filter($rawData);
    }

    #[\Override]
    public function validate(array $data): void
    {
        $this->inputSpec->validate($data);
    }

    /** @param array{dashboard_id: string, links: array<int, array{link_id: string, sort_order: int}>} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $dashboardId = Uuid::fromString($data["dashboard_id"]);

        // Get current favorites and create a map by link_id for quick lookup
        $currentFavorites = $this->favoriteService->getFavoritesForDashboard(
            $dashboardId,
        );
        $favoritesMap = [];
        foreach ($currentFavorites as $favorite) {
            $favoritesMap[$favorite->link->linkId->toString()] = $favorite;
        }

        // Collect valid favorites with their sort_order, throw if link not favorited
        $validFavorites = [];
        foreach ($data["links"] as $linkData) {
            $linkId = $linkData["link_id"];
            if (!isset($favoritesMap[$linkId])) {
                throw FavoriteNotFoundException::forDashboardAndLink(
                    $dashboardId,
                    Uuid::fromString($linkId),
                );
            }
            $validFavorites[] = [
                "favorite" => $favoritesMap[$linkId],
                "sort_order" => $linkData["sort_order"],
            ];
        }

        // Sort favorites by sort_order
        usort(
            $validFavorites,
            fn($a, $b) => $a["sort_order"] <=> $b["sort_order"],
        );

        // Extract just the favorite objects in the sorted order
        $reorderedFavorites = array_map(
            fn($item) => $item["favorite"],
            $validFavorites,
        );

        // Reorder the favorites
        $this->favoriteService->reorderFavorites(
            $dashboardId,
            new FavoriteCollection(...$reorderedFavorites),
        );

        // Transform each favorite to array
        return [
            "favorites" => array_map(
                $this->outputSpec->transform(...),
                $reorderedFavorites,
            ),
        ];
    }
}
