<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\Middle\Exception\ValidationFailedException;

/**
 * Application-layer component for processing input and executing operations
 *
 * Defines a three-phase pattern:
 * 1. Filter - Transform raw input into clean data
 * 2. Validate - Check data against constraints
 * 3. Execute - Perform the business operation
 *
 * Actions are composable and can be used from any context:
 * HTTP controllers, CLI commands or background jobs.
 */
interface ActionInterface
{
    /**
     * Returns the attribute keys that are required to be added to the raw data
     * @return array<string> The attribute keys
     */
    public function getAttributeKeysForData(): array;

    /**
     * Filter/sanitize raw HTTP input
     * Should not error, invalid values should be nulled
     * @param array<string, mixed> $rawData
     * @return array<string, mixed>
     */
    public function filter(array $rawData): array;

    /**
     * Validate filtered data against constraints
     * @param array<string, mixed> $data
     * @throws ValidationFailedException When validation fails
     */
    public function validate(array $data): void;

    /**
     * Executes the action using the filtered & validated data
     * @param array<string, mixed> $data
     * @return array<string, mixed> that must be safe to be turned into JSON
     */
    public function execute(array $data): array;
}
