<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects the IdInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class CategoryReadAction implements ActionInterface
{
    public function __construct(
        private CategoryServiceInterface $categoryService,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec
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
        $categoryId = Uuid::fromString($data['id']);
        $category = $this->categoryService->getCategory($categoryId);
        return $this->outputSpec->transform($category);
    }
}
