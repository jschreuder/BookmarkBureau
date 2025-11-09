<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\JwtToken;
use jschreuder\BookmarkBureau\Entity\Value\TokenClaims;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Exception\InvalidTokenException;
use Psr\Clock\ClockInterface;

final readonly class LcobucciJwtService implements JwtServiceInterface
{
    public function __construct(
        private Configuration $jwtConfig,
        private int $sessionTtl,
        private int $rememberMeTtl,
        private ClockInterface $clock,
    ) {}

    #[\Override]
    public function generate(User $user, TokenType $tokenType): JwtToken
    {
        $now = $this->clock->now();

        $builder = $this->jwtConfig
            ->builder()
            ->relatedTo($user->userId->toString())
            ->withClaim("type", $tokenType->value)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now);

        if ($tokenType !== TokenType::CLI_TOKEN) {
            $builder = $builder->expiresAt(
                $this->getExpiresAt($tokenType, $now),
            );
        }

        $token = $builder->getToken(
            $this->jwtConfig->signer(),
            $this->jwtConfig->signingKey(),
        );

        return new JwtToken($token->toString());
    }

    #[\Override]
    public function verify(JwtToken $token): TokenClaims
    {
        try {
            /** @var Plain $parsedToken */
            $parsedToken = $this->jwtConfig
                ->parser()
                ->parse($token->getToken());

            $tokenType = TokenType::from($parsedToken->claims()->get("type"));

            // Build constraints: always verify signature, optionally verify expiry
            $constraints = [
                new SignedWith(
                    $this->jwtConfig->signer(),
                    $this->jwtConfig->verificationKey(),
                ),
            ];
            if ($tokenType !== TokenType::CLI_TOKEN) {
                $constraints[] = new StrictValidAt($this->clock);
            }

            // Validate all constraints
            $this->jwtConfig
                ->validator()
                ->assert($parsedToken, ...$constraints);

            // Extract claims
            $userId = \Ramsey\Uuid\Uuid::fromString(
                $parsedToken->claims()->get("sub"),
            );
            $issuedAt = DateTime::createFromFormat(
                "U",
                (string) $parsedToken->claims()->get("iat")->getTimestamp(),
                new DateTimeZone("UTC"),
            );

            if ($issuedAt === false) {
                throw new InvalidTokenException(
                    "Invalid token issued-at timestamp",
                );
            }

            $expiresAt = null;
            if ($tokenType !== TokenType::CLI_TOKEN) {
                $expClaim = $parsedToken->claims()->get("exp");
                if ($expClaim !== null) {
                    $expiresAt = DateTime::createFromFormat(
                        "U",
                        (string) $expClaim->getTimestamp(),
                        new DateTimeZone("UTC"),
                    );

                    if ($expiresAt === false) {
                        throw new InvalidTokenException(
                            "Invalid token expiry timestamp",
                        );
                    }
                }
            }

            return new TokenClaims($userId, $tokenType, $issuedAt, $expiresAt);
        } catch (InvalidTokenException $e) {
            throw $e;
        } catch (InvalidTokenStructure | UnsupportedHeaderFound $e) {
            throw new InvalidTokenException(
                "Invalid token format: " . $e->getMessage(),
                0,
                $e,
            );
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
        $now = $this->clock->now();

        $builder = $this->jwtConfig
            ->builder()
            ->relatedTo($userId->toString())
            ->withClaim("type", $tokenType->value)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now);

        if ($tokenType !== TokenType::CLI_TOKEN) {
            $builder = $builder->expiresAt(
                $this->getExpiresAt($tokenType, $now),
            );
        }

        $token = $builder->getToken(
            $this->jwtConfig->signer(),
            $this->jwtConfig->signingKey(),
        );

        return new JwtToken($token->toString());
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
