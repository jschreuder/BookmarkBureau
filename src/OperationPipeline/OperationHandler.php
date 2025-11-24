<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationPipeline;

use Closure;
use jschreuder\BookmarkBureau\Exception\OperationPipelineException;

/**
 * @template TInput of object|null
 * @template TOutput of object|null
 */
final class OperationHandler
{
    private bool $called = false;

    /**
     * @param array<PipelineMiddlewareInterface> $middlewares
     * @param int $currentIndex
     */
    public function __construct(
        private readonly array $middlewares,
        private readonly int $currentIndex = 0,
    ) {}

    /**
     * @param callable(TInput): TOutput $operation
     * @param TInput $data
     * @phpstan-return TOutput
     */
    public function handle(callable $operation, ?object $data = null): ?object
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
        /** @var self<TInput, TOutput> $nextHandler */
        $nextHandler = new self($this->middlewares, $this->currentIndex + 1);

        /** @var TOutput $result */
        $result = $middleware->process(
            $data,
            fn($d) => $nextHandler->handle($operation, $d),
        );
        return $result;
    }
}
