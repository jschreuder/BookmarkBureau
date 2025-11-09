<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use DateTimeInterface;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use Ramsey\Uuid\UuidInterface;

interface JwtJtiRepositoryInterface
{
    /**
     * Save a new JWT JTI to the whitelist
     *
     * @throws RepositoryStorageException on storage failure
     */
    public function saveJti(
        UuidInterface $jti,
        UuidInterface $userId,
        DateTimeInterface $createdAt,
    ): void;

    /**
     * Check if a JTI exists in the whitelist
     */
    public function hasJti(UuidInterface $jti): bool;

    /**
     * Delete a JTI from the whitelist (revokes the token)
     *
     * @throws RepositoryStorageException on storage failure
     */
    public function deleteJti(UuidInterface $jti): void;
}
