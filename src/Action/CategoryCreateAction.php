<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects the CategoryInputSpec, but it can be replaced to modify filtering
 * and validation.
 */
final readonly class CategoryCreateAction implements ActionInterface
{
    /** @param  OutputSpecInterface<Category> $outputSpec */
    public function __construct(
        private CategoryServiceInterface $categoryService,
        private InputSpecInterface $inputSpec,
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
        // Create operations need all fields except "id", since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), [
            "category_id",
        ]);
        return $this->inputSpec->filter($rawData, $fields);
    }

    #[\Override]
    public function validate(array $data): void
    {
        // Create operations need all fields except "id", since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), [
            "category_id",
        ]);
        $this->inputSpec->validate($data, $fields);
    }

    /** @param array{dashboard_id: string, title: string, color: ?string, sort_order: int} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $dashboardId = Uuid::fromString($data["dashboard_id"]);
        $category = $this->categoryService->createCategory(
            dashboardId: $dashboardId,
            title: $data["title"],
            color: $data["color"],
        );
        return $this->outputSpec->transform($category);
    }
}
