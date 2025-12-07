<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects the IdInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class LinkDeleteAction implements ActionInterface
{
    public function __construct(
        private LinkServiceInterface $linkService,
        private InputSpecInterface $inputSpec,
    ) {}

    #[\Override]
    public function getAttributeKeysForData(): array
    {
        return ["link_id"];
    }

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

    /** @param array{link_id: string} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $linkId = Uuid::fromString($data["link_id"]);
        $this->linkService->deleteLink($linkId);

        return [];
    }
}
