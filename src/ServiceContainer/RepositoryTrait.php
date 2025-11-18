<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\ServiceContainer;

use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FileUserRepository;
use jschreuder\BookmarkBureau\Repository\JwtJtiRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\PdoCategoryRepository;
use jschreuder\BookmarkBureau\Repository\PdoDashboardRepository;
use jschreuder\BookmarkBureau\Repository\PdoFavoriteRepository;
use jschreuder\BookmarkBureau\Repository\PdoJwtJtiRepository;
use jschreuder\BookmarkBureau\Repository\PdoLinkRepository;
use jschreuder\BookmarkBureau\Repository\PdoTagRepository;
use jschreuder\BookmarkBureau\Repository\PdoUserRepository;
use jschreuder\BookmarkBureau\Repository\TagRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;
use jschreuder\BookmarkBureau\Entity\Mapper\DashboardEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\CategoryEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\FavoriteEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\TagEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\UserEntityMapper;
use PDO;

trait RepositoryTrait
{
    // Abstract for methods from ConfigTrait, DatabaseTrait
    abstract protected function config(string $key): mixed;
    abstract public function getDb(): PDO;

    public function getLinkRepository(): LinkRepositoryInterface
    {
        return new PdoLinkRepository(
            $this->getDb(),
            new LinkEntityMapper(),
            new TagEntityMapper(),
        );
    }

    public function getTagRepository(): TagRepositoryInterface
    {
        return new PdoTagRepository($this->getDb(), new TagEntityMapper());
    }

    public function getCategoryRepository(): CategoryRepositoryInterface
    {
        return new PdoCategoryRepository(
            $this->getDb(),
            $this->getDashboardRepository(),
            $this->getLinkRepository(),
            new CategoryEntityMapper(),
            new LinkEntityMapper(),
        );
    }

    public function getDashboardRepository(): DashboardRepositoryInterface
    {
        return new PdoDashboardRepository(
            $this->getDb(),
            new DashboardEntityMapper(),
        );
    }

    public function getFavoriteRepository(): FavoriteRepositoryInterface
    {
        return new PdoFavoriteRepository(
            $this->getDb(),
            $this->getDashboardRepository(),
            $this->getLinkRepository(),
            new FavoriteEntityMapper(),
            new DashboardEntityMapper(),
            new LinkEntityMapper(),
        );
    }

    public function getUserRepository(): UserRepositoryInterface
    {
        $storageType = $this->config("users.storage.type");

        return match ($storageType) {
            "file" => new FileUserRepository(
                $this->config("users.storage.path"),
                new UserEntityMapper(),
            ),
            "pdo" => new PdoUserRepository(
                $this->getDb(),
                new UserEntityMapper(),
            ),
            default => throw new RepositoryStorageException(
                "Unknown user storage type: {$storageType}",
            ),
        };
    }

    public function getJwtJtiRepository(): JwtJtiRepositoryInterface
    {
        return new PdoJwtJtiRepository($this->getDb());
    }
}
