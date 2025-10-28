<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\InputSpec;

use jschreuder\Middle\Exception\ValidationFailedException;

/**
 * Specification for filtering, and validating arrays from HTTP Requests
 *
 * InputSpecs mirror OutputSpecs, providing symmetry in the data flow:
 * - InputSpec:  array parsed from HTTP Request → validated array → domain
 * - OutputSpec: domain → array ready for HTTP Response
 *
 * Functionally, InputSpecs contain definitions of filters and validators
 * that are used to clean the data. They should only handle the fields as
 * defined, or as specified when only partial filtering/validation is needed.
 */
interface InputSpecInterface
{
    /** @return string[] */
    public function getAvailableFields(): array;

    /** @return array the raw data after filtering */
    public function filter(array $rawData, ?array $fields = null): array;

    /** @throws ValidationFailedException */
    public function validate(array $data, ?array $fields = null): void;
}
