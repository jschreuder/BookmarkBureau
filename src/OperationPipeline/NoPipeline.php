<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationPipeline;

use Closure;

/**
 * Null-object pattern implementation of PipelineInterface.
 *
 * This pipeline does not apply any middlewares and executes the operation directly.
 * Use this when you don't need any middleware processing, avoiding the overhead
 * of the full Pipeline implementation.
 *
 * @template TInput of object|null
 * @template TOutput of object|null
 * @implements PipelineInterface<TInput, TOutput>
 */
final class NoPipeline implements PipelineInterface
{
    /**
     * @param Closure(TInput): TOutput $operation
     * @param TInput $data
     * @phpstan-return TOutput
     */
    #[\Override]
    public function run(Closure $operation, ?object $data = null): ?object
    {
        return $operation($data);
    }
}
