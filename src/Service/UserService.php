<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Collection\UserCollection;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Exception\DuplicateEmailException;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private UnitOfWorkInterface $unitOfWork,
    ) {}

    #[\Override]
    public function createUser(Email $email, string $plainPassword): User
    {
        return $this->unitOfWork->transactional(function () use (
            $email,
            $plainPassword,
        ): User {
            // Check if email already exists
            if ($this->userRepository->existsByEmail($email)) {
                throw new DuplicateEmailException(
                    "Email already exists: {$email}",
                );
            }

            // Hash the password
            $passwordHash = $this->passwordHasher->hash($plainPassword);

            // Create new user without TOTP
            $now = new DateTimeImmutable();
            $user = new User(
                userId: Uuid::uuid4(),
                email: $email,
                passwordHash: $passwordHash,
                totpSecret: null,
                createdAt: $now,
                updatedAt: $now,
            );

            // Persist to repository
            $this->userRepository->save($user);
            return $user;
        });
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function getUser(UuidInterface $userId): User
    {
        return $this->userRepository->findById($userId);
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function getUserByEmail(Email $email): User
    {
        return $this->userRepository->findByEmail($email);
    }

    #[\Override]
    public function listAllUsers(): UserCollection
    {
        return $this->userRepository->findAll();
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function deleteUser(UuidInterface $userId): void
    {
        $this->unitOfWork->transactional(function () use ($userId): void {
            $user = $this->userRepository->findById($userId);
            $this->userRepository->delete($user);
        });
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function changePassword(
        UuidInterface $userId,
        string $plainPassword,
    ): void {
        $this->unitOfWork->transactional(function () use (
            $userId,
            $plainPassword,
        ): void {
            $user = $this->userRepository->findById($userId);

            // Hash the new password
            $newPasswordHash = $this->passwordHasher->hash($plainPassword);

            // Update user's password using the entity method
            $user->changePassword($newPasswordHash);

            // Persist changes
            $this->userRepository->save($user);
        });
    }

    #[\Override]
    public function verifyPassword(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->verify(
            $plainPassword,
            $user->passwordHash,
        );
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function enableTotp(UuidInterface $userId): TotpSecret
    {
        return $this->unitOfWork->transactional(function () use (
            $userId,
        ): TotpSecret {
            $user = $this->userRepository->findById($userId);

            // Generate a new TOTP secret
            $totpSecret = $this->generateTotpSecret();

            // Update user's TOTP secret using the entity method
            $user->changeTotpSecret($totpSecret);

            // Persist changes
            $this->userRepository->save($user);

            return $totpSecret;
        });
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function disableTotp(UuidInterface $userId): void
    {
        $this->unitOfWork->transactional(function () use ($userId): void {
            $user = $this->userRepository->findById($userId);

            // Disable TOTP using the entity method
            $user->disableTotp();

            // Persist changes
            $this->userRepository->save($user);
        });
    }

    /**
     * Generate a random TOTP secret in Base32 format (RFC 4648)
     *
     * Generates a 32-character Base32 secret (160 bits of entropy)
     * using the alphabet: A-Z and 2-7
     */
    private function generateTotpSecret(): TotpSecret
    {
        // Base32 alphabet per RFC 4648
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
        $secret = "";

        // Generate 32 random Base32 characters (160 bits = 32 * 5 bits)
        for ($i = 0; $i < 32; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }

        return new TotpSecret($secret);
    }
}
