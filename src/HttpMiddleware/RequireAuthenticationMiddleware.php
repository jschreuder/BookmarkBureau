<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\HttpMiddleware;

use jschreuder\Middle\Exception\AuthenticationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UnexpectedValueException;

final readonly class RequireAuthenticationMiddleware implements
    MiddlewareInterface
{
    /**
     * @param array<string> $publicRoutes Route names that don't require authentication
     */
    public function __construct(private array $publicRoutes) {}

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $routeName = $request->getAttribute("route");
        if (!\is_string($routeName) && $routeName !== null) {
            throw new UnexpectedValueException(
                "Routename in request must be a string or null.",
            );
        }

        // If route is public, allow through without authentication
        if (
            $routeName !== null &&
            \in_array($routeName, $this->publicRoutes, true)
        ) {
            return $handler->handle($request);
        }

        // For protected routes, require authenticatedUser attribute
        if (
            $routeName !== null &&
            !$request->getAttribute("authenticatedUserId")
        ) {
            throw new AuthenticationException(
                "Authentication required: {$routeName}",
            );
        }

        return $handler->handle($request);
    }
}
