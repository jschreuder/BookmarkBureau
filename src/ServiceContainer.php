<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau;

use Closure;
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
use jschreuder\BookmarkBureau\Exception\IncompleteConfigException;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\JsonUserRepository;
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
use jschreuder\BookmarkBureau\Service\UserService;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use jschreuder\BookmarkBureau\Service\PasswordHasherInterface;
use jschreuder\BookmarkBureau\Service\PhpPasswordHasher;
use jschreuder\BookmarkBureau\Service\TotpVerifierInterface;
use jschreuder\BookmarkBureau\Service\OtphpTotpVerifier;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\LcobucciJwtService;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use jschreuder\BookmarkBureau\Middleware\JwtAuthenticationMiddleware;
use jschreuder\BookmarkBureau\Middleware\RequireAuthenticationMiddleware;
use jschreuder\Middle\Exception\ValidationFailedException;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\Clock;
use jschreuder\Middle\ServerMiddleware\RequestFilterMiddleware;
use jschreuder\Middle\ServerMiddleware\RequestValidatorMiddleware;
use Laminas\Diactoros\Response\JsonResponse;
use Monolog;
use PDO;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Extensible by design to allow overwriting service definitions and because
 * Middle DI needs it to be.
 */
class ServiceContainer
{
    use ConfigTrait;

    public function getApp(): ApplicationStack
    {
        return new ApplicationStack(
            new ControllerRunner(),
            new RequestValidatorMiddleware($this->getValidationErrorHandler()),
            new RequestFilterMiddleware(),
            new JsonRequestParserMiddleware(),
            new RequireAuthenticationMiddleware([
                "home", // GET / - API info endpoint
                "auth.login", // POST /auth/login - User login
                // All other routes require authentication
            ]),
            new JwtAuthenticationMiddleware($this->getJwtService()),
            new RoutingMiddleware(
                $this->getAppRouter(),
                $this->get404Handler(),
            ),
            new ErrorHandlerMiddleware(
                $this->getLogger(),
                $this->get500Handler(),
            ),
        );
    }

    public function getLogger(): LoggerInterface
    {
        $logger = new \Monolog\Logger($this->config("logger.name"));
        $logger->pushHandler(
            new \Monolog\Handler\StreamHandler(
                $this->config("logger.path"),
                Monolog\Level::Notice,
            )->setFormatter(new \Monolog\Formatter\LineFormatter()),
        );
        return $logger;
    }

    public function getAppRouter(): RouterInterface
    {
        return new SymfonyRouter($this->config("site.url"));
    }

    public function getUrlGenerator(): UrlGeneratorInterface
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

    public function getValidationErrorHandler(): Closure
    {
        return fn(
            ServerRequestInterface $request,
            ValidationFailedException $error,
        ) => new JsonResponse(["errors" => $error->getValidationErrors()], 400);
    }

    public function getDb(): PDO
    {
        // Extract database type from DSN
        $dsn = $this->config("db.dsn");
        $parts = explode(":", $dsn, 2);
        $dbType = strtolower($parts[0]);

        return match ($dbType) {
            "sqlite" => $this->createSqliteDb($dsn),
            "mysql" => $this->createMysqlDb($dsn),
            default => throw new RepositoryStorageException(
                "Unsupported database type: {$dbType}",
            ),
        };
    }

    private function createSqliteDb(string $dsn): PDO
    {
        // SQLite doesn't support authentication, so only pass DSN and options
        return new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function createMysqlDb(string $baseDsn): PDO
    {
        $dbname = $this->config("db.dbname");
        $dsn = $baseDsn . ";dbname={$dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ];

        return new PDO(
            $dsn,
            $this->config("db.user"),
            $this->config("db.pass"),
            $options,
        );
    }

    public function getUnitOfWork(): UnitOfWorkInterface
    {
        return new PdoUnitOfWork($this->getDb());
    }

    public function getLinkRepository(): LinkRepositoryInterface
    {
        return new PdoLinkRepository($this->getDb(), new LinkEntityMapper());
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

    public function getLinkService(): LinkServiceInterface
    {
        return new LinkService(
            $this->getLinkRepository(),
            $this->getUnitOfWork(),
        );
    }

    public function getTagService(): TagServiceInterface
    {
        return new TagService(
            $this->getTagRepository(),
            $this->getLinkRepository(),
            $this->getUnitOfWork(),
        );
    }

    public function getCategoryService(): CategoryServiceInterface
    {
        return new CategoryService(
            $this->getCategoryRepository(),
            $this->getDashboardRepository(),
            $this->getUnitOfWork(),
        );
    }

    public function getDashboardService(): DashboardServiceInterface
    {
        return new DashboardService(
            $this->getDashboardRepository(),
            $this->getCategoryRepository(),
            $this->getFavoriteRepository(),
            $this->getUnitOfWork(),
        );
    }

    public function getFavoriteService(): FavoriteServiceInterface
    {
        return new FavoriteService(
            $this->getFavoriteRepository(),
            $this->getDashboardRepository(),
            $this->getLinkRepository(),
            $this->getUnitOfWork(),
        );
    }

    public function getPasswordHasher(): PasswordHasherInterface
    {
        return new PhpPasswordHasher();
    }

    public function getUserRepository(): UserRepositoryInterface
    {
        $storageType = $this->config("users.storage.type");

        return match ($storageType) {
            "json" => new JsonUserRepository(
                $this->config("users.storage.path"),
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

    public function getUserService(): UserServiceInterface
    {
        return new UserService(
            $this->getUserRepository(),
            $this->getPasswordHasher(),
            $this->getUnitOfWork(),
        );
    }

    public function getClock(): ClockInterface
    {
        return Clock::get();
    }

    public function getTotpVerifier(): TotpVerifierInterface
    {
        return new OtphpTotpVerifier($this->getClock(), window: 1);
    }

    public function getJwtConfiguration(): Configuration
    {
        $jwtSecret = $this->config("auth.jwt_secret");
        if (!$jwtSecret) {
            throw new IncompleteConfigException(
                "JWT secret is not configured. Set auth.jwt_secret in config.",
            );
        }

        // Enforce minimum key length for HS256 (256 bits = 32 bytes)
        if (\strlen($jwtSecret) < 32) {
            throw new IncompleteConfigException(
                "JWT secret must be at least 32 bytes (256 bits) for HS256. Current length: " .
                    \strlen($jwtSecret) .
                    " bytes.",
            );
        }

        return Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($jwtSecret),
        )
            // Clear default validation constraints to use only our custom ones
            ->withValidationConstraints();
    }

    public function getJwtJtiRepository(): JwtJtiRepositoryInterface
    {
        return new PdoJwtJtiRepository($this->getDb());
    }

    public function getJwtService(): JwtServiceInterface
    {
        return new LcobucciJwtService(
            $this->getJwtConfiguration(),
            $this->config("auth.application_name"),
            $this->config("auth.session_ttl"),
            $this->config("auth.remember_me_ttl"),
            $this->getClock(),
            $this->getJwtJtiRepository(),
        );
    }
}
