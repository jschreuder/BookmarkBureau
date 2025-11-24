<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final class GenerateCliTokenInputSpec implements InputSpecInterface
{
    private const array FIELDS = ["email", "password"];

    #[\Override]
    public function getAvailableFields(): array
    {
        return self::FIELDS;
    }

    /** @return array{email: string, password: string} */
    #[\Override]
    public function filter(array $rawData, ?array $fields = null): array
    {
        $filtered = [];
        $fields ??= $this->getAvailableFields();

        foreach ($fields as $field) {
            $filtered[$field] = match ($field) {
                "email" => Filter::start($rawData, "email", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->lowercase()
                    ->done(),
                "password" => Filter::start($rawData, "password", "")
                    ->string(allowNull: false)
                    ->done(),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        /** @var array{email: string, password: string} */
        return $filtered;
    }

    /** @param array{email: string, password: string} $data */
    #[\Override]
    public function validate(array $data, ?array $fields = null): void
    {
        $validator = Validator::arrayType();
        $fields ??= $this->getAvailableFields();
        foreach ($fields as $field) {
            match ($field) {
                "email" => $validator->key("email", Validator::email()),
                "password" => $validator->key(
                    "password",
                    Validator::stringType()->notEmpty(),
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
