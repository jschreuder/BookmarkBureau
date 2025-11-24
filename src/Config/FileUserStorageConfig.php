<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

use jschreuder\BookmarkBureau\Repository\FileUserRepository;
use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;
use jschreuder\BookmarkBureau\Entity\Mapper\UserEntityMapper;

final readonly class FileUserStorageConfig implements UserStorageConfigInterface
{
    public function __construct(
        public string $filePath,
    ) {}

    #[\Override]
    public function createUserRepository(): UserRepositoryInterface
    {
        return new FileUserRepository(
            $this->filePath,
            new UserEntityMapper(),
        );
    }
}
