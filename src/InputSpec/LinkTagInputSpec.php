<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final class LinkTagInputSpec implements InputSpecInterface
{
    private const array FIELDS = ["link_id", "tag_name"];

    #[\Override]
    public function getAvailableFields(): array
    {
        return self::FIELDS;
    }

    /** @return array{link_id: string, tag_name: string} */
    #[\Override]
    public function filter(array $rawData, ?array $fields = null): array
    {
        $filtered = [];
        $fields ??= $this->getAvailableFields();
        foreach ($fields as $field) {
            $filtered[$field] = match ($field) {
                "link_id" => Filter::start($rawData, "link_id", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->done(),
                "tag_name" => Filter::start($rawData, "tag_name", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->done(),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        /** @var array{link_id: string, tag_name: string} */
        return $filtered;
    }

    /** @param array{link_id: string, tag_name: string} $data */
    #[\Override]
    public function validate(array $data, ?array $fields = null): void
    {
        $validator = Validator::arrayType();
        $fields ??= $this->getAvailableFields();
        foreach ($fields as $field) {
            match ($field) {
                "link_id" => $validator->key(
                    "link_id",
                    Validator::notEmpty()->uuid(),
                ),
                "tag_name" => $validator->key(
                    "tag_name",
                    Validator::notEmpty()->length(1, 256),
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
