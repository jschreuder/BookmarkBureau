<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

final class User implements EntityEqualityInterface
{
    public readonly UuidInterface $userId;

    public Value\Email $email {
        set {
            $this->email = $value;
            $this->markAsUpdated();
        }
    }

    public Value\HashedPassword $passwordHash {
        set {
            $this->passwordHash = $value;
            $this->markAsUpdated();
        }
    }

    public ?Value\TotpSecret $totpSecret {
        set {
            $this->totpSecret = $value;
            $this->markAsUpdated();
        }
    }

    public readonly DateTimeInterface $createdAt;

    public private(set) DateTimeInterface $updatedAt;

    public function __construct(
        UuidInterface $userId,
        Value\Email $email,
        Value\HashedPassword $passwordHash,
        ?Value\TotpSecret $totpSecret,
        DateTimeInterface $createdAt,
        DateTimeInterface $updatedAt,
    ) {
        $this->userId = $userId;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->totpSecret = $totpSecret;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

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
        return $this->totpSecret !== null;
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    #[\Override]
    public function equals(object $entity): bool
    {
        return match (true) {
            !$entity instanceof self => false,
            default => $entity->userId->equals($this->userId),
        };
    }
}
