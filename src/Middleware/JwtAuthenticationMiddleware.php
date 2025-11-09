<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Middleware;

use jschreuder\BookmarkBureau\Entity\Value\JwtToken;
use jschreuder\BookmarkBureau\Exception\InvalidTokenException;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\Middle\Exception\AuthenticationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class JwtAuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(private JwtServiceInterface $jwtService) {}

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $requestHandler,
    ): ResponseInterface {
        // Try to extract and verify JWT token from Authorization header
        $authHeader = $request->getHeaderLine("Authorization");

        if (!empty($authHeader)) {
            try {
                $token = $this->extractTokenFromHeader($authHeader);
                $claims = $this->jwtService->verify($token);

                // Attach authenticated user to request for controllers/actions
                $request = $request->withAttribute(
                    "authenticatedUserId",
                    $claims->getUserId(),
                );
                $request = $request->withAttribute("tokenClaims", $claims);
            } catch (InvalidTokenException $e) {
                // Token is invalid/expired, throw authentication exception
                throw new AuthenticationException(
                    "Invalid or expired token: " . $e->getMessage(),
                );
            } catch (\Throwable $e) {
                // User not found or other error
                throw new AuthenticationException(
                    "Authentication failed: " . $e->getMessage(),
                );
            }
        }

        // If no Authorization header or token is valid, continue
        // Routes can check for 'authenticatedUser' attribute if they require auth
        return $requestHandler->handle($request);
    }

    private function extractTokenFromHeader(string $authHeader): JwtToken
    {
        // Expected format: "Bearer <token>"
        $parts = explode(" ", trim($authHeader), 2);

        if (\count($parts) !== 2 || strtolower($parts[0]) !== "bearer") {
            throw new InvalidTokenException(
                "Invalid Authorization header format",
            );
        }

        return new JwtToken($parts[1]);
    }
}
