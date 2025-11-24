<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects the FavoriteInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class FavoriteCreateAction implements ActionInterface
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
        // Create operations need dashboard_id and link_id, but not sort_order
        $fields = ["dashboard_id", "link_id"];
        return $this->inputSpec->filter($rawData, $fields);
    }

    #[\Override]
    public function validate(array $data): void
    {
        // Create operations need dashboard_id and link_id, but not sort_order
        $fields = ["dashboard_id", "link_id"];
        $this->inputSpec->validate($data, $fields);
    }

    #[\Override]
    public function execute(array $data): array
    {
        $favorite = $this->favoriteService->addFavorite(
            dashboardId: Uuid::fromString($data["dashboard_id"]),
            linkId: Uuid::fromString($data["link_id"]),
        );
        return $this->outputSpec->transform($favorite);
    }
}
