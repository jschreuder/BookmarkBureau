<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

use jschreuder\BookmarkBureau\Entity\Value\TokenResponse;

final readonly class TokenOutputSpec implements OutputSpecInterface
{
    use OutputSpecTrait;

    #[\Override]
    public function supports(object $data): bool
    {
        return $data instanceof TokenResponse;
    }

    private function doTransform(object $data): array
    {
        /** @var TokenResponse $data */
        return [
            "token" => (string) $data->token,
            "type" => $data->type,
            "expires_at" => $data->expiresAt?->format("c"),
        ];
    }
}
