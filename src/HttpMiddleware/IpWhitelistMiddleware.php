<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\HttpMiddleware;

use jschreuder\BookmarkBureau\Util\IpAddress;
use jschreuder\Middle\Exception\AuthorizationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UnexpectedValueException;

/**
 * Middleware to restrict access based on client IP address.
 *
 * Useful for restricting admin operations to specific IP ranges (e.g., local network only).
 * Public routes (e.g., login) are automatically exempted from IP whitelist checks.
 */
final readonly class IpWhitelistMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string> $allowedIpRanges Array of allowed IPs or CIDR ranges (e.g., ["192.168.1.0/24", "10.0.0.5"])
     * @param array<string> $publicRoutes Route names that are exempt from IP whitelist (same as auth public routes)
     * @param bool $trustProxyHeaders Whether to trust X-Forwarded-For headers for client IP
     */
    public function __construct(
        private array $allowedIpRanges,
        private array $publicRoutes,
        private bool $trustProxyHeaders = false,
    ) {}

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

        // If route is public, allow through without IP check
        if (
            $routeName !== null &&
            \in_array($routeName, $this->publicRoutes, true)
        ) {
            return $handler->handle($request);
        }

        // If no whitelist configured, allow all (whitelist is optional)
        if (empty($this->allowedIpRanges)) {
            return $handler->handle($request);
        }

        $clientIp = IpAddress::fromRequest($request, $this->trustProxyHeaders);

        // Check if client IP matches any allowed range
        if (IpAddress::matchesAnyRange($clientIp, $this->allowedIpRanges)) {
            return $handler->handle($request);
        }

        // IP not in whitelist - deny access
        throw new AuthorizationException(
            "Access denied: IP address {$clientIp} not in whitelist",
        );
    }
}
