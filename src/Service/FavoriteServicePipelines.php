<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;

final readonly class FavoriteServicePipelines
{
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $addFavorite = null,
        private ?PipelineInterface $removeFavorite = null,
        private ?PipelineInterface $reorderFavorites = null,
    ) {}

    public function addFavorite(): PipelineInterface
    {
        return $this->addFavorite ?? $this->default;
    }

    public function removeFavorite(): PipelineInterface
    {
        return $this->removeFavorite ?? $this->default;
    }

    public function reorderFavorites(): PipelineInterface
    {
        return $this->reorderFavorites ?? $this->default;
    }
}
