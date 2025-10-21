<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller\Action;

/**
 * Specification for HTTP request processing
 * Defines how to filter and validate incoming request data
 */
interface ActionInterface
{
    /**
     * Filter/sanitize raw HTTP input
     * Should not error, invalid values should be nulled
     */
    public function filter(array $rawData): array;

    /**
     * Validate filtered data against constraints
     * @throws ValidationFailedException When validation fails
     */
    public function validate(array $data): void;

    /**
     * Executes the action using the filtered & validated data
     * @return array that is safe to be turned into JSON
     */
    public function execute(array $data): array;
}
