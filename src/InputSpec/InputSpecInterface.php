<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use jschreuder\Middle\Exception\ValidationFailedException;

interface InputSpecInterface
{
    /** @return string[] */
    public function getAvailableFields(): array;

    /** @return array the raw data after filtering */
    public function filter(array $rawData, ?array $fields = null): array;

    /** @throws ValidationFailedException */
    public function validate(array $data, ?array $fields = null): void;
}
