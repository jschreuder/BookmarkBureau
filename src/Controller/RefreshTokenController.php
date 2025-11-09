<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller;

use jschreuder\BookmarkBureau\Entity\Value\TokenResponse;
use jschreuder\BookmarkBureau\OutputSpec\TokenOutputSpec;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Exception\AuthenticationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RefreshTokenController implements ControllerInterface
{
    public function __construct(
        private JwtServiceInterface $jwtService,
        private TokenOutputSpec $tokenOutputSpec,
        private \jschreuder\BookmarkBureau\Response\ResponseTransformerInterface $responseTransformer,
    ) {}

    #[\Override]
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        // Get authenticated user from request (set by JwtAuthenticationMiddleware)
        $user = $request->getAttribute("authenticatedUser");
        $tokenClaims = $request->getAttribute("tokenClaims");

        if ($user === null || $tokenClaims === null) {
            throw new AuthenticationException("Authentication required");
        }

        // Refresh the token with the same type
        $newToken = $this->jwtService->refresh($tokenClaims);
        $newClaims = $this->jwtService->verify($newToken);

        $tokenResponse = new TokenResponse(
            $newToken,
            $tokenClaims->getTokenType()->value,
            $newClaims->getExpiresAt(),
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
