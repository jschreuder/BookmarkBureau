<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;

/**
 * User storage configuration interface.
 * Implementations define whether users are stored in files or a database.
 */
interface UserStorageConfigInterface
{
    /**
     * Create a user repository with this storage configuration
     */
    public function createUserRepository(): UserRepositoryInterface;
}
