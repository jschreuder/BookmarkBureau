<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;

interface PasswordHasherInterface
{
    public function hash(string $plaintext): HashedPassword;

    public function verify(string $plaintext, HashedPassword $hash): bool;
}
