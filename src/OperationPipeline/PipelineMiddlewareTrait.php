<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationPipeline;

use jschreuder\BookmarkBureau\Exception\OperationPipelineException;

trait PipelineMiddlewareTrait
{
    abstract private function supports(object $data): bool;

    public function process(object $data, callable $next): object
    {
        if (!$this->supports($data)) {
            throw new OperationPipelineException(
                static::class .
                    " does not support objects of type " .
                    \get_class($data),
            );
        }

        return $this->doProcess($data, $next);
    }

    abstract private function doProcess(object $data, callable $next): object;
}
