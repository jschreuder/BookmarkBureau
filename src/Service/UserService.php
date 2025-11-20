<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Composite\UserCollection;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Exception\DuplicateEmailException;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private UserServicePipelines $pipelines,
    ) {}

    #[\Override]
    public function createUser(Email $email, string $plainPassword): User
    {
        // Check if email already exists
        if ($this->userRepository->existsByEmail($email)) {
            throw new DuplicateEmailException("Email already exists: {$email}");
        }

        // Hash the password
        $passwordHash = $this->passwordHasher->hash($plainPassword);

        // Create new user without TOTP
        $now = new DateTimeImmutable();
        $newUser = new User(
            userId: Uuid::uuid4(),
            email: $email,
            passwordHash: $passwordHash,
            totpSecret: null,
            createdAt: $now,
            updatedAt: $now,
        );

        return $this->pipelines->createUser()->run(function (User $user): User {
            // Persist to repository
            $this->userRepository->save($user);
            return $user;
        }, $newUser);
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function getUser(UuidInterface $userId): User
    {
        return $this->pipelines
            ->getUser()
            ->run(
                fn(UuidInterface $uid): User => $this->userRepository->findById(
                    $uid,
                ),
                $userId,
            );
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function getUserByEmail(Email $email): User
    {
        return $this->pipelines
            ->getUserByEmail()
            ->run(
                fn(Email $e) => $this->userRepository->findByEmail($e),
                $email,
            );
    }

    #[\Override]
    public function listAllUsers(): UserCollection
    {
        return $this->pipelines
            ->listAllUsers()
            ->run(fn(): UserCollection => $this->userRepository->findAll());
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function deleteUser(UuidInterface $userId): void
    {
        $deleteUser = $this->userRepository->findById($userId);
        $this->pipelines->deleteUser()->run(function (User $user): void {
            $this->userRepository->delete($user);
        }, $deleteUser);
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function changePassword(
        UuidInterface $userId,
        string $plainPassword,
    ): void {
        // Update user's password on the entity
        $updatedUser = $this->userRepository->findById($userId);
        $updatedUser->changePassword(
            $this->passwordHasher->hash($plainPassword),
        );

        $this->pipelines->changePassword()->run(function (User $user): void {
            // Persist changes
            $this->userRepository->save($user);
        }, $updatedUser);
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
        $updatedUser = $this->userRepository->findById($userId);
        // Update user's TOTP secret using the entity method
        $updatedUser->changeTotpSecret($this->generateTotpSecret());

        return $this->pipelines
            ->enableTotp()
            ->run(function (User $user): TotpSecret {
                $this->userRepository->save($user);
                return $user->totpSecret;
            }, $updatedUser);
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function disableTotp(UuidInterface $userId): void
    {
        $updatedUser = $this->userRepository->findById($userId);
        $updatedUser->disableTotp();

        $this->pipelines->disableTotp()->run(function (User $user): void {
            $this->userRepository->save($user);
        }, $updatedUser);
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
