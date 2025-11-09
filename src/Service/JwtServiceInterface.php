<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\JwtToken;
use jschreuder\BookmarkBureau\Entity\Value\TokenClaims;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Exception\InvalidTokenException;

interface JwtServiceInterface
{
    /**
     * Generate a JWT token for the given user with the specified token type.
     *
     * @throws \Exception If token generation fails
     */
    public function generate(User $user, TokenType $tokenType): JwtToken;

    /**
     * Verify and decode a JWT token.
     *
     * @throws InvalidTokenException If token is invalid or expired
     */
    public function verify(JwtToken $token): TokenClaims;

    /**
     * Create a new token from existing claims (refresh operation).
     *
     * @throws InvalidTokenException If claims are invalid
     */
    public function refresh(TokenClaims $claims): JwtToken;
}
