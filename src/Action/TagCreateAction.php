<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;

/**
 * Expects the TagInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class TagCreateAction implements ActionInterface
{
    /** @param  OutputSpecInterface<Tag> $outputSpec */
    public function __construct(
        private TagServiceInterface $tagService,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec,
    ) {}

    #[\Override]
    public function filter(array $rawData): array
    {
        // Create operations need all fields
        return $this->inputSpec->filter($rawData);
    }

    #[\Override]
    public function validate(array $data): void
    {
        // Create operations need all fields
        $this->inputSpec->validate($data);
    }

    /** @param array{tag_name: string, color: ?string} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $tag = $this->tagService->createTag(
            tagName: $data["tag_name"],
            color: $data["color"],
        );
        return $this->outputSpec->transform($tag);
    }
}
