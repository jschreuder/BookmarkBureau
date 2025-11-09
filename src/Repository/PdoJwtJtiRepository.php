<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use DateTimeInterface;
use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Util\SqlFormat;

final readonly class PdoJwtJtiRepository implements JwtJtiRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * @throws RepositoryStorageException on storage failure
     */
    #[\Override]
    public function saveJti(
        UuidInterface $jti,
        UuidInterface $userId,
        DateTimeInterface $createdAt,
    ): void {
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO jwt_jti (jti, user_id, created_at)
                 VALUES (:jti, :user_id, :created_at)',
            );
            $statement->execute([
                ":jti" => $jti->getBytes(),
                ":user_id" => $userId->getBytes(),
                ":created_at" => $createdAt->format(SqlFormat::TIMESTAMP),
            ]);
        } catch (\Throwable $e) {
            throw new RepositoryStorageException(
                "Failed to save JWT JTI: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    #[\Override]
    public function hasJti(UuidInterface $jti): bool
    {
        try {
            $statement = $this->pdo->prepare(
                "SELECT 1 FROM jwt_jti WHERE jti = :jti LIMIT 1",
            );
            $statement->execute([":jti" => $jti->getBytes()]);
            return $statement->fetch() !== false;
        } catch (\Throwable $e) {
            throw new RepositoryStorageException(
                "Failed to check JWT JTI: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * @throws RepositoryStorageException on storage failure
     */
    #[\Override]
    public function deleteJti(UuidInterface $jti): void
    {
        try {
            $statement = $this->pdo->prepare(
                "DELETE FROM jwt_jti WHERE jti = :jti",
            );
            $statement->execute([":jti" => $jti->getBytes()]);
        } catch (\Throwable $e) {
            throw new RepositoryStorageException(
                "Failed to delete JWT JTI: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }
}
