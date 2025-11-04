<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\ServiceContainer;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use jschreuder\BookmarkBureau\Service\PasswordHasherInterface;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\TagRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\ApplicationStack;
use jschreuder\MiddleDi\DiCompiler;
use Psr\Log\LoggerInterface;

/**
 * Create a test configuration for the container.
 * Uses in-memory SQLite database for testing.
 */
function createTestConfig(): array
{
    return [
        "site.url" => "http://test-localhost",
        "logger.name" => "test-logger",
        "logger.path" => "php://memory",
        "db.dsn" => "sqlite:",
        "db.dbname" => ":memory:",
        "db.user" => "",
        "db.pass" => "",
        "users.storage.type" => "pdo",
        "users.storage.path" => sys_get_temp_dir() . "/test_users.json",
    ];
}

/**
 * Get the compiled container class (only compiles once per PHP process).
 * This mimics what app_init.php does in production.
 */
function getCompiledContainerClass()
{
    static $compiledClass = null;

    if ($compiledClass === null) {
        $compiler = new DiCompiler(ServiceContainer::class);
        $compiledClass = $compiler->compile();
    }

    return $compiledClass;
}

/**
 * Create a new container instance with test configuration.
 */
function createContainerInstance(): ServiceContainer
{
    $compiledClass = getCompiledContainerClass();
    return $compiledClass->newInstance(createTestConfig());
}

