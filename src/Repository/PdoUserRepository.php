<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Composite\UserCollection;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Mapper\UserEntityMapper;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Util\SqlBuilder;

final readonly class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly UserEntityMapper $mapper,
    ) {}

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function findById(UuidInterface $userId): User
    {
        $sql = SqlBuilder::buildSelect(
            "users",
            $this->mapper->getDbFields(),
            "user_id = :user_id LIMIT 1",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":user_id" => $userId->getBytes()]);

        /** @var array{user_id: string, email: string, password_hash: string, totp_secret: string|null, created_at: string, updated_at: string}|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw UserNotFoundException::forId($userId);
        }

        return $this->mapper->mapToEntity($row);
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function findByEmail(Email $email): User
    {
        $sql = SqlBuilder::buildSelect(
            "users",
            $this->mapper->getDbFields(),
            "email = :email LIMIT 1",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":email" => (string) $email]);

        /** @var array{user_id: string, email: string, password_hash: string, totp_secret: string|null, created_at: string, updated_at: string}|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw UserNotFoundException::forEmail($email);
        }

        return $this->mapper->mapToEntity($row);
    }

    /**
     * Get all users ordered by email
     */
    #[\Override]
    public function findAll(): UserCollection
    {
        $sql = SqlBuilder::buildSelect(
            "users",
            $this->mapper->getDbFields(),
            null,
            "email ASC",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute();
        $users = [];

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            /** @var array{user_id: string, email: string, password_hash: string, totp_secret: string|null, created_at: string, updated_at: string} $row */
            $users[] = $this->mapper->mapToEntity($row);
        }
        return new UserCollection(...$users);
    }

    /**
     * Save a new user
     */
    #[\Override]
    public function insert(User $user): void
    {
        $row = $this->mapper->mapToRow($user);
        $build = SqlBuilder::buildInsert("users", $row);
        $this->pdo->prepare($build["sql"])->execute($build["params"]);
    }

    /**
     * Update existing user
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function update(User $user): void
    {
        $row = $this->mapper->mapToRow($user);
        $build = SqlBuilder::buildUpdate("users", $row, "user_id");
        $statement = $this->pdo->prepare($build["sql"]);
        $statement->execute($build["params"]);

        if ($statement->rowCount() === 0) {
            throw UserNotFoundException::forId($user->userId);
        }
    }

    /**
     * Delete a user
     */
    #[\Override]
    public function delete(User $user): void
    {
        $query = SqlBuilder::buildDelete("users", [
            "user_id" => $user->userId->getBytes(),
        ]);
        $this->pdo->prepare($query["sql"])->execute($query["params"]);
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

        /** @var array{count: int}|false $result */
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            throw new RepositoryStorageException("Failed to count users");
        }
        return (int) $result["count"];
    }
}
