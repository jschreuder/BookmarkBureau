<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Collection\UserCollection;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Exception\DuplicateEmailException;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface UserServiceInterface
{
    /**
     * Create a new user with email and plaintext password
     *
     * Password is hashed before storage. Email must be unique.
     *
     * @throws DuplicateEmailException when email already exists
     */
    public function createUser(Email $email, string $plainPassword): User;

    /**
     * Get a user by their ID
     *
     * @throws UserNotFoundException when user doesn't exist
     */
    public function getUser(UuidInterface $userId): User;

    /**
     * Get a user by their email address
     *
     * @throws UserNotFoundException when user doesn't exist
     */
    public function getUserByEmail(Email $email): User;

    /**
     * List all users
     */
    public function listAllUsers(): UserCollection;

    /**
     * Delete a user
     *
     * @throws UserNotFoundException when user doesn't exist
     */
    public function deleteUser(UuidInterface $userId): void;

    /**
     * Change a user's password
     *
     * Takes plaintext password, hashes it, and updates the user
     *
     * @throws UserNotFoundException when user doesn't exist
     */
    public function changePassword(
        UuidInterface $userId,
        string $plainPassword,
    ): void;

    /**
     * Verify a plaintext password against a user's stored hash
     *
     * Used during authentication to validate login credentials
     */
    public function verifyPassword(User $user, string $plainPassword): bool;

    /**
     * Enable TOTP for a user and return the generated secret
     *
     * The secret should be shown as a QR code or text for scanning into an authenticator app
     *
     * @throws UserNotFoundException when user doesn't exist
     */
    public function enableTotp(UuidInterface $userId): TotpSecret;

    /**
     * Disable TOTP for a user
     *
     * @throws UserNotFoundException when user doesn't exist
     */
    public function disableTotp(UuidInterface $userId): void;
}
