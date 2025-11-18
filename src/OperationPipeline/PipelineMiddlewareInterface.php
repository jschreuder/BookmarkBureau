<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationPipeline;

interface PipelineMiddlewareInterface
{
    public function process(object $data, callable $next): object;
}
