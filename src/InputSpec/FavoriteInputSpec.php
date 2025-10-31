<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final class FavoriteInputSpec implements InputSpecInterface
{
    private const FIELDS = [
        'dashboard_id',
        'link_id',
        'sort_order',
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
            $filtered[$field] = match($field) {
                'dashboard_id' => Filter::start($rawData, 'dashboard_id', '')
                    ->string(allowNull: false)->trim()->done(),
                'link_id' => Filter::start($rawData, 'link_id', '')
                    ->string(allowNull: false)->trim()->done(),
                'sort_order' => Filter::start($rawData, 'sort_order', null)
                    ->int()->done(),
                default => throw new InvalidArgumentException("Unknown field: {$field}"),
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
                'dashboard_id' => $validator->key('dashboard_id', Validator::notEmpty()->uuid()),
                'link_id' => $validator->key('link_id', Validator::notEmpty()->uuid()),
                'sort_order' => $validator->key('sort_order', Validator::optional(Validator::intType())),
                default => throw new InvalidArgumentException("Unknown field: {$field}"),
            };
        }

        try {
            $validator->assert($data);
        } catch (NestedValidationException $exception) {
            throw new ValidationFailedException($exception->getMessages());
        }
    }
}
