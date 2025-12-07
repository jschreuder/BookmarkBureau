<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Entity\Tag;
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
    public function getAttributeKeysForData(): array
    {
        return ["tag_name"];
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

    /** @param array{tag_name: string} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $tag = $this->tagService->getTag($data["tag_name"]);
        return $this->outputSpec->transform($tag);
    }
}
