<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Composite\UserCollection;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Mapper\UserEntityMapper;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class FileUserRepository implements UserRepositoryInterface
{
    /**
     * @var array<string, User> In-memory cache of users indexed by user_id string
     */
    private array $users = [];

    private bool $isLoaded = false;

    public function __construct(
        private readonly string $filePath,
        private readonly UserEntityMapper $mapper,
    ) {}

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function findById(UuidInterface $userId): User
    {
        $this->loadUsers();
        $userIdString = $userId->toString();

        if (!isset($this->users[$userIdString])) {
            throw new UserNotFoundException("User not found: {$userIdString}");
        }

        return $this->users[$userIdString];
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function findByEmail(Email $email): User
    {
        $this->loadUsers();
        $emailString = (string) $email;

        foreach ($this->users as $user) {
            if ((string) $user->email === $emailString) {
                return $user;
            }
        }

        throw new UserNotFoundException("User not found: {$emailString}");
    }

    /**
     * Get all users ordered by email
     */
    #[\Override]
    public function findAll(): UserCollection
    {
        $this->loadUsers();

        // Sort by email
        $users = $this->users;
        usort(
            $users,
            fn(User $a, User $b) => strcmp(
                (string) $a->email,
                (string) $b->email,
            ),
        );

        return new UserCollection(...$users);
    }

    /**
     * Save a new user or update an existing one
     */
    #[\Override]
    public function save(User $user): void
    {
        $this->loadUsers();
        $userIdString = $user->userId->toString();
        $this->users[$userIdString] = $user;
        $this->persistUsers();
    }

    /**
     * Delete a user
     */
    #[\Override]
    public function delete(User $user): void
    {
        $this->loadUsers();
        $userIdString = $user->userId->toString();
        unset($this->users[$userIdString]);
        $this->persistUsers();
    }

    /**
     * Check if a user with the given email already exists
     */
    #[\Override]
    public function existsByEmail(Email $email): bool
    {
        $this->loadUsers();
        $emailString = (string) $email;

        foreach ($this->users as $user) {
            if ((string) $user->email === $emailString) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count total number of users
     */
    #[\Override]
    public function count(): int
    {
        $this->loadUsers();
        return \count($this->users);
    }

    /**
     * Load users from JSON file into memory
     */
    private function loadUsers(): void
    {
        if ($this->isLoaded) {
            return;
        }

        $this->users = [];
        $this->isLoaded = true;

        $data = $this->loadJsonData();
        foreach ($data as $userArray) {
            $user = $this->mapArrayToUser($userArray);
            $this->users[$user->userId->toString()] = $user;
        }
    }

    private function loadJsonData(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return \is_array($data) ? $data : [];
    }

    /**
     * Persist all users to JSON file
     */
    private function persistUsers(): void
    {
        $data = [];
        foreach ($this->users as $user) {
            $data[] = $this->mapUserToArray($user);
        }

        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RepositoryStorageException(
                "Failed to encode users to JSON",
            );
        }

        if (file_put_contents($this->filePath, $json) === false) {
            throw new RepositoryStorageException(
                "Failed to write users to file: {$this->filePath}",
            );
        }
    }

    /**
     * Map a User entity to an array for JSON serialization
     * Uses mapper to transform UUIDs to string format for JSON storage
     */
    private function mapUserToArray(User $user): array
    {
        $row = $this->mapper->mapToRow($user);
        // Convert binary UUID back to string for JSON storage
        return [
            "user_id" => $user->userId->toString(),
            "email" => $row["email"],
            "password_hash" => $row["password_hash"],
            "totp_secret" => $row["totp_secret"],
            "created_at" => $row["created_at"],
            "updated_at" => $row["updated_at"],
        ];
    }

    /**
     * Map an array from JSON to a User entity
     * Converts string UUID to bytes format expected by mapper
     */
    private function mapArrayToUser(array $data): User
    {
        // Convert string UUID to bytes for mapper compatibility
        $mappedData = [
            "user_id" => Uuid::fromString($data["user_id"])->getBytes(),
            "email" => $data["email"],
            "password_hash" => $data["password_hash"],
            "totp_secret" => $data["totp_secret"],
            "created_at" => $data["created_at"],
            "updated_at" => $data["updated_at"],
        ];
        return $this->mapper->mapToEntity($mappedData);
    }
}
