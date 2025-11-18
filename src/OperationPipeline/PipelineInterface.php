<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationPipeline;

use Closure;

interface PipelineInterface
{
    public function run(Closure $operation, ?object $data = null): ?object;
}