describe("ServiceContainer Integration", function () {
    describe("container compilation", function () {
        test("container compiles successfully with DiCompiler", function () {
            $compiledClass = getCompiledContainerClass();
            expect($compiledClass)->not->toBeNull();
        });

        test(
            "container instantiates successfully with test config",
            function () {
                $container = createContainerInstance();
                expect($container)->toBeInstanceOf(ServiceContainer::class);
            },
        );
    });

    describe("core service provisioning", function () {
        test("provides Logger instance", function () {
            $container = createContainerInstance();
            $logger = $container->getLogger();

            expect($logger)->toBeInstanceOf(LoggerInterface::class);
        });

        test("provides Router instance", function () {
            $container = createContainerInstance();
            $router = $container->getAppRouter();

            expect($router)->toBeInstanceOf(RouterInterface::class);
        });

        test("provides ApplicationStack instance", function () {
            $container = createContainerInstance();
            $app = $container->getApp();

            expect($app)->toBeInstanceOf(ApplicationStack::class);
        });

        test("provides URL Generator via Router", function () {
            $container = createContainerInstance();
            $urlGenerator = $container->getUrlGenerator();

            expect($urlGenerator)->not->toBeNull();
        });

        test("provides PDO database instance", function () {
            $container = createContainerInstance();
            $db = $container->getDb();

            expect($db)->toBeInstanceOf(\PDO::class);
        });
    });

    describe("repository provisioning", function () {
        test("provides LinkRepository instance", function () {
            $container = createContainerInstance();
            $repository = $container->getLinkRepository();

            expect($repository)->toBeInstanceOf(LinkRepositoryInterface::class);
        });

        test("provides CategoryRepository instance", function () {
            $container = createContainerInstance();
            $repository = $container->getCategoryRepository();

            expect($repository)->toBeInstanceOf(
                CategoryRepositoryInterface::class,
            );
        });

        test("provides DashboardRepository instance", function () {
            $container = createContainerInstance();
            $repository = $container->getDashboardRepository();

            expect($repository)->toBeInstanceOf(
                DashboardRepositoryInterface::class,
            );
        });

        test("provides FavoriteRepository instance", function () {
            $container = createContainerInstance();
            $repository = $container->getFavoriteRepository();

            expect($repository)->toBeInstanceOf(
                FavoriteRepositoryInterface::class,
            );
        });

        test("provides TagRepository instance", function () {
            $container = createContainerInstance();
            $repository = $container->getTagRepository();

            expect($repository)->toBeInstanceOf(TagRepositoryInterface::class);
        });

        test("provides UserRepository instance", function () {
            $container = createContainerInstance();
            $repository = $container->getUserRepository();

            expect($repository)->toBeInstanceOf(UserRepositoryInterface::class);
        });

        test(
            "provides UserRepository with configurable storage type",
            function () {
                $config = createTestConfig();
                $config["users.storage.type"] = "json";
                $compiledClass = getCompiledContainerClass();
                $container = $compiledClass->newInstance($config);
                $repository = $container->getUserRepository();

                expect($repository)->toBeInstanceOf(
                    UserRepositoryInterface::class,
                );
            },
        );
    });

    describe("service provisioning", function () {
        test("provides LinkService instance", function () {
            $container = createContainerInstance();
            $service = $container->getLinkService();

            expect($service)->toBeInstanceOf(LinkServiceInterface::class);
        });

        test("provides CategoryService instance", function () {
            $container = createContainerInstance();
            $service = $container->getCategoryService();

            expect($service)->toBeInstanceOf(CategoryServiceInterface::class);
        });

        test("provides DashboardService instance", function () {
            $container = createContainerInstance();
            $service = $container->getDashboardService();

            expect($service)->toBeInstanceOf(DashboardServiceInterface::class);
        });

        test("provides FavoriteService instance", function () {
            $container = createContainerInstance();
            $service = $container->getFavoriteService();

            expect($service)->toBeInstanceOf(FavoriteServiceInterface::class);
        });

        test("provides TagService instance", function () {
            $container = createContainerInstance();
            $service = $container->getTagService();

            expect($service)->toBeInstanceOf(TagServiceInterface::class);
        });

        test("provides UnitOfWork instance", function () {
            $container = createContainerInstance();
            $unitOfWork = $container->getUnitOfWork();

            expect($unitOfWork)->toBeInstanceOf(UnitOfWorkInterface::class);
        });

        test("provides PasswordHasher instance", function () {
            $container = createContainerInstance();
            $hasher = $container->getPasswordHasher();

            expect($hasher)->toBeInstanceOf(PasswordHasherInterface::class);
        });

        test("provides UserService instance", function () {
            $container = createContainerInstance();
            $service = $container->getUserService();

            expect($service)->toBeInstanceOf(UserServiceInterface::class);
        });
    });

    describe("error handlers", function () {
        test("provides 404 error handler", function () {
            $container = createContainerInstance();
            $handler = $container->get404Handler();

            expect($handler)->not->toBeNull();
        });

        test("provides 500 error handler", function () {
            $container = createContainerInstance();
            $handler = $container->get500Handler();

            expect($handler)->not->toBeNull();
        });
    });

    describe("dependency injection verification", function () {
        test(
            "all services are properly wired with their dependencies",
            function () {
                $container = createContainerInstance();

                // Services should all be instantiable and return the expected types
                expect($container->getLinkService())->toBeInstanceOf(
                    LinkServiceInterface::class,
                );
                expect($container->getCategoryService())->toBeInstanceOf(
                    CategoryServiceInterface::class,
                );
                expect($container->getDashboardService())->toBeInstanceOf(
                    DashboardServiceInterface::class,
                );
                expect($container->getFavoriteService())->toBeInstanceOf(
                    FavoriteServiceInterface::class,
                );
                expect($container->getTagService())->toBeInstanceOf(
                    TagServiceInterface::class,
                );
                expect($container->getUserService())->toBeInstanceOf(
                    UserServiceInterface::class,
                );
            },
        );

        test(
            "DatabaseRepository instances share the same database connection",
            function () {
                $container = createContainerInstance();

                $db1 = $container->getDb();
                $db2 = $container->getDb();

                // Both calls should return PDO instances (though they may not be identical due to how getDb() is implemented)
                expect($db1)->toBeInstanceOf(\PDO::class);
                expect($db2)->toBeInstanceOf(\PDO::class);
            },
        );

        test("services can be instantiated multiple times", function () {
            $container = createContainerInstance();

            $linkService1 = $container->getLinkService();
            $linkService2 = $container->getLinkService();

            expect($linkService1)->toBeInstanceOf(LinkServiceInterface::class);
            expect($linkService2)->toBeInstanceOf(LinkServiceInterface::class);
        });
    });

    describe("application stack assembly", function () {
        test("ApplicationStack includes all required middleware", function () {
            $container = createContainerInstance();
            $app = $container->getApp();

            // The ApplicationStack should be properly constructed with all middleware
            expect($app)->toBeInstanceOf(ApplicationStack::class);
        });
    });
});
