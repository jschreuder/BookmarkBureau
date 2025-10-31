<?php declare(strict_types = 1);

namespace jschreuder\BookmarkBureau;

use jschreuder\Middle\ApplicationStack;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\ControllerRunner;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\SymfonyRouter;
use jschreuder\Middle\Router\UrlGeneratorInterface;
use jschreuder\Middle\ServerMiddleware\ErrorHandlerMiddleware;
use jschreuder\Middle\ServerMiddleware\JsonRequestParserMiddleware;
use jschreuder\Middle\ServerMiddleware\RoutingMiddleware;
use jschreuder\MiddleDi\ConfigTrait;
use jschreuder\BookmarkBureau\Controller\ErrorHandlerController;
use jschreuder\BookmarkBureau\Controller\NotFoundHandlerController;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\PdoCategoryRepository;
use jschreuder\BookmarkBureau\Repository\PdoDashboardRepository;
use jschreuder\BookmarkBureau\Repository\PdoFavoriteRepository;
use jschreuder\BookmarkBureau\Repository\PdoLinkRepository;
use jschreuder\BookmarkBureau\Repository\PdoTagRepository;
use jschreuder\BookmarkBureau\Repository\TagRepositoryInterface;
use jschreuder\BookmarkBureau\Service\CategoryService;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\BookmarkBureau\Service\DashboardService;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use jschreuder\BookmarkBureau\Service\FavoriteService;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use jschreuder\BookmarkBureau\Service\LinkService;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use jschreuder\BookmarkBureau\Service\TagService;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\BookmarkBureau\Service\UnitOfWork\PdoUnitOfWork;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\ServerMiddleware\RequestFilterMiddleware;
use jschreuder\Middle\ServerMiddleware\RequestValidatorMiddleware;
use Laminas\Diactoros\Response\JsonResponse;
use Monolog;
use PDO;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ServiceContainer
{
    use ConfigTrait;

    public function getApp(): ApplicationStack
    {
        return new ApplicationStack(
            new ControllerRunner(),
            new RequestValidatorMiddleware(function(ServerRequestInterface $request, ValidationFailedException $error) {
                return new JsonResponse(['errors' => $error->getValidationErrors()], 400);
            }),
            new RequestFilterMiddleware,
            new JsonRequestParserMiddleware(),
            new RoutingMiddleware(
                $this->getAppRouter(),
                $this->get404Handler()
            ),
            new ErrorHandlerMiddleware(
                $this->getLogger(),
                $this->get500Handler()
            )
        );
    }

    public function getLogger(): LoggerInterface
    {
        $logger = new \Monolog\Logger($this->config('logger.name'));
        $logger->pushHandler((new \Monolog\Handler\StreamHandler(
            $this->config('logger.path'),
            Monolog\Level::Notice
        ))->setFormatter(new \Monolog\Formatter\LineFormatter()));
        return $logger;
    }

    public function getAppRouter(): RouterInterface
    {
        return new SymfonyRouter($this->config('site.url'));
    }

    public function getUrlGenerator() : UrlGeneratorInterface
    {
        return $this->getAppRouter()->getGenerator();
    }

    public function get404Handler(): ControllerInterface
    {
        return new NotFoundHandlerController();
    }

    public function get500Handler(): ControllerInterface
    {
        return new ErrorHandlerController($this->getLogger());
    }

    public function getDb(): PDO
    {
        $baseDsn = $this->config('db.dsn');
        $dbname = $this->config('db.dbname');

        // For SQLite, the dbname is part of the DSN directly (e.g., "sqlite::memory:" or "sqlite:/path/to/db")
        // For other databases like MySQL, we need to append ";dbname=" separator
        if (str_starts_with($baseDsn, 'sqlite:')) {
            $dsn = $baseDsn . $dbname;
        } else {
            $dsn = $baseDsn . ';dbname=' . $dbname;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        // Only set MySQL-specific attributes if using MySQL driver
        if (!str_starts_with($baseDsn, 'sqlite:') && defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
        }

        // Convert empty values to null for PDO compatibility
        $user = $this->config('db.user') ?: null;
        $pass = $this->config('db.pass') ?: null;

        return new PDO(
            $dsn,
            $user,
            $pass,
            $options
        );
    }

    public function getUnitOfWork(): UnitOfWorkInterface
    {
        return new PdoUnitOfWork($this->getDb());
    }

    public function getLinkRepository(): LinkRepositoryInterface
    {
        return new PdoLinkRepository($this->getDb());
    }

    public function getTagRepository(): TagRepositoryInterface
    {
        return new PdoTagRepository($this->getDb());
    }

    public function getCategoryRepository(): CategoryRepositoryInterface
    {
        return new PdoCategoryRepository(
            $this->getDb(),
            $this->getDashboardRepository(),
            $this->getLinkRepository()
        );
    }

    public function getDashboardRepository(): DashboardRepositoryInterface
    {
        return new PdoDashboardRepository($this->getDb());
    }

    public function getFavoriteRepository(): FavoriteRepositoryInterface
    {
        return new PdoFavoriteRepository(
            $this->getDb(),
            $this->getDashboardRepository(),
            $this->getLinkRepository()
        );
    }

    public function getLinkService(): LinkServiceInterface
    {
        return new LinkService(
            $this->getLinkRepository(),
            $this->getUnitOfWork()
        );
    }

    public function getTagService(): TagServiceInterface
    {
        return new TagService(
            $this->getTagRepository(),
            $this->getLinkRepository(),
            $this->getUnitOfWork()
        );
    }

    public function getCategoryService(): CategoryServiceInterface
    {
        return new CategoryService(
            $this->getCategoryRepository(),
            $this->getDashboardRepository(),
            $this->getUnitOfWork()
        );
    }

    public function getDashboardService(): DashboardServiceInterface
    {
        return new DashboardService(
            $this->getDashboardRepository(),
            $this->getCategoryRepository(),
            $this->getFavoriteRepository(),
            $this->getUnitOfWork()
        );
    }

    public function getFavoriteService(): FavoriteServiceInterface
    {
        return new FavoriteService(
            $this->getFavoriteRepository(),
            $this->getDashboardRepository(),
            $this->getLinkRepository(),
            $this->getUnitOfWork()
        );
    }
}
