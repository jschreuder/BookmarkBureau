<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller;

use jschreuder\BookmarkBureau\Entity\Value\TokenResponse;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\OutputSpec\TokenOutputSpec;
use jschreuder\BookmarkBureau\Response\ResponseTransformerInterface;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Exception\AuthenticationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RefreshTokenController implements ControllerInterface
{
    public function __construct(
        private JwtServiceInterface $jwtService,
        private UserServiceInterface $userService,
        private TokenOutputSpec $tokenOutputSpec,
        private ResponseTransformerInterface $responseTransformer,
    ) {}

    #[\Override]
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        // Get authenticated user from request (set by JwtAuthenticationMiddleware)
        $userId = $request->getAttribute("authenticatedUserId");
        $tokenClaims = $request->getAttribute("tokenClaims");

        if ($userId === null || $tokenClaims === null) {
            throw new AuthenticationException("Authentication required");
        }

        try {
            // Fetch user to check if it exists, throws exception if not found
            $this->userService->getUser($userId);
        } catch (UserNotFoundException $e) {
            throw new AuthenticationException("Invalid user ID");
        }

        // Refresh the token with the same type
        $newToken = $this->jwtService->refresh($tokenClaims);
        $newClaims = $this->jwtService->verify($newToken);

        $tokenResponse = new TokenResponse(
            $newToken,
            $tokenClaims->tokenType->value,
            $newClaims->expiresAt,
        );

        return $this->responseTransformer->transform(
            data: [
                "success" => true,
                "data" => $this->tokenOutputSpec->transform($tokenResponse),
            ],
            statusCode: 200,
        );
    }
}
