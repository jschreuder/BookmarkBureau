<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

/**
 * Generic input spec for operations that only require an ID
 * Useful for delete operations and simple lookups
 */
final class IdInputSpec implements InputSpecInterface
{
    private const array FIELDS = ["id"];

    #[\Override]
    public function getAvailableFields(): array
    {
        return self::FIELDS;
    }

    #[\Override]
    public function filter(array $rawData, ?array $fields = null): array
    {
        $filtered = [];
        $fields ??= $this->getAvailableFields();

        foreach ($fields as $field) {
            $filtered[$field] = match ($field) {
                "id" => Filter::start($rawData, "id", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->done(),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        return $filtered;
    }

    #[\Override]
    public function validate(array $data, ?array $fields = null): void
    {
        $validator = Validator::arrayType();
        $fields ??= $this->getAvailableFields();

        foreach ($fields as $field) {
            $validator = match ($field) {
                "id" => $validator->key("id", Validator::notEmpty()->uuid()),
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
    }
}
