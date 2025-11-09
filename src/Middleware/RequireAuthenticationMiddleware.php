<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Middleware;

use jschreuder\Middle\Exception\AuthenticationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        RequestHandlerInterface $requestHandler,
    ): ResponseInterface {
        $routeName = $request->getAttribute("route");

        // If route is public, allow through without authentication
        if (
            $routeName !== null &&
            \in_array($routeName, $this->publicRoutes, true)
        ) {
            return $requestHandler->handle($request);
        }

        // For protected routes, require authenticatedUser attribute
        if (!$request->getAttribute("authenticatedUserId")) {
            throw new AuthenticationException("Authentication required");
        }

        return $requestHandler->handle($request);
    }
}
