<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final class CategoryInputSpec implements InputSpecInterface
{
    private const array FIELDS = [
        "category_id",
        "dashboard_id",
        "title",
        "color",
        "sort_order",
    ];

    #[\Override]
    public function getAvailableFields(): array
    {
        return self::FIELDS;
    }

    /** @return array{category_id: string, dashboard_id: string, title: string, color: ?string, sort_order: int} */
    #[\Override]
    public function filter(array $rawData, ?array $fields = null): array
    {
        $filtered = [];
        $fields ??= $this->getAvailableFields();
        foreach ($fields as $field) {
            $filtered[$field] = match ($field) {
                "category_id" => Filter::start($rawData, "category_id", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->done(),
                "dashboard_id" => Filter::start($rawData, "dashboard_id", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->done(),
                "title" => Filter::start($rawData, "title", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->striptags()
                    ->done(),
                "color" => Filter::start($rawData, "color", null)
                    ->string()
                    ->trim()
                    ->done(),
                "sort_order" => Filter::start($rawData, "sort_order", 1)
                    ->int(allowNull: false)
                    ->done(),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        /** @var array{category_id: string, dashboard_id: string, title: string, color: ?string, sort_order: int} */
        return $filtered;
    }

    /** @param array{category_id: string, dashboard_id: string, title: string, color: ?string, sort_order: int} $data */
    #[\Override]
    public function validate(array $data, ?array $fields = null): void
    {
        $validator = Validator::arrayType();
        $fields ??= $this->getAvailableFields();
        foreach ($fields as $field) {
            match ($field) {
                "category_id" => $validator->key(
                    "category_id",
                    Validator::notEmpty()->uuid(),
                ),
                "dashboard_id" => $validator->key(
                    "dashboard_id",
                    Validator::notEmpty()->uuid(),
                ),
                "title" => $validator->key(
                    "title",
                    Validator::notEmpty()->length(1, 256),
                ),
                "color" => $validator->key(
                    "color",
                    Validator::optional(Validator::stringType()->hexRgbColor()),
                ),
                "sort_order" => $validator->key(
                    "sort_order",
                    Validator::optional(Validator::intType()),
                ),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        try {
            $validator->assert($data);
        } catch (NestedValidationException $exception) {
            // Get all error messages as an array
            throw new ValidationFailedException($exception->getMessages());
        }
    }
}
