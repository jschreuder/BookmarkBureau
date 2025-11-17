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
    public function findAll(): UserCollection;

    /**
     * Save a new user or update an existing one
     */
    public function save(User $user): void;

    /**
     * Delete a user
     */
    public function delete(User $user): void;

    /**
     * Check if a user with the given email already exists
     */
    public function existsByEmail(Email $email): bool;

    /**
     * Count total number of users
     */
    public function count(): int;
}
