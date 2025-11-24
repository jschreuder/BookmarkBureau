<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationPipeline;

use Closure;

/**
 * @template TInput of object|null
 * @template TOutput of object|null
 */
interface PipelineInterface
{
    /**
     * @param Closure(TInput): TOutput $operation
     * @param TInput $data
     * @phpstan-return TOutput
     */
    public function run(Closure $operation, ?object $data = null): ?object;
}
