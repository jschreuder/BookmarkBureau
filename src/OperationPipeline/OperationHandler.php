<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationPipeline;

use Closure;
use jschreuder\BookmarkBureau\Exception\OperationPipelineException;

final class OperationHandler
{
    private bool $called = false;

    public function __construct(
        private readonly array $middlewares,
        private readonly int $currentIndex = 0,
    ) {}

    public function handle(Closure $operation, ?object $data = null): ?object
    {
        if ($this->called) {
            throw new OperationPipelineException(
                "Handler already called, cannot process twice",
            );
        }
        $this->called = true;

        if ($this->currentIndex >= \count($this->middlewares)) {
            // All middlewares processed, execute the final operation
            return $operation($data);
        }
        $middleware = $this->middlewares[$this->currentIndex];
        $nextHandler = new self($this->middlewares, $this->currentIndex + 1);

        return $middleware->process(
            $data,
            fn($d) => $nextHandler->handle($operation, $d),
        );
    }
}
