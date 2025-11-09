<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Firebase\JWT\JWT;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\JwtToken;
use jschreuder\BookmarkBureau\Entity\Value\TokenClaims;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Exception\InvalidTokenException;
use Psr\Clock\ClockInterface;

final readonly class JwtService implements JwtServiceInterface
{
    public function __construct(
        private string $jwtSecret,
        private int $sessionTtl,
        private int $rememberMeTtl,
        private ClockInterface $clock,
    ) {}

    #[\Override]
    public function generate(User $user, TokenType $tokenType): JwtToken
    {
        $now = $this->clock->now();
        $issuedAt = $now->getTimestamp();

        $payload = [
            "sub" => $user->userId->toString(),
            "type" => $tokenType->value,
            "iat" => $issuedAt,
        ];

        if ($tokenType !== TokenType::CLI_TOKEN) {
            $payload["exp"] = $this->getExpiresAt(
                $tokenType,
                $now,
            )->getTimestamp();
        }

        $token = JWT::encode($payload, $this->jwtSecret, "HS256");

        return new JwtToken($token);
    }

    #[\Override]
    public function verify(JwtToken $token): TokenClaims
    {
        try {
            $now = $this->clock->now();
            $currentTimestamp = $now->getTimestamp();

            // Manually decode and verify signature without Firebase's expiry validation
            // This allows us to use our testable clock for expiry checks
            $parts = explode(".", $token->getToken());
            if (\count($parts) !== 3) {
                throw new InvalidTokenException("Invalid token format");
            }

            // Decode the payload (second part)
            $payload = json_decode(
                base64_decode(strtr($parts[1], "-_", "+/")),
                flags: JSON_THROW_ON_ERROR,
            );

            // Verify the signature manually
            $signature = $parts[2];
            $signatureData = hash_hmac(
                "sha256",
                "{$parts[0]}.{$parts[1]}",
                $this->jwtSecret,
                true,
            );
            $expectedSignature = rtrim(
                strtr(base64_encode($signatureData), "+/", "-_"),
                "=",
            );

            if ($signature !== $expectedSignature) {
                throw new InvalidTokenException("Invalid token signature");
            }

            $userId = \Ramsey\Uuid\Uuid::fromString($payload->sub);
            $tokenType = TokenType::from($payload->type);
            $issuedAt = DateTime::createFromFormat(
                "U",
                (string) $payload->iat,
                new DateTimeZone("UTC"),
            );
            $expiresAt =
                $tokenType === TokenType::CLI_TOKEN
                    ? null
                    : DateTime::createFromFormat(
                        "U",
                        (string) $payload->exp,
                        new DateTimeZone("UTC"),
                    );

            if (
                $issuedAt === false ||
                ($expiresAt === false && $tokenType !== TokenType::CLI_TOKEN)
            ) {
                throw new InvalidTokenException("Invalid token timestamps");
            }

            // Custom expiry check using our clock
            if (
                $expiresAt !== null &&
                $currentTimestamp >= $expiresAt->getTimestamp()
            ) {
                throw new InvalidTokenException("Token has expired");
            }

            return new TokenClaims($userId, $tokenType, $issuedAt, $expiresAt);
        } catch (InvalidTokenException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InvalidTokenException(
                "Failed to verify token: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    #[\Override]
    public function refresh(TokenClaims $claims): JwtToken
    {
        $userId = $claims->getUserId();
        $tokenType = $claims->getTokenType();

        // Create a temporary user with just the userId for token generation
        // We avoid loading the full user to keep this operation lightweight
        $now = $this->clock->now();
        $issuedAt = $now->getTimestamp();

        $payload = [
            "sub" => $userId->toString(),
            "type" => $tokenType->value,
            "iat" => $issuedAt,
        ];

        if ($tokenType !== TokenType::CLI_TOKEN) {
            $payload["exp"] = $this->getExpiresAt(
                $tokenType,
                $now,
            )->getTimestamp();
        }

        $token = JWT::encode($payload, $this->jwtSecret, "HS256");

        return new JwtToken($token);
    }

    private function getExpiresAt(
        TokenType $tokenType,
        DateTimeImmutable $now,
    ): DateTimeImmutable {
        return match ($tokenType) {
            TokenType::CLI_TOKEN => throw new \LogicException(
                "CLI tokens should not have an expiry",
            ),
            TokenType::SESSION_TOKEN => $now->modify(
                "+{$this->sessionTtl} seconds",
            ),
            TokenType::REMEMBER_ME_TOKEN => $now->modify(
                "+{$this->rememberMeTtl} seconds",
            ),
        };
    }
}
