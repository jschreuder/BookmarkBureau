<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;

final readonly class PhpPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private string|int $algorithm = PASSWORD_ARGON2ID,
    ) {}

    #[\Override]
    public function hash(string $plaintext): HashedPassword
    {
        return new HashedPassword(password_hash($plaintext, $this->algorithm));
    }

    #[\Override]
    public function verify(string $plaintext, HashedPassword $hash): bool
    {
        return password_verify($plaintext, $hash->getHash());
    }
}
