<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use DateTimeInterface;
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
    public function __construct(
        private CategoryServiceInterface $categoryService,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec
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
        return $this->outputSpec->transform($category);
    }
}
