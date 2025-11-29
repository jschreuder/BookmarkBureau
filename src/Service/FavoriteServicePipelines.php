<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Composite\FavoriteCollection;
use jschreuder\BookmarkBureau\Composite\FavoriteParams;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class FavoriteServicePipelines
{
    /**
     * @param PipelineInterface<FavoriteParams, Favorite>|null $addFavorite
     * @param PipelineInterface<FavoriteParams, null>|null $removeFavorite
     * @param PipelineInterface<UuidInterface, FavoriteCollection>|null $getFavoritesForDashboard
     * @param PipelineInterface<FavoriteCollection, null>|null $reorderFavorites
     */
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $addFavorite = null,
        private ?PipelineInterface $removeFavorite = null,
        private ?PipelineInterface $getFavoritesForDashboard = null,
        private ?PipelineInterface $reorderFavorites = null,
    ) {}

    /** @return PipelineInterface<FavoriteParams, Favorite> */
    public function addFavorite(): PipelineInterface
    {
        return $this->addFavorite ?? $this->default;
    }

    /** @return PipelineInterface<FavoriteParams, null> */
    public function removeFavorite(): PipelineInterface
    {
        return $this->removeFavorite ?? $this->default;
    }

    /** @return PipelineInterface<UuidInterface, FavoriteCollection> */
    public function getFavoritesForDashboard(): PipelineInterface
    {
        return $this->getFavoritesForDashboard ?? $this->default;
    }

    /** @return PipelineInterface<FavoriteCollection, null> */
    public function reorderFavorites(): PipelineInterface
    {
        return $this->reorderFavorites ?? $this->default;
    }
}
