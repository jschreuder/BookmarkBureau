<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

use Ramsey\Uuid\UuidInterface;

final class User
{
    public function __construct(
        public readonly UuidInterface $userId,
        public private(set) Value\Email $email,
        public private(set) Value\HashedPassword $passwordHash,
        public private(set) ?Value\TotpSecret $totpSecret
    ) {}

    /**
     * Putting this in a function as it must never happen by accident
     */
    public function changePassword(Value\HashedPassword $newPassword): void
    {
        $this->passwordHash = $newPassword;
    }

    /**
     * Enable or change TOTP secret
     * Putting this in a function as it must never happen by accident
     */
    public function changeTotpSecret(Value\TotpSecret $newSecret): void
    {
        $this->totpSecret = $newSecret;
    }

    /**
     * Disable TOTP authentication
     * Putting this in a function as it must never happen by accident
     */
    public function disableTotp(): void
    {
        $this->totpSecret = null;
    }

    public function requiresTotp(): bool
    {
        return !is_null($this->totpSecret);
    }
}
