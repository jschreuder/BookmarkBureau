<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

use jschreuder\BookmarkBureau\Entity\Value\TokenResponse;

/**
 * @implements OutputSpecInterface<TokenResponse>
 */
final readonly class TokenOutputSpec implements OutputSpecInterface
{
    /** @use OutputSpecTrait<TokenResponse> */
    use OutputSpecTrait;

    #[\Override]
    public function supports(object $data): bool
    {
        return $data instanceof TokenResponse;
    }

    #[\Override]
    private function doTransform(object $tokenResponse): array
    {
        return [
            "token" => (string) $tokenResponse->token,
            "type" => $tokenResponse->type,
            "expires_at" => $tokenResponse->expiresAt?->format("c"),
        ];
    }
}
