<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Composite\CategoryCollection;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Handles bulk reordering of categories within a dashboard via PUT request.
 * Expects ReorderCategoryInputSpec, but it can be replaced to modify filtering and validation.
 */
final readonly class CategoryReorderAction implements ActionInterface
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
        return ["dashboard_id"];
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

    /** @param array{dashboard_id: string, categories: array<int, array{category_id: string, sort_order: int}>} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $dashboardId = Uuid::fromString($data["dashboard_id"]);

        // Get current categories and create a map by link_id for quick lookup
        $currentCategories = $this->categoryService->getCategoriesForDashboard(
            $dashboardId,
        );
        $categoriesMap = [];
        foreach ($currentCategories as $category) {
            $categoriesMap[$category->categoryId->toString()] = $category;
        }

        // Collect valid favorites with their sort_order, throw if link not favorited
        $validCategories = [];
        foreach ($data["categories"] as $categoryData) {
            $categoryId = $categoryData["category_id"];
            if (!isset($categoriesMap[$categoryId])) {
                throw CategoryNotFoundException::forId(
                    Uuid::fromString($categoryId),
                );
            }
            $validCategories[] = [
                "category" => $categoriesMap[$categoryId],
                "sort_order" => $categoryData["sort_order"],
            ];
        }

        // Sort categories by sort_order
        usort(
            $validCategories,
            fn($a, $b) => $a["sort_order"] <=> $b["sort_order"],
        );

        // Extract just the category objects in the sorted order
        $reorderedCategories = array_map(
            fn($item) => $item["category"],
            $validCategories,
        );

        // Reorder the categories
        $this->categoryService->reorderCategories(
            $dashboardId,
            new CategoryCollection(...$reorderedCategories),
        );

        // Transform each category to array
        return [
            "categories" => array_map(
                $this->outputSpec->transform(...),
                $reorderedCategories,
            ),
        ];
    }
}
