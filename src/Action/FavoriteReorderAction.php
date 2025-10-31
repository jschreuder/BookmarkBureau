<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

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
    public function __construct(
        private FavoriteServiceInterface $favoriteService,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec
    ) {}

    public function filter(array $rawData): array
    {
        return $this->inputSpec->filter($rawData);
    }

    public function validate(array $data): void
    {
        $this->inputSpec->validate($data);
    }

    public function execute(array $data): array
    {
        // Transform the links array into a map of link_id => sort_order
        $linkIdToSortOrder = [];
        foreach ($data['links'] as $link) {
            $linkIdToSortOrder[$link['link_id']] = $link['sort_order'];
        }

        // Reorder and get the updated favorites
        $reorderedFavorites = $this->favoriteService->reorderFavorites(
            Uuid::fromString($data['dashboard_id']),
            $linkIdToSortOrder
        );

        // Transform each favorite to array
        return array_map(
            fn($favorite) => $this->outputSpec->transform($favorite),
            $reorderedFavorites->toArray()
        );
    }
}
