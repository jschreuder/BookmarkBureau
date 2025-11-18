<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use Closure;
use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Exception\WeakPasswordException;

final readonly class PasswordHasherStrengthDecorator implements
    PasswordHasherInterface
{
    public function __construct(
        private PasswordHasherInterface $hasher,
        private ?int $minLength = null,
        private ?Closure $strengthValidator = null,
    ) {}

    #[\Override]
    public function hash(string $plaintext): HashedPassword
    {
        match (true) {
            $this->minLength !== null && \strlen($plaintext) < $this->minLength
                => throw new WeakPasswordException("Password is too short"),
            $this->strengthValidator !== null &&
                !($this->strengthValidator)($plaintext)
                => throw new WeakPasswordException("Password is too weak"),
            default => null,
        };

        return $this->hasher->hash($plaintext);
    }

    #[\Override]
    public function verify(string $plaintext, HashedPassword $hash): bool
    {
        return $this->hasher->verify($plaintext, $hash);
    }
}
