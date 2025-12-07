<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects the IdInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class CategoryDeleteAction implements ActionInterface
{
    public function __construct(
        private CategoryServiceInterface $categoryService,
        private InputSpecInterface $inputSpec,
    ) {}

    #[\Override]
    public function getAttributeKeysForData(): array
    {
        return ["category_id"];
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

    /** @param array{category_id: string} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $categoryId = Uuid::fromString($data["category_id"]);
        $this->categoryService->deleteCategory($categoryId);

        return [];
    }
}
