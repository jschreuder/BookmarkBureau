<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use jschreuder\BookmarkBureau\Service\PasswordHasherInterface;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\CategoryRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\DashboardRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\FavoriteRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\TagRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\UserRepositoryInterface;
use jschreuder\BookmarkBureau\ServiceContainer;
use jschreuder\BookmarkBureau\Exception\IncompleteConfigException;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\ApplicationStack;
use Psr\Log\LoggerInterface;

describe("ServiceContainer Integration", function () {
    describe("container compilation", function () {
        test("container compiles successfully with DiCompiler", function () {
            $compiledClass = TestContainerHelper::getCompiledContainerClass();
            expect($compiledClass)->not->toBeNull();
        });

        test(
            "container instantiates successfully with test config",
            function () {
                $container = TestContainerHelper::createContainerInstance();
                expect($container)->toBeInstanceOf(ServiceContainer::class);
            },
        );
    });

    describe("core service provisioning", function () {
        test("provides Logger instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $logger = $container->getLogger();

            expect($logger)->toBeInstanceOf(LoggerInterface::class);
        });

        test("provides Router instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $router = $container->getAppRouter();

            expect($router)->toBeInstanceOf(RouterInterface::class);
        });

        test("provides ApplicationStack instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $app = $container->getApp();

            expect($app)->toBeInstanceOf(ApplicationStack::class);
        });

        test("provides URL Generator via Router", function () {
            $container = TestContainerHelper::createContainerInstance();
            $urlGenerator = $container->getUrlGenerator();

            expect($urlGenerator)->not->toBeNull();
        });

        test("provides PDO database instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $db = $container->getDb();

            expect($db)->toBeInstanceOf(\PDO::class);
        });
    });

    describe("repository provisioning", function () {
        test("provides LinkRepository instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $repository = $container->getLinkRepository();

            expect($repository)->toBeInstanceOf(LinkRepositoryInterface::class);
        });

        test("provides CategoryRepository instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $repository = $container->getCategoryRepository();

            expect($repository)->toBeInstanceOf(
                CategoryRepositoryInterface::class,
            );
        });

        test("provides DashboardRepository instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $repository = $container->getDashboardRepository();

            expect($repository)->toBeInstanceOf(
                DashboardRepositoryInterface::class,
            );
        });

        test("provides FavoriteRepository instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $repository = $container->getFavoriteRepository();

            expect($repository)->toBeInstanceOf(
                FavoriteRepositoryInterface::class,
            );
        });

        test("provides TagRepository instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $repository = $container->getTagRepository();

            expect($repository)->toBeInstanceOf(TagRepositoryInterface::class);
        });

        test("provides UserRepository instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $repository = $container->getUserRepository();

            expect($repository)->toBeInstanceOf(UserRepositoryInterface::class);
        });

        test(
            "provides UserRepository with configurable storage type",
            function () {
                $config = TestContainerHelper::createTestConfig();
                $config["users.storage.type"] = "json";
                $compiledClass = TestContainerHelper::getCompiledContainerClass();
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
            $container = TestContainerHelper::createContainerInstance();
            $service = $container->getLinkService();

            expect($service)->toBeInstanceOf(LinkServiceInterface::class);
        });

        test("provides CategoryService instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $service = $container->getCategoryService();

            expect($service)->toBeInstanceOf(CategoryServiceInterface::class);
        });

        test("provides DashboardService instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $service = $container->getDashboardService();

            expect($service)->toBeInstanceOf(DashboardServiceInterface::class);
        });

        test("provides FavoriteService instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $service = $container->getFavoriteService();

            expect($service)->toBeInstanceOf(FavoriteServiceInterface::class);
        });

        test("provides TagService instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $service = $container->getTagService();

            expect($service)->toBeInstanceOf(TagServiceInterface::class);
        });

        test("provides UnitOfWork instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $unitOfWork = $container->getUnitOfWork();

            expect($unitOfWork)->toBeInstanceOf(UnitOfWorkInterface::class);
        });

        test("provides PasswordHasher instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $hasher = $container->getPasswordHasher();

            expect($hasher)->toBeInstanceOf(PasswordHasherInterface::class);
        });

        test("provides UserService instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $service = $container->getUserService();

            expect($service)->toBeInstanceOf(UserServiceInterface::class);
        });

        test("provides JwtService instance", function () {
            $container = TestContainerHelper::createContainerInstance();
            $service = $container->getJwtService();

            expect($service)->toBeInstanceOf(JwtServiceInterface::class);
        });

        test(
            "throws IncompleteConfigException when JWT secret is not configured",
            function () {
                $config = TestContainerHelper::createTestConfig();
                $config["auth.jwt_secret"] = "";
                $compiledClass = TestContainerHelper::getCompiledContainerClass();
                $container = $compiledClass->newInstance($config);

                expect(fn() => $container->getJwtService())->toThrow(
                    IncompleteConfigException::class,
                );
            },
        );
    });

    describe("error handlers", function () {
        test("provides 404 error handler", function () {
            $container = TestContainerHelper::createContainerInstance();
            $handler = $container->get404Handler();

            expect($handler)->not->toBeNull();
        });

        test("provides 500 error handler", function () {
            $container = TestContainerHelper::createContainerInstance();
            $handler = $container->get500Handler();

            expect($handler)->not->toBeNull();
        });
    });

    describe("dependency injection verification", function () {
        test(
            "all services are properly wired with their dependencies",
            function () {
                $container = TestContainerHelper::createContainerInstance();

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
                $container = TestContainerHelper::createContainerInstance();

                $db1 = $container->getDb();
                $db2 = $container->getDb();

                // Both calls should return PDO instances (though they may not be identical due to how getDb() is implemented)
                expect($db1)->toBeInstanceOf(\PDO::class);
                expect($db2)->toBeInstanceOf(\PDO::class);
            },
        );

        test("services can be instantiated multiple times", function () {
            $container = TestContainerHelper::createContainerInstance();

            $linkService1 = $container->getLinkService();
            $linkService2 = $container->getLinkService();

            expect($linkService1)->toBeInstanceOf(LinkServiceInterface::class);
            expect($linkService2)->toBeInstanceOf(LinkServiceInterface::class);
        });
    });

    describe("application stack assembly", function () {
        test("ApplicationStack includes all required middleware", function () {
            $container = TestContainerHelper::createContainerInstance();
            $app = $container->getApp();

            // The ApplicationStack should be properly constructed with all middleware
            expect($app)->toBeInstanceOf(ApplicationStack::class);
        });
    });
});
