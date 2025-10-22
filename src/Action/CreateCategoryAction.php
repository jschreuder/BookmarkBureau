<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use DateTimeInterface;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects the CategoryInputSpec, but it can be replaced to modify filtering
 * and validation.
 */
final readonly class CreateCategoryAction implements ActionInterface
{
    public function __construct(
        private CategoryServiceInterface $categoryService,
        private InputSpecInterface $inputSpec
    ) {}

    public function filter(array $rawData): array
    {
        // Create operations need all fields except 'id', since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), ['id']);
        return $this->inputSpec->filter($rawData, $fields);
    }

    public function validate(array $data): void
    {
        // Create operations need all fields except 'id', since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), ['id']);
        $this->inputSpec->validate($data, $fields);
    }

    public function execute(array $data): array
    {
        $dashboardId = Uuid::fromString($data['dashboard_id']);
        $category = $this->categoryService->createCategory(
            dashboardId: $dashboardId,
            title: $data['title'],
            color: $data['color']
        );
        return [
            'id' => $category->categoryId->toString(),
            'dashboard_id' => $category->dashboard->dashboardId->toString(),
            'title' => $category->title->value,
            'color' => $category->color?->value,
            'sort_order' => $category->sortOrder,
            'created_at' => $category->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $category->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
