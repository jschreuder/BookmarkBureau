<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final class TagInputSpec implements InputSpecInterface
{
    private const array FIELDS = ["tag_name", "color"];

    #[\Override]
    public function getAvailableFields(): array
    {
        return self::FIELDS;
    }

    /** @return array{tag_name: string, color: ?string} */
    #[\Override]
    public function filter(array $rawData, ?array $fields = null): array
    {
        $filtered = [];
        $fields ??= $this->getAvailableFields();
        foreach ($fields as $field) {
            $filtered[$field] = match ($field) {
                "tag_name" => Filter::start($rawData, "tag_name", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->done(),
                "color" => Filter::start($rawData, "color", null)
                    ->string()
                    ->trim()
                    ->done(),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        /** @var array{tag_name: string, color: ?string} */
        return $filtered;
    }

    /** @param array{tag_name: string, color: ?string} $data */
    #[\Override]
    public function validate(array $data, ?array $fields = null): void
    {
        $validator = Validator::arrayType();
        $fields ??= $this->getAvailableFields();
        foreach ($fields as $field) {
            match ($field) {
                "tag_name" => $validator->key(
                    "tag_name",
                    Validator::notEmpty()->length(1, 256),
                ),
                "color" => $validator->key(
                    "color",
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
            throw new ValidationFailedException($exception->getMessages());
        }
    }
}
