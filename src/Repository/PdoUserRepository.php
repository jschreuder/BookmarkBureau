<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use DateTimeImmutable;
use PDO;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Collection\UserCollection;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Util\SqlFormat;

final readonly class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function findById(UuidInterface $userId): User
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM users WHERE user_id = :user_id LIMIT 1",
        );
        $statement->execute([":user_id" => $userId->getBytes()]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new UserNotFoundException(
                "User not found: " . $userId->toString(),
            );
        }

        return $this->mapRowToUser($row);
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function findByEmail(Email $email): User
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM users WHERE email = :email LIMIT 1",
        );
        $statement->execute([":email" => (string) $email]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new UserNotFoundException("User not found: " . $email);
        }

        return $this->mapRowToUser($row);
    }

    /**
     * Get all users ordered by email
     */
    #[\Override]
    public function findAll(): UserCollection
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM users ORDER BY email ASC",
        );
        $statement->execute();
        $users = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $this->mapRowToUser($row);
        }
        return new UserCollection(...$users);
    }

    /**
     * Save a new user or update an existing one
     */
    #[\Override]
    public function save(User $user): void
    {
        $userIdBytes = $user->userId->getBytes();

        // Check if user exists
        $check = $this->pdo->prepare(
            "SELECT 1 FROM users WHERE user_id = :user_id LIMIT 1",
        );
        $check->execute([":user_id" => $userIdBytes]);

        if ($check->fetch() === false) {
            // Insert new user
            $statement = $this->pdo->prepare(
                'INSERT INTO users (user_id, email, password_hash, totp_secret, created_at, updated_at)
                 VALUES (:user_id, :email, :password_hash, :totp_secret, :created_at, :updated_at)',
            );
            $statement->execute([
                ":user_id" => $userIdBytes,
                ":email" => (string) $user->email,
                ":password_hash" => $user->passwordHash->getHash(),
                ":totp_secret" => $user->totpSecret
                    ? (string) $user->totpSecret
                    : null,
                ":created_at" => $user->createdAt->format(SqlFormat::TIMESTAMP),
                ":updated_at" => $user->updatedAt->format(SqlFormat::TIMESTAMP),
            ]);
        } else {
            // Update existing user
            $statement = $this->pdo->prepare(
                'UPDATE users
                 SET email = :email, password_hash = :password_hash,
                     totp_secret = :totp_secret, updated_at = :updated_at
                 WHERE user_id = :user_id',
            );
            $statement->execute([
                ":user_id" => $userIdBytes,
                ":email" => (string) $user->email,
                ":password_hash" => $user->passwordHash->getHash(),
                ":totp_secret" => $user->totpSecret
                    ? (string) $user->totpSecret
                    : null,
                ":updated_at" => $user->updatedAt->format(SqlFormat::TIMESTAMP),
            ]);
        }
    }

    /**
     * Delete a user
     */
    #[\Override]
    public function delete(User $user): void
    {
        $statement = $this->pdo->prepare(
            "DELETE FROM users WHERE user_id = :user_id",
        );
        $statement->execute([":user_id" => $user->userId->getBytes()]);
    }

    /**
     * Check if a user with the given email already exists
     */
    #[\Override]
    public function existsByEmail(Email $email): bool
    {
        $statement = $this->pdo->prepare(
            "SELECT 1 FROM users WHERE email = :email LIMIT 1",
        );
        $statement->execute([":email" => (string) $email]);
        return $statement->fetch() !== false;
    }

    /**
     * Count total number of users
     */
    #[\Override]
    public function count(): int
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) as count FROM users");
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (int) $result["count"];
    }

    /**
     * Map a database row to a User entity
     */
    private function mapRowToUser(array $row): User
    {
        return new User(
            userId: Uuid::fromBytes($row["user_id"]),
            email: new Email($row["email"]),
            passwordHash: new HashedPassword($row["password_hash"]),
            totpSecret: $row["totp_secret"] !== null
                ? new TotpSecret($row["totp_secret"])
                : null,
            createdAt: new DateTimeImmutable($row["created_at"]),
            updatedAt: new DateTimeImmutable($row["updated_at"]),
        );
    }
}
