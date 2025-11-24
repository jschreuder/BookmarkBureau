<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\JwtToken;
use jschreuder\BookmarkBureau\Entity\Value\TokenClaims;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Exception\InvalidTokenException;
use jschreuder\BookmarkBureau\Repository\JwtJtiRepositoryInterface;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class LcobucciJwtService implements JwtServiceInterface
{
    public function __construct(
        private Configuration $jwtConfig,
        private string $applicationName,
        private int $sessionTtl,
        private int $rememberMeTtl,
        private ClockInterface $clock,
        private JwtJtiRepositoryInterface $jwtJtiRepository,
    ) {
        if (empty($this->applicationName)) {
            throw new InvalidArgumentException(
                "Application name cannot be empty",
            );
        }
    }

    #[\Override]
    public function generate(User $user, TokenType $tokenType): JwtToken
    {
        $now = $this->clock->now();

        $builder = $this->jwtConfig
            ->builder()
            ->issuedBy($this->applicationName)
            ->permittedFor("{$this->applicationName}-api")
            ->relatedTo($user->userId->toString())
            ->withClaim("type", $tokenType->value)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now);

        if ($tokenType === TokenType::CLI_TOKEN) {
            // Generate and store JTI for CLI tokens
            $jti = Uuid::uuid4();
            $builder = $builder->identifiedBy($jti->toString());
            $this->jwtJtiRepository->saveJti($jti, $user->userId, $now);
        } else {
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
            $parsedToken = $this->jwtConfig->parser()->parse((string) $token);
            $tokenType = TokenType::from($parsedToken->claims()->get("type"));

            $this->validateTokenConstraints($parsedToken, $tokenType);

            $userId = Uuid::fromString($parsedToken->claims()->get("sub"));
            $issuedAt = $this->extractIssuedAt($parsedToken);
            [$expiresAt, $jti] = $this->extractTypeSpecificClaims(
                $parsedToken,
                $tokenType,
            );

            return new TokenClaims(
                $userId,
                $tokenType,
                $issuedAt,
                $expiresAt,
                $jti,
            );
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

    private function validateTokenConstraints(
        Plain $token,
        TokenType $tokenType,
    ): void {
        $constraints = [
            new SignedWith(
                $this->jwtConfig->signer(),
                $this->jwtConfig->verificationKey(),
            ),
            new IssuedBy($this->applicationName),
            new PermittedFor("{$this->applicationName}-api"),
        ];

        if ($tokenType !== TokenType::CLI_TOKEN) {
            $constraints[] = new StrictValidAt($this->clock);
        }

        $this->jwtConfig->validator()->assert($token, ...$constraints);
    }

    private function extractIssuedAt(Plain $token): DateTime
    {
        $issuedAt = DateTime::createFromFormat(
            "U",
            (string) $token->claims()->get("iat")->getTimestamp(),
            new DateTimeZone("UTC"),
        );

        if ($issuedAt === false) {
            throw new InvalidTokenException(
                "Invalid token issued-at timestamp",
            );
        }

        return $issuedAt;
    }

    /**
     * @return array{0: ?DateTime, 1: ?UuidInterface}
     */
    private function extractTypeSpecificClaims(
        Plain $token,
        TokenType $tokenType,
    ): array {
        if ($tokenType === TokenType::CLI_TOKEN) {
            return [null, $this->extractAndValidateCliJti($token)];
        }

        return [$this->extractExpiresAt($token), null];
    }

    private function extractAndValidateCliJti(Plain $token): UuidInterface
    {
        $jtiClaim = $token->claims()->get("jti");
        if ($jtiClaim === null) {
            throw new InvalidTokenException(
                "CLI token missing required JTI claim",
            );
        }

        $jti = Uuid::fromString((string) $jtiClaim);

        if (!$this->jwtJtiRepository->hasJti($jti)) {
            throw new InvalidTokenException(
                "CLI token JTI not in whitelist (revoked or invalid)",
            );
        }

        return $jti;
    }

    private function extractExpiresAt(Plain $token): ?DateTime
    {
        $expClaim = $token->claims()->get("exp");
        if ($expClaim === null) {
            return null;
        }

        $expiresAt = DateTime::createFromFormat(
            "U",
            (string) $expClaim->getTimestamp(),
            new DateTimeZone("UTC"),
        );

        if ($expiresAt === false) {
            throw new InvalidTokenException("Invalid token expiry timestamp");
        }

        return $expiresAt;
    }

    #[\Override]
    public function refresh(TokenClaims $claims): JwtToken
    {
        $userId = $claims->userId;
        $tokenType = $claims->tokenType;
        $now = $this->clock->now();

        $builder = $this->jwtConfig
            ->builder()
            ->issuedBy($this->applicationName)
            ->permittedFor("{$this->applicationName}-api")
            ->relatedTo($userId->toString())
            ->withClaim("type", $tokenType->value)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now);

        if ($tokenType === TokenType::CLI_TOKEN) {
            // Preserve the JTI for CLI tokens (same token identity)
            $jti = $claims->jti;
            if ($jti === null) {
                throw new InvalidTokenException(
                    "CLI token missing JTI for refresh",
                );
            }
            $builder = $builder->identifiedBy($jti->toString());
        } else {
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
