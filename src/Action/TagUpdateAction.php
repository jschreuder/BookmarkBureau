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
final readonly class TagUpdateAction implements ActionInterface
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
        // Update operations can have tag_name and/or color
        return $this->inputSpec->filter($rawData);
    }

    #[\Override]
    public function validate(array $data): void
    {
        // Update operations can have tag_name and/or color
        $this->inputSpec->validate($data);
    }

    /** @param array{id: string, color: ?string} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $tag = $this->tagService->updateTag(
            tagName: $data["id"],
            color: $data["color"],
        );
        return $this->outputSpec->transform($tag);
    }
}
