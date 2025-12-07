<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final class DashboardInputSpec implements InputSpecInterface
{
    private const array FIELDS = [
        "dashboard_id",
        "title",
        "description",
        "icon",
    ];

    #[\Override]
    public function getAvailableFields(): array
    {
        return self::FIELDS;
    }

    /** @return array{dashboard_id: string, title: string, description: string, icon: ?string} */
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
                "title" => Filter::start($rawData, "title", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->striptags()
                    ->done(),
                "description" => Filter::start($rawData, "description", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->striptags()
                    ->done(),
                "icon" => Filter::start($rawData, "icon", null)
                    ->string()
                    ->trim()
                    ->done(),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        /** @var array{dashboard_id: string, title: string, description: string, icon: ?string} */
        return $filtered;
    }

    /** @param array{dashboard_id: string, title: string, description: string, icon: ?string} $data */
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
                "title" => $validator->key(
                    "title",
                    Validator::notEmpty()->length(1, 256),
                ),
                "description" => $validator->key(
                    "description",
                    Validator::optional(Validator::stringType()),
                ),
                "icon" => $validator->key(
                    "icon",
                    Validator::optional(Validator::stringType()),
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
