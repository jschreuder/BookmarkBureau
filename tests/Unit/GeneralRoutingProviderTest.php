<?php declare(strict_types = 1);

use jschreuder\BookmarkBureau\GeneralRoutingProvider;
use jschreuder\BookmarkBureau\ServiceContainer;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Controller\ControllerInterface;

describe('GeneralRoutingProvider', function () {
    function createMockContainer() {
        $container = Mockery::mock(ServiceContainer::class);
        $container->shouldReceive('getLinkService')->andReturn(Mockery::mock(LinkServiceInterface::class));
        $container->shouldReceive('getCategoryService')->andReturn(Mockery::mock(CategoryServiceInterface::class));
        $container->shouldReceive('getDashboardService')->andReturn(Mockery::mock(DashboardServiceInterface::class));
        $container->shouldReceive('getFavoriteService')->andReturn(Mockery::mock(FavoriteServiceInterface::class));
        return $container;
    }

    describe('route handler factories', function () {
        test('link-read route handler returns ActionController instance', function () {
            $router = Mockery::mock(RouterInterface::class);
            $capturedFactory = null;

            $router->shouldReceive('get')
                ->andReturnUsing(function($name, $path, $factory) use (&$capturedFactory) {
                    if ($name === 'link-read') {
                        $capturedFactory = $factory;
                    }
                });
            $router->shouldReceive('post', 'put', 'delete')->andReturnNull();

            $provider = new GeneralRoutingProvider(createMockContainer());
            $provider->registerRoutes($router);

            expect($capturedFactory)->not->toBeNull();
            $controller = $capturedFactory();
            expect($controller)->toBeInstanceOf(ControllerInterface::class);
        });

        test('category-create route handler returns ActionController instance', function () {
            $router = Mockery::mock(RouterInterface::class);
            $capturedFactory = null;

            $router->shouldReceive('post')
                ->andReturnUsing(function($name, $path, $factory) use (&$capturedFactory) {
                    if ($name === 'category-create') {
                        $capturedFactory = $factory;
                    }
                });
            $router->shouldReceive('get', 'put', 'delete')->andReturnNull();

            $provider = new GeneralRoutingProvider(createMockContainer());
            $provider->registerRoutes($router);

            $controller = $capturedFactory();
            expect($controller)->toBeInstanceOf(ControllerInterface::class);
        });

        test('dashboard-update route handler returns ActionController instance', function () {
            $router = Mockery::mock(RouterInterface::class);
            $capturedFactory = null;

            $router->shouldReceive('put')
                ->andReturnUsing(function($name, $path, $factory) use (&$capturedFactory) {
                    if ($name === 'dashboard-update') {
                        $capturedFactory = $factory;
                    }
                });
            $router->shouldReceive('get', 'post', 'delete')->andReturnNull();

            $provider = new GeneralRoutingProvider(createMockContainer());
            $provider->registerRoutes($router);

            $controller = $capturedFactory();
            expect($controller)->toBeInstanceOf(ControllerInterface::class);
        });

        test('favorite-reorder route handler returns ActionController instance', function () {
            $router = Mockery::mock(RouterInterface::class);
            $capturedFactory = null;

            $router->shouldReceive('put')
                ->andReturnUsing(function($name, $path, $factory) use (&$capturedFactory) {
                    if ($name === 'favorite-reorder') {
                        $capturedFactory = $factory;
                    }
                });
            $router->shouldReceive('get', 'post', 'delete')->andReturnNull();

            $provider = new GeneralRoutingProvider(createMockContainer());
            $provider->registerRoutes($router);

            $controller = $capturedFactory();
            expect($controller)->toBeInstanceOf(ControllerInterface::class);
        });
    });

    describe('route registration paths and methods', function () {
        test('registers routes with correct HTTP methods and paths', function () {
            $router = Mockery::mock(RouterInterface::class);
            $registeredRoutes = [];

            $router->shouldReceive('get')
                ->andReturnUsing(function($name, $path) use (&$registeredRoutes) {
                    $registeredRoutes[$name] = ['method' => 'GET', 'path' => $path];
                });
            $router->shouldReceive('post')
                ->andReturnUsing(function($name, $path) use (&$registeredRoutes) {
                    $registeredRoutes[$name] = ['method' => 'POST', 'path' => $path];
                });
            $router->shouldReceive('put')
                ->andReturnUsing(function($name, $path) use (&$registeredRoutes) {
                    $registeredRoutes[$name] = ['method' => 'PUT', 'path' => $path];
                });
            $router->shouldReceive('delete')
                ->andReturnUsing(function($name, $path) use (&$registeredRoutes) {
                    $registeredRoutes[$name] = ['method' => 'DELETE', 'path' => $path];
                });

            $provider = new GeneralRoutingProvider(createMockContainer());
            $provider->registerRoutes($router);

            // Link routes
            expect($registeredRoutes['link-read'])->toBe(['method' => 'GET', 'path' => '/link/:id']);
            expect($registeredRoutes['link-create'])->toBe(['method' => 'POST', 'path' => '/link']);
            expect($registeredRoutes['link-update'])->toBe(['method' => 'PUT', 'path' => '/link/:id']);
            expect($registeredRoutes['link-delete'])->toBe(['method' => 'DELETE', 'path' => '/link/:id']);

            // Category routes
            expect($registeredRoutes['category-read'])->toBe(['method' => 'GET', 'path' => '/category/:id']);
            expect($registeredRoutes['category-create'])->toBe(['method' => 'POST', 'path' => '/category']);
            expect($registeredRoutes['category-update'])->toBe(['method' => 'PUT', 'path' => '/category/:id']);
            expect($registeredRoutes['category-delete'])->toBe(['method' => 'DELETE', 'path' => '/category/:id']);

            // Dashboard routes (no read)
            expect($registeredRoutes['dashboard-create'])->toBe(['method' => 'POST', 'path' => '/dashboard']);
            expect($registeredRoutes['dashboard-update'])->toBe(['method' => 'PUT', 'path' => '/dashboard/:id']);
            expect($registeredRoutes['dashboard-delete'])->toBe(['method' => 'DELETE', 'path' => '/dashboard/:id']);

            // Favorite routes
            expect($registeredRoutes['favorite-create'])->toBe(['method' => 'POST', 'path' => '/dashboard/:id/favorites']);
            expect($registeredRoutes['favorite-delete'])->toBe(['method' => 'DELETE', 'path' => '/dashboard/:id/favorites']);
            expect($registeredRoutes['favorite-reorder'])->toBe(['method' => 'PUT', 'path' => '/dashboard/:id/favorites']);

            // Home route
            expect($registeredRoutes['home'])->toBe(['method' => 'GET', 'path' => '/']);
        });
    });

    describe('service container interaction', function () {
        test('fetches all services during route registration', function () {
            $router = Mockery::mock(RouterInterface::class);
            $router->shouldReceive('get', 'post', 'put', 'delete')->andReturnNull();

            $container = Mockery::mock(ServiceContainer::class);
            $container->shouldReceive('getLinkService')
                ->once()
                ->andReturn(Mockery::mock(LinkServiceInterface::class));
            $container->shouldReceive('getCategoryService')
                ->once()
                ->andReturn(Mockery::mock(CategoryServiceInterface::class));
            $container->shouldReceive('getDashboardService')
                ->once()
                ->andReturn(Mockery::mock(DashboardServiceInterface::class));
            $container->shouldReceive('getFavoriteService')
                ->once()
                ->andReturn(Mockery::mock(FavoriteServiceInterface::class));

            $provider = new GeneralRoutingProvider($container);
            $provider->registerRoutes($router);
        });
    });
});
