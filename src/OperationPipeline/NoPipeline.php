<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationPipeline;

use Closure;

/**
 * Null-object pattern implementation of PipelineInterface.
 *
 * This pipeline does not apply any middlewares and executes the operation directly.
 * Use this when you don't need any middleware processing, avoiding the overhead
 * of the full Pipeline implementation.
 */
final class NoPipeline implements PipelineInterface
{
    #[\Override]
    public function run(Closure $operation, ?object $data = null): ?object
    {
        return $operation($data);
    }
}
