<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationPipeline;

use Closure;

/**
 * Pipeline composes middleware into a decorator chain.
 *
 * While the implementation uses the Decorator pattern (each middleware
 * wraps the next), we call it a Pipeline because the mental model is
 * "compose stages and run operations through them" rather than
 * "wrap the operation with decorators."
 *
 * Usage should look like this:
 *
 * $pipeline = new Pipeline(new Middleware1(), new Middleware2());
 * $result = $pipeline->run(fn($user) => register_user($user), $newUser);
 */
final readonly class Pipeline implements PipelineInterface
{
    /** @var array<PipelineMiddlewareInterface> */
    private array $middlewares;

    public function __construct(PipelineMiddlewareInterface ...$middlewares)
    {
        $this->middlewares = $middlewares;
    }

    public function withMiddleware(
        PipelineMiddlewareInterface $middleware,
    ): Pipeline {
        $newMiddlewares = $this->middlewares;
        array_push($newMiddlewares, $middleware);
        return new self(...$newMiddlewares);
    }

    #[\Override]
    public function run(Closure $operation, ?object $data = null): ?object
    {
        if (\count($this->middlewares) === 0) {
            return $operation($data);
        }

        $handler = new OperationHandler($this->middlewares);
        return $handler->handle($operation, $data);
    }
}
