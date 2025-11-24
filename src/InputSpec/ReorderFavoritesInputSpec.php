<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final class ReorderFavoritesInputSpec implements InputSpecInterface
{
    private const array FIELDS = ["dashboard_id", "links"];

    #[\Override]
    public function getAvailableFields(): array
    {
        return self::FIELDS;
    }

    /** @return array{dashboard_id: string, links: array<int, array{link_id: string, sort_order: int}>} */
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
                "links" => (function () use ($rawData): array {
                    $links = Filter::start($rawData, "links", [])
                        ->do(fn($val) => \is_array($val) ? $val : [])
                        ->done();
                    /** @var array<int, mixed> $links */
                    return $this->filterLinks($links);
                })(),
                default => throw new InvalidArgumentException(
                    "Unknown field: {$field}",
                ),
            };
        }

        /** @var array{dashboard_id: string, links: array<int, array{link_id: string, sort_order: int}>} */
        return $filtered;
    }

    /**
     * @param array<int, mixed> $links
     * @return array<int, array{link_id: string, sort_order: int}>
     */
    private function filterLinks(array $links): array
    {
        $filtered = [];
        foreach ($links as $link) {
            if (!\is_array($link)) {
                continue;
            }
            /** @var array<string, mixed> $link */
            $filtered[] = [
                "link_id" => Filter::start($link, "link_id", "")
                    ->string(allowNull: false)
                    ->trim()
                    ->done(),
                "sort_order" => Filter::start($link, "sort_order", 1)
                    ->int(allowNull: false)
                    ->done(),
            ];
        }

        /** @var array<int, array{link_id: string, sort_order: int}> */
        return $filtered;
    }

    /** @param array{dashboard_id: string, links: array<int, array{link_id: string, sort_order: int}>} $data */
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
                "links" => $validator->key(
                    "links",
                    Validator::arrayType()->notEmpty(),
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

        // Validate each link entry
        $links = $data["links"];
        if (empty($links)) {
            throw new ValidationFailedException([
                "links" => "Links array must not be empty",
            ]);
        }

        $linkValidator = Validator::each(
            Validator::arrayType()
                ->key("link_id", Validator::notEmpty()->uuid())
                ->key("sort_order", Validator::intType()->positive()),
        );

        try {
            $linkValidator->assert($links);
        } catch (NestedValidationException $exception) {
            throw new ValidationFailedException($exception->getMessages());
        }
    }
}
