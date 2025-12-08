<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final class ReorderCategoriesInputSpec implements InputSpecInterface
{
    private const array FIELDS = ["dashboard_id", "categories"];

    #[\Override]
    public function getAvailableFields(): array
    {
        return self::FIELDS;
    }

    /** @return array{dashboard_id: string, categories: array<int, array{category_id: string, sort_order: int}>} */
    #[\Override]
    public function filter(array $rawData, ?array $fields = null): array
    {
        $filtered = [];
        $fields ??= $this->getAvailableFields();
        foreach ($fields as $field) {
            $filtered[$field] = match ($field) {
                "dashboard_id" => Filter::start($rawData, "dashboard_id", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->done(),
                "categories" => (function () use ($rawData): array {
                    $categories = Filter::start($rawData, "categories", [])
                        ->do(fn($val) => \is_array($val) ? $val : [])
                        ->done();
                    /** @var array<int, mixed> $categories */
                    return $this->filterCategories($categories);
                })(),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        /** @var array{dashboard_id: string, categories: array<int, array{category_id: string, sort_order: int}>} */
        return $filtered;
    }

    /**
     * @param array<int, mixed> $categories
     * @return array<int, array{category_id: string, sort_order: int}>
     */
    private function filterCategories(array $categories): array
    {
        $filtered = [];
        foreach ($categories as $category) {
            if (!\is_array($category)) {
                continue;
            }
            /** @var array<string, mixed> $category */
            $filtered[] = [
                "category_id" => Filter::start($category, "category_id", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->done(),
                "sort_order" => Filter::start($category, "sort_order", 1)
                    ->int(allowNull: false)
                    ->done(),
            ];
        }

        /** @var array<int, array{category_id: string, sort_order: int}> */
        return $filtered;
    }

    /** @param array{dashboard_id: string, categories: array<int, array{category_id: string, sort_order: int}>} $data */
    #[\Override]
    public function validate(array $data, ?array $fields = null): void
    {
        $validator = Validator::arrayType();
        $fields ??= $this->getAvailableFields();
        foreach ($fields as $field) {
            match ($field) {
                "dashboard_id" => $validator->key(
                    "dashboard_id",
                    Validator::notEmpty()->uuid(),
                ),
                "categories" => $validator->key(
                    "categories",
                    Validator::arrayType()->notEmpty(),
                ),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        try {
            $validator->assert($data);
        } catch (NestedValidationException $exception) {
            throw new ValidationFailedException($exception->getMessages());
        }

        // Validate each category entry
        $categories = $data["categories"];
        if (empty($categories)) {
            throw new ValidationFailedException([
                "categories" => "Categories array must not be empty",
            ]);
        }

        $categoryValidator = Validator::each(
            Validator::arrayType()
                ->key("category_id", Validator::notEmpty()->uuid())
                ->key("sort_order", Validator::intType()->positive()),
        );

        try {
            $categoryValidator->assert($categories);
        } catch (NestedValidationException $exception) {
            throw new ValidationFailedException($exception->getMessages());
        }
    }
}
