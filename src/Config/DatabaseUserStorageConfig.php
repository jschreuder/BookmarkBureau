<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

use jschreuder\BookmarkBureau\Repository\PdoUserRepository;
use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;
use jschreuder\BookmarkBureau\Entity\Mapper\UserEntityMapper;

final readonly class DatabaseUserStorageConfig implements
    UserStorageConfigInterface
{
    public function __construct(private DatabaseConfigInterface $database) {}

    #[\Override]
    public function createUserRepository(): UserRepositoryInterface
    {
        return new PdoUserRepository(
            $this->database->getConnection(),
            new UserEntityMapper(),
        );
    }
}
