<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use jschreuder\BookmarkBureau\Composite\UserCollection;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Mapper\UserEntityMapper;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Exception\DuplicateUserException;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class FileUserRepository implements UserRepositoryInterface
{
    /**
     * @var array<string, User> In-memory cache of users indexed by emailaddress
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

        foreach ($this->users as $user) {
            if ($user->userId->equals($userId)) {
                return $user;
            }
        }

        throw UserNotFoundException::forId($userId);
    }

    /**
     * @throws UserNotFoundException when user doesn't exist
     */
    #[\Override]
    public function findByEmail(Email $email): User
    {
        $this->loadUsers();

        if (!isset($this->users[$email->value])) {
            throw UserNotFoundException::forEmail($email);
        }

        return $this->users[$email->value];
    }

    /**
     * Get all users ordered by email
     */
    #[\Override]
    public function listAll(): UserCollection
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
    public function insert(User $user): void
    {
        $this->loadUsers();
        if (isset($this->users[$user->email->value])) {
            throw DuplicateUserException::forEmail($user->email);
        }
        $this->users[$user->email->value] = $user;
        $this->persistUsers();
    }

    /**
     * Update an existing user
     */
    #[\Override]
    public function update(User $user): void
    {
        $this->loadUsers();
        // Throws exception if user does not exist:
        $existingUser = $this->findById($user->userId);

        // Check if new email is already taken by another user
        if (
            isset($this->users[$user->email->value]) &&
            !$existingUser->userId->equals(
                $this->users[$user->email->value]->userId,
            )
        ) {
            throw DuplicateUserException::forEmail($user->email);
        }

        // Remove old entry if user email has changed
        if (!$existingUser->email->equals($user->email)) {
            unset($this->users[$existingUser->email->value]);
        }

        // Remove old entry and insert updated user
        $this->users[$user->email->value] = $user;
        $this->persistUsers();
    }

    /**
     * Delete a user
     */
    #[\Override]
    public function delete(User $user): void
    {
        $this->loadUsers();
        unset($this->users[$user->email->value]);
        $this->persistUsers();
    }

    /**
     * Check if a user with the given email already exists
     */
    #[\Override]
    public function hasUserWithEmail(Email $email): bool
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
        /** @var array{user_id: string, email: string, password_hash: string, totp_secret: string|null, created_at: string, updated_at: string} $userArray */
        foreach ($data as $userArray) {
            // JSON stores UUIDs as strings, mapper expects bytes - convert for mapper compatibility
            $userArray["user_id"] = Uuid::fromString(
                $userArray["user_id"],
            )->getBytes();
            $user = $this->mapper->mapToEntity($userArray);
            $this->users[$user->email->value] = $user;
        }
    }

    /**
     * Load users from JSON file into memory
     * @return array<int, array{user_id: string, email: string, password_hash: string, totp_secret: string|null, created_at: string, updated_at: string}>
     */
    private function loadJsonData(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            return [];
        }

        /** @var array<int, array{user_id: string, email: string, password_hash: string, totp_secret: string|null, created_at: string, updated_at: string}> $data */
        $data = json_decode($content, true);
        return $data;
    }

    /**
     * Persist all users to JSON file
     */
    private function persistUsers(): void
    {
        $data = [];
        foreach ($this->users as $user) {
            $row = $this->mapper->mapToRow($user);
            $row["user_id"] = Uuid::fromBytes($row["user_id"])->toString();
            $data[] = $row;
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
}
