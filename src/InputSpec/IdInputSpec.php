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
    public function __construct(private string $idName = "id") {}

    #[\Override]
    public function getAvailableFields(): array
    {
        return [$this->idName];
    }

    /** @return non-empty-array<string, string> */
    #[\Override]
    public function filter(array $rawData, ?array $fields = null): array
    {
        $filtered = [];
        $fields ??= $this->getAvailableFields();

        foreach ($fields as $field) {
            $filtered[$field] = match ($field) {
                $this->idName => Filter::start($rawData, $this->idName, "")
                    ->string(allowNull: false)
                    ->trim()
                    ->done(),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        /** @var non-empty-array<string, string> */
        return $filtered;
    }

    /** @param non-empty-array<string, string> $data */
    #[\Override]
    public function validate(array $data, ?array $fields = null): void
    {
        $validator = Validator::arrayType();
        $fields ??= $this->getAvailableFields();

        foreach ($fields as $field) {
            $validator = match ($field) {
                $this->idName => $validator->key(
                    $this->idName,
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
