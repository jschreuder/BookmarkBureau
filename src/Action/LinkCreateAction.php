<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;

/**
 * Expects the LinkInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class LinkCreateAction implements ActionInterface
{
    public function __construct(
        private LinkServiceInterface $linkService,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec,
    ) {}

    #[\Override]
    public function filter(array $rawData): array
    {
        // Create operations need all fields except 'id', since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), ["id"]);
        return $this->inputSpec->filter($rawData, $fields);
    }

    #[\Override]
    public function validate(array $data): void
    {
        // Create operations need all fields except 'id', since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), ["id"]);
        $this->inputSpec->validate($data, $fields);
    }

    #[\Override]
    public function execute(array $data): array
    {
        $link = $this->linkService->createLink(
            url: $data["url"],
            title: $data["title"],
            description: $data["description"],
            icon: $data["icon"],
        );
        return $this->outputSpec->transform($link);
    }
}
