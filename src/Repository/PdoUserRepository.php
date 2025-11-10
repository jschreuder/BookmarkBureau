<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use PDO;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Collection\UserCollection;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Mapper\UserEntityMapper;
use jschreuder\BookmarkBureau\Entity\Value\Email;
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
            $this->mapper->getFields(),
            "user_id = :user_id LIMIT 1",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":user_id" => $userId->getBytes()]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new UserNotFoundException(
                "User not found: " . $userId->toString(),
            );
        }

        return $this->mapper->mapToEntity($row);
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function findByEmail(
        \jschreuder\BookmarkBureau\Entity\Value\Email $email,
    ): User {
        $sql = SqlBuilder::buildSelect(
            "users",
            $this->mapper->getFields(),
            "email = :email LIMIT 1",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([":email" => (string) $email]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new UserNotFoundException("User not found: " . $email);
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
            $this->mapper->getFields(),
            null,
            "email ASC",
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute();
        $users = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $this->mapper->mapToEntity($row);
        }
        return new UserCollection(...$users);
    }

    /**
     * Save a new user or update an existing one
     */
    #[\Override]
    public function save(User $user): void
    {
        $row = $this->mapper->mapToRow($user);
        $userIdBytes = $row["user_id"];

        // Check if user exists
        $check = $this->pdo->prepare(
            "SELECT 1 FROM users WHERE user_id = :user_id LIMIT 1",
        );
        $check->execute([":user_id" => $userIdBytes]);

        if ($check->fetch() === false) {
            // Insert new user
            $build = SqlBuilder::buildInsert("users", $row);
            $this->pdo->prepare($build["sql"])->execute($build["params"]);
        } else {
            // Update existing user
            $build = SqlBuilder::buildUpdate("users", $row, "user_id");
            $this->pdo->prepare($build["sql"])->execute($build["params"]);
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
}
