<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Composite\UserCollection;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface UserRepositoryInterface
{
    /**
     * Get a user by their ID
     *
     * @throws UserNotFoundException when user doesn't exist
     */
    public function findById(UuidInterface $userId): User;

    /**
     * Get a user by their email address
     *
     * @throws UserNotFoundException when user doesn't exist
     */
    public function findByEmail(Email $email): User;

    /**
     * Get all users
     */
    public function listAll(): UserCollection;

    /**
     * Save a new user
     */
    public function insert(User $user): void;

    /**
     * Update existing user
     */
    public function update(User $user): void;

    /**
     * Delete a user
     */
    public function delete(User $user): void;

    /**
     * Check if a user with the given email already exists
     */
    public function hasUserWithEmail(Email $email): bool;

    /**
     * Count total number of users
     */
    public function count(): int;
}
