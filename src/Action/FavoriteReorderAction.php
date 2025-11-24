<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Entity\Favorite;
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

    #[\Override]
    public function execute(array $data): array
    {
        $dashboardId = Uuid::fromString($data["dashboard_id"]);

        // Get current favorites and create a map by link_id for quick lookup
        $currentFavorites = $this->favoriteService->getFavoritesForDashboardId(
            $dashboardId,
        );
        $favoritesMap = [];
        foreach ($currentFavorites as $favorite) {
            $favoritesMap[$favorite->link->linkId->toString()] = $favorite;
        }

        // Build the reordered collection based on the request order
        $reorderedFavorites = [];
        foreach ($data["links"] as $link) {
            if (isset($favoritesMap[$link["link_id"]])) {
                $reorderedFavorites[] = $favoritesMap[$link["link_id"]];
            }
        }

        // Reorder the favorites
        $this->favoriteService->reorderFavorites(
            $dashboardId,
            new FavoriteCollection(...$reorderedFavorites),
        );

        // Transform each favorite to array
        return [
            "favorites" => array_map(
                fn($favorite) => $this->outputSpec->transform($favorite),
                $reorderedFavorites,
            ),
        ];
    }
}
