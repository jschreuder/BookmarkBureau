<?php declare(strict_types = 1);

use jschreuder\BookmarkBureau\GeneralRoutingProvider;
use jschreuder\BookmarkBureau\ServiceContainer;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Controller\ControllerInterface;

describe('GeneralRoutingProvider', function () {
    function createMockContainer() {
        $container = Mockery::mock(ServiceContainer::class);
        $container->shouldReceive('getLinkService')->andReturn(Mockery::mock(LinkServiceInterface::class));
        $container->shouldReceive('getCategoryService')->andReturn(Mockery::mock(CategoryServiceInterface::class));
        $container->shouldReceive('getDashboardService')->andReturn(Mockery::mock(DashboardServiceInterface::class));
        $container->shouldReceive('getFavoriteService')->andReturn(Mockery::mock(FavoriteServiceInterface::class));
        $container->shouldReceive('getTagService')->andReturn(Mockery::mock(TagServiceInterface::class));
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

            // Tag routes
            expect($registeredRoutes['tag-read'])->toBe(['method' => 'GET', 'path' => '/tag/:id']);
            expect($registeredRoutes['tag-create'])->toBe(['method' => 'POST', 'path' => '/tag']);
            expect($registeredRoutes['tag-update'])->toBe(['method' => 'PUT', 'path' => '/tag/:id']);
            expect($registeredRoutes['tag-delete'])->toBe(['method' => 'DELETE', 'path' => '/tag/:id']);

            // Link-Tag routes
            expect($registeredRoutes['link_tag-create'])->toBe(['method' => 'POST', 'path' => '/link/:id/tag']);
            expect($registeredRoutes['link_tag-delete'])->toBe(['method' => 'DELETE', 'path' => '/link/:id/tag/:tag_name']);

            // Home route
            expect($registeredRoutes['home'])->toBe(['method' => 'GET', 'path' => '/']);
        });
    });

    describe('service container interaction', function () {
        test('does not fetch services during route registration (lazy loading)', function () {
            $router = Mockery::mock(RouterInterface::class);
            $router->shouldReceive('get', 'post', 'put', 'delete')->andReturnNull();

            $container = Mockery::mock(ServiceContainer::class);
            $container->shouldReceive('getLinkService')->never();
            $container->shouldReceive('getCategoryService')->never();
            $container->shouldReceive('getDashboardService')->never();
            $container->shouldReceive('getFavoriteService')->never();
            $container->shouldReceive('getTagService')->never();

            $provider = new GeneralRoutingProvider($container);
            $provider->registerRoutes($router);
        });

        test('fetches services when route handler is invoked', function () {
            $router = Mockery::mock(RouterInterface::class);
            $capturedFactory = null;

            $router->shouldReceive('get')
                ->andReturnUsing(function($name, $_path, $factory) use (&$capturedFactory, $router) {
                    if ($name === 'link-read') {
                        $capturedFactory = $factory;
                    }
                    return $router;
                });
            $router->shouldReceive('post', 'put', 'delete')->andReturn($router);

            $linkService = Mockery::mock(LinkServiceInterface::class);
            $container = Mockery::mock(ServiceContainer::class);
            $container->shouldReceive('getLinkService')
                ->andReturn($linkService);
            $container->shouldReceive('getCategoryService')->andReturn(Mockery::mock(CategoryServiceInterface::class));
            $container->shouldReceive('getDashboardService')->andReturn(Mockery::mock(DashboardServiceInterface::class));
            $container->shouldReceive('getFavoriteService')->andReturn(Mockery::mock(FavoriteServiceInterface::class));
            $container->shouldReceive('getTagService')->andReturn(Mockery::mock(TagServiceInterface::class));

            $provider = new GeneralRoutingProvider($container);
            $provider->registerRoutes($router);

            // Now invoke the route handler - should return a controller
            expect($capturedFactory)->not->toBeNull();
            $controller = $capturedFactory();
            expect($controller)->toBeInstanceOf(ControllerInterface::class);
        });
    });
});
