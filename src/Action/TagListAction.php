<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;

/**
 * Expects the TagNameInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class TagListAction implements ActionInterface
{
    /** @param  OutputSpecInterface<Tag> $outputSpec */
    public function __construct(
        private TagServiceInterface $tagService,
        private OutputSpecInterface $outputSpec,
    ) {}

    #[\Override]
    public function getAttributeKeysForData(): array
    {
        return [];
    }

    #[\Override]
    public function filter(array $rawData): array
    {
        // No input parameters needed for listing all dashboards
        return [];
    }

    #[\Override]
    public function validate(array $data): void
    {
        // No validation needed for listing all dashboards
    }

    /** @param array{tag_name: string} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $tags = $this->tagService->getAllTags();

        $result = [];
        foreach ($tags as $tag) {
            $result[] = $this->outputSpec->transform($tag);
        }

        return ["tags" => $result];
    }
}
