<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final class CategoryLinkInputSpec implements InputSpecInterface
{
    private const array FIELDS = ["category_id", "link_id"];

    #[\Override]
    public function getAvailableFields(): array
    {
        return self::FIELDS;
    }

    /** @return array{category_id: string, link_id: string} */
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
                "link_id" => Filter::start($rawData, "link_id", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->done(),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        /** @var array{category_id: string, link_id: string} */
        return $filtered;
    }

    /** @param array{category_id: string, link_id: string} $data */
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
                "link_id" => $validator->key(
                    "link_id",
                    Validator::notEmpty()->uuid(),
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
