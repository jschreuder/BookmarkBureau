<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final class LoginInputSpec implements InputSpecInterface
{
    private const array FIELDS = [
        "email",
        "password",
        "remember_me",
        "totp_code",
    ];

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
                "email" => Filter::start($rawData, "email", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->lowercase()
                    ->done(),
                "password" => Filter::start($rawData, "password", "")
                    ->string(allowNull: false)
                    ->done(),
                "remember_me" => Filter::start($rawData, "remember_me", false)
                    ->bool()
                    ->done(),
                "totp_code" => Filter::start($rawData, "totp_code", "")
                    ->string(allowNull: true)
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
            match ($field) {
                "email" => $validator->key("email", Validator::email()),
                "password" => $validator->key(
                    "password",
                    Validator::stringType()->notEmpty(),
                ),
                "remember_me" => $validator->key(
                    "remember_me",
                    Validator::optional(Validator::boolType()),
                ),
                "totp_code" => $validator->key(
                    "totp_code",
                    Validator::optional(
                        Validator::stringType()->length(6, 6)->digit(),
                    ),
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
