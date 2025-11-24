<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Exception\TagNotFoundException;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;

/**
 * Expects the TagNameInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class TagReadAction implements ActionInterface
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
        // Note: TagService doesn't have a getTag method, but we can list all tags
        // For now, returning the tag by searching with the exact tag name
        // This might need adjustment based on how tag retrieval is intended to work
        $tags = $this->tagService->listAllTags();
        foreach ($tags as $tag) {
            if ($tag->tagName->value === $data["tag_name"]) {
                return $this->outputSpec->transform($tag);
            }
        }
        // If tag not found, let the service throw TagNotFoundException
        // We'll need to add a getTag method to TagService or handle this differently
        throw TagNotFoundException::forName(new TagName($data["tag_name"]));
    }
}
