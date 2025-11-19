<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\ServiceContainer;

use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use jschreuder\BookmarkBureau\Service\CategoryService;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\BookmarkBureau\Service\CategoryServicePipelines;
use jschreuder\BookmarkBureau\Service\DashboardService;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use jschreuder\BookmarkBureau\Service\DashboardServicePipelines;
use jschreuder\BookmarkBureau\Service\FavoriteService;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use jschreuder\BookmarkBureau\Service\FavoriteServicePipelines;
use jschreuder\BookmarkBureau\Service\LinkService;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use jschreuder\BookmarkBureau\Service\LinkServicePipelines;
use jschreuder\BookmarkBureau\Service\TagService;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\BookmarkBureau\Service\TagServicePipelines;
use jschreuder\BookmarkBureau\Service\UserService;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use jschreuder\BookmarkBureau\Service\UserServicePipelines;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\TagRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;
use jschreuder\BookmarkBureau\Service\PasswordHasherInterface;

trait ServiceTrait
{
    // Abstract for methods from ConfigTrait, DatabaseTrait, RepositoryTrait
    abstract protected function config(string $key): mixed;
    abstract public function getDefaultDbPipeline(): PipelineInterface;
    abstract public function getLinkRepository(): LinkRepositoryInterface;
    abstract public function getTagRepository(): TagRepositoryInterface;
    abstract public function getCategoryRepository(): CategoryRepositoryInterface;
    abstract public function getDashboardRepository(): DashboardRepositoryInterface;
    abstract public function getFavoriteRepository(): FavoriteRepositoryInterface;
    abstract public function getUserRepository(): UserRepositoryInterface;
    abstract public function getPasswordHasher(): PasswordHasherInterface;

    public function getLinkService(): LinkServiceInterface
    {
        return new LinkService(
            $this->getLinkRepository(),
            $this->getLinkServicePipelines(),
        );
    }

    public function getLinkServicePipelines(): LinkServicePipelines
    {
        return new LinkServicePipelines(default: $this->getDefaultDbPipeline());
    }

    public function getTagService(): TagServiceInterface
    {
        return new TagService(
            $this->getTagRepository(),
            $this->getLinkRepository(),
            $this->getTagServicePipelines(),
        );
    }

    public function getTagServicePipelines(): TagServicePipelines
    {
        return new TagServicePipelines(default: $this->getDefaultDbPipeline());
    }

    public function getCategoryService(): CategoryServiceInterface
    {
        return new CategoryService(
            $this->getCategoryRepository(),
            $this->getDashboardRepository(),
            $this->getLinkRepository(),
            $this->getCategoryServicePipelines(),
        );
    }

    public function getCategoryServicePipelines(): CategoryServicePipelines
    {
        return new CategoryServicePipelines(
            default: $this->getDefaultDbPipeline(),
        );
    }

    public function getDashboardService(): DashboardServiceInterface
    {
        return new DashboardService(
            $this->getDashboardRepository(),
            $this->getCategoryRepository(),
            $this->getFavoriteRepository(),
            $this->getDashboardServicePipelines(),
        );
    }

    public function getDashboardServicePipelines(): DashboardServicePipelines
    {
        return new DashboardServicePipelines(
            default: $this->getDefaultDbPipeline(),
        );
    }

    public function getFavoriteService(): FavoriteServiceInterface
    {
        return new FavoriteService(
            $this->getFavoriteRepository(),
            $this->getDashboardRepository(),
            $this->getLinkRepository(),
            $this->getFavoriteServicePipelines(),
        );
    }

    public function getFavoriteServicePipelines(): FavoriteServicePipelines
    {
        return new FavoriteServicePipelines(
            default: $this->getDefaultDbPipeline(),
        );
    }

    public function getUserService(): UserServiceInterface
    {
        return new UserService(
            $this->getUserRepository(),
            $this->getPasswordHasher(),
            $this->getUserServicePipelines(),
        );
    }

    public function getUserServicePipelines(): UserServicePipelines
    {
        $storageType = $this->config("users.storage.type");

        // Use NoPipeline for file-based storage, default DB pipeline for PDO storage
        $defaultPipeline = match ($storageType) {
            "file" => new NoPipeline(),
            "pdo" => $this->getDefaultDbPipeline(),
            default => new NoPipeline(),
        };

        return new UserServicePipelines(default: $defaultPipeline);
    }
}
