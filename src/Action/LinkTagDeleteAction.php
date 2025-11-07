<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects an InputSpec that validates link_id and tag_name, but it can be replaced
 * to modify filtering and validation.
 */
final readonly class LinkTagDeleteAction implements ActionInterface
{
    public function __construct(
        private TagServiceInterface $tagService,
        private InputSpecInterface $inputSpec,
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
        $this->tagService->removeTagFromLink(
            linkId: Uuid::fromString($data["id"]),
            tagName: $data["tag_name"],
        );

        return [];
    }
}
