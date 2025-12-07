<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;

/**
 * Expects the TagNameInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class TagDeleteAction implements ActionInterface
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

    /** @param array{id: string} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $this->tagService->deleteTag($data["id"]);

        return [];
    }
}
