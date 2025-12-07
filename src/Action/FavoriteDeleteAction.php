<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects an InputSpec that validates dashboard_id and link_id, but it can be replaced
 * to modify filtering and validation.
 */
final readonly class FavoriteDeleteAction implements ActionInterface
{
    public function __construct(
        private FavoriteServiceInterface $favoriteService,
        private InputSpecInterface $inputSpec,
    ) {}

    #[\Override]
    public function getAttributeKeysForData(): array
    {
        return ["dashboard_id", "link_id"];
    }

    #[\Override]
    public function filter(array $rawData): array
    {
        $fields = ["dashboard_id", "link_id"];
        return $this->inputSpec->filter($rawData, $fields);
    }

    #[\Override]
    public function validate(array $data): void
    {
        $fields = ["dashboard_id", "link_id"];
        $this->inputSpec->validate($data, $fields);
    }

    /** @param array{dashboard_id: string, link_id: string} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $this->favoriteService->removeFavorite(
            Uuid::fromString($data["dashboard_id"]),
            Uuid::fromString($data["link_id"]),
        );

        return [];
    }
}
