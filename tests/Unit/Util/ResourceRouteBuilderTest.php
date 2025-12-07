<?php

use jschreuder\BookmarkBureau\Action\ActionInterface;
use jschreuder\BookmarkBureau\Controller\ActionController;
use jschreuder\BookmarkBureau\Response\JsonResponseTransformer;
use jschreuder\BookmarkBureau\Util\ResourceRouteBuilder;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouterInterface;

describe("ResourceRouteBuilder", function () {
    describe("initialization", function () {
        test(
            "creates builder with router, resource name, and path segment",
            function () {
                $router = Mockery::mock(RouterInterface::class);
                $builder = new ResourceRouteBuilder(
                    $router,
                    "product",
                    "/product",
                );

                expect($builder)->toBeInstanceOf(ResourceRouteBuilder::class);
            },
        );
    });

    describe("registerList", function () {
        test("registers GET route with list action", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("get")
                ->with("product-list", "/product", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerList(fn() => $action);

            expect($result)->toBe($builder);
        });

        test("returns self for method chaining", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router->shouldReceive("get")->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerList(fn() => $action);

            expect($result)->toBeInstanceOf(ResourceRouteBuilder::class);
            expect($result)->toBe($builder);
        });

        test(
            "defers action instantiation until controller is invoked",
            function () {
                $router = Mockery::mock(RouterInterface::class);
                $capturedController = null;

                $router
                    ->shouldReceive("get")
                    ->with(
                        "item-list",
                        "/item",
                        Mockery::on(function ($controller) use (
                            &$capturedController,
                        ) {
                            $capturedController = $controller;
                            return true;
                        }),
                    )
                    ->once()
                    ->andReturn($router);

                $action = Mockery::mock(ActionInterface::class);
                $actionCalled = false;

                $actionFactory = function () use ($action, &$actionCalled) {
                    $actionCalled = true;
                    return $action;
                };

                $builder = new ResourceRouteBuilder($router, "item", "/item");
                $builder->registerList($actionFactory);

                expect($actionCalled)->toBeFalse();
                expect($capturedController)->not->toBeNull();

                $createdController = $capturedController();
                expect($createdController)->toBeInstanceOf(
                    ControllerInterface::class,
                );
                expect($actionCalled)->toBeTrue();
            },
        );
    });

    describe("registerRead", function () {
        test("registers GET route with read action", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("get")
                ->with("product-read", "/product/{id}", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerRead(fn() => $action);

            expect($result)->toBe($builder);
        });

        test("returns self for method chaining", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router->shouldReceive("get")->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerRead(fn() => $action);

            expect($result)->toBeInstanceOf(ResourceRouteBuilder::class);
            expect($result)->toBe($builder);
        });

        test(
            "passes closure to router that creates action on invocation",
            function () {
                $router = Mockery::mock(RouterInterface::class);
                $capturedController = null;

                $router
                    ->shouldReceive("get")
                    ->with(
                        "item-read",
                        "/item/{id}",
                        Mockery::on(function ($controller) use (
                            &$capturedController,
                        ) {
                            $capturedController = $controller;
                            return true;
                        }),
                    )
                    ->once()
                    ->andReturn($router);

                $action = Mockery::mock(ActionInterface::class);
                $actionCalled = false;

                $actionFactory = function () use ($action, &$actionCalled) {
                    $actionCalled = true;
                    return $action;
                };

                $builder = new ResourceRouteBuilder($router, "item", "/item");
                $builder->registerRead($actionFactory);

                expect($actionCalled)->toBeFalse();
                expect($capturedController)->not->toBeNull();

                $createdController = $capturedController();
                expect($createdController)->toBeInstanceOf(
                    ControllerInterface::class,
                );
                expect($actionCalled)->toBeTrue();
            },
        );
    });

    describe("registerCreate", function () {
        test("registers POST route with create action", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("post")
                ->with("product-create", "/product", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerCreate(fn() => $action);

            expect($result)->toBe($builder);
        });

        test("returns self for method chaining", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router->shouldReceive("post")->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerCreate(fn() => $action);

            expect($result)->toBeInstanceOf(ResourceRouteBuilder::class);
            expect($result)->toBe($builder);
        });

        test(
            "defers action instantiation until controller is invoked",
            function () {
                $router = Mockery::mock(RouterInterface::class);
                $capturedController = null;

                $router
                    ->shouldReceive("post")
                    ->with(
                        "item-create",
                        "/item",
                        Mockery::on(function ($controller) use (
                            &$capturedController,
                        ) {
                            $capturedController = $controller;
                            return true;
                        }),
                    )
                    ->once()
                    ->andReturn($router);

                $action = Mockery::mock(ActionInterface::class);
                $actionCalled = false;

                $actionFactory = function () use ($action, &$actionCalled) {
                    $actionCalled = true;
                    return $action;
                };

                $builder = new ResourceRouteBuilder($router, "item", "/item");
                $builder->registerCreate($actionFactory);

                expect($actionCalled)->toBeFalse();

                $createdController = $capturedController();
                expect($createdController)->toBeInstanceOf(
                    ControllerInterface::class,
                );
                expect($actionCalled)->toBeTrue();
            },
        );
    });

    describe("registerUpdate", function () {
        test("registers PUT route with update action", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("put")
                ->with("product-update", "/product/{id}", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerUpdate(fn() => $action);

            expect($result)->toBe($builder);
        });

        test("returns self for method chaining", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router->shouldReceive("put")->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerUpdate(fn() => $action);

            expect($result)->toBeInstanceOf(ResourceRouteBuilder::class);
            expect($result)->toBe($builder);
        });

        test(
            "defers action instantiation until controller is invoked",
            function () {
                $router = Mockery::mock(RouterInterface::class);
                $capturedController = null;

                $router
                    ->shouldReceive("put")
                    ->with(
                        "item-update",
                        "/item/{id}",
                        Mockery::on(function ($controller) use (
                            &$capturedController,
                        ) {
                            $capturedController = $controller;
                            return true;
                        }),
                    )
                    ->once()
                    ->andReturn($router);

                $action = Mockery::mock(ActionInterface::class);
                $actionCalled = false;

                $actionFactory = function () use ($action, &$actionCalled) {
                    $actionCalled = true;
                    return $action;
                };

                $builder = new ResourceRouteBuilder($router, "item", "/item");
                $builder->registerUpdate($actionFactory);

                expect($actionCalled)->toBeFalse();

                $createdController = $capturedController();
                expect($createdController)->toBeInstanceOf(
                    ControllerInterface::class,
                );
                expect($actionCalled)->toBeTrue();
            },
        );
    });

    describe("registerDelete", function () {
        test("registers DELETE route with delete action", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("delete")
                ->with("product-delete", "/product/{id}", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerDelete(fn() => $action);

            expect($result)->toBe($builder);
        });

        test("returns self for method chaining", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router->shouldReceive("delete")->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerDelete(fn() => $action);

            expect($result)->toBeInstanceOf(ResourceRouteBuilder::class);
            expect($result)->toBe($builder);
        });

        test(
            "defers action instantiation until controller is invoked",
            function () {
                $router = Mockery::mock(RouterInterface::class);
                $capturedController = null;

                $router
                    ->shouldReceive("delete")
                    ->with(
                        "item-delete",
                        "/item/{id}",
                        Mockery::on(function ($controller) use (
                            &$capturedController,
                        ) {
                            $capturedController = $controller;
                            return true;
                        }),
                    )
                    ->once()
                    ->andReturn($router);

                $action = Mockery::mock(ActionInterface::class);
                $actionCalled = false;

                $actionFactory = function () use ($action, &$actionCalled) {
                    $actionCalled = true;
                    return $action;
                };

                $builder = new ResourceRouteBuilder($router, "item", "/item");
                $builder->registerDelete($actionFactory);

                expect($actionCalled)->toBeFalse();

                $createdController = $capturedController();
                expect($createdController)->toBeInstanceOf(
                    ControllerInterface::class,
                );
                expect($actionCalled)->toBeTrue();
            },
        );
    });

    describe("registerCustom", function () {
        test("registers custom GET route", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("get")
                ->with("product-list", "/product/list", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerCustom(
                "GET",
                "list",
                "/list",
                fn() => $action,
            );

            expect($result)->toBe($builder);
        });

        test("registers custom POST route", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("post")
                ->with("product-bulk", "/product/bulk", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerCustom(
                "POST",
                "bulk",
                "/bulk",
                fn() => $action,
            );

            expect($result)->toBe($builder);
        });

        test("registers custom PUT route", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("put")
                ->with("product-sync", "/product/sync", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerCustom(
                "PUT",
                "sync",
                "/sync",
                fn() => $action,
            );

            expect($result)->toBe($builder);
        });

        test("registers custom DELETE route", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("delete")
                ->with("product-purge", "/product/purge", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerCustom(
                "DELETE",
                "purge",
                "/purge",
                fn() => $action,
            );

            expect($result)->toBe($builder);
        });

        test("registers custom PATCH route", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("patch")
                ->with("product-partial", "/product/partial", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerCustom(
                "PATCH",
                "partial",
                "/partial",
                fn() => $action,
            );

            expect($result)->toBe($builder);
        });

        test(
            "uses resource name as route name when suffix is empty",
            function () {
                $router = Mockery::mock(RouterInterface::class);
                $router
                    ->shouldReceive("get")
                    ->with("product", "/product", Mockery::any())
                    ->once()
                    ->andReturn($router);

                $action = Mockery::mock(ActionInterface::class);
                $builder = new ResourceRouteBuilder(
                    $router,
                    "product",
                    "/product",
                );

                $builder->registerCustom("GET", "", "", fn() => $action);
            },
        );

        test("appends path suffix to resource path", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("get")
                ->with("item-search", "/item/search", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "item", "/item");

            $builder->registerCustom(
                "GET",
                "search",
                "/search",
                fn() => $action,
            );
        });

        test("case insensitive HTTP method matching", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("get")
                ->with("product-list", "/product/list", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $builder->registerCustom("get", "list", "/list", fn() => $action);
        });

        test("throws exception for unsupported HTTP method", function () {
            $router = Mockery::mock(RouterInterface::class);
            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            expect(
                fn() => $builder->registerCustom(
                    "HEAD",
                    "test",
                    "/test",
                    fn() => $action,
                ),
            )->toThrow(
                InvalidArgumentException::class,
                "Unsupported HTTP method: HEAD",
            );
        });

        test("returns self for method chaining", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router->shouldReceive("post")->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder->registerCustom(
                "POST",
                "custom",
                "/custom",
                fn() => $action,
            );

            expect($result)->toBeInstanceOf(ResourceRouteBuilder::class);
            expect($result)->toBe($builder);
        });

        test(
            "defers action instantiation until controller is invoked",
            function () {
                $router = Mockery::mock(RouterInterface::class);
                $capturedController = null;

                $router
                    ->shouldReceive("post")
                    ->with(
                        "item-action",
                        "/item/action",
                        Mockery::on(function ($controller) use (
                            &$capturedController,
                        ) {
                            $capturedController = $controller;
                            return true;
                        }),
                    )
                    ->once()
                    ->andReturn($router);

                $action = Mockery::mock(ActionInterface::class);
                $actionCalled = false;

                $actionFactory = function () use ($action, &$actionCalled) {
                    $actionCalled = true;
                    return $action;
                };

                $builder = new ResourceRouteBuilder($router, "item", "/item");
                $builder->registerCustom(
                    "POST",
                    "action",
                    "/action",
                    $actionFactory,
                );

                expect($actionCalled)->toBeFalse();

                $createdController = $capturedController();
                expect($createdController)->toBeInstanceOf(
                    ControllerInterface::class,
                );
                expect($actionCalled)->toBeTrue();
            },
        );
    });

    describe("method chaining", function () {
        test("chains multiple standard CRUD registrations", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router->shouldReceive("get")->andReturn($router);
            $router->shouldReceive("post")->andReturn($router);
            $router->shouldReceive("put")->andReturn($router);
            $router->shouldReceive("delete")->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder
                ->registerRead(fn() => $action)
                ->registerCreate(fn() => $action)
                ->registerUpdate(fn() => $action)
                ->registerDelete(fn() => $action);

            expect($result)->toBe($builder);
        });

        test("chains CRUD with custom routes", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router->shouldReceive("get")->andReturn($router);
            $router->shouldReceive("post")->andReturn($router);
            $router->shouldReceive("put")->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");

            $result = $builder
                ->registerRead(fn() => $action)
                ->registerCreate(fn() => $action)
                ->registerCustom("PUT", "reorder", "/reorder", fn() => $action);

            expect($result)->toBe($builder);
        });

        test("verifies all registrations were called", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("get")
                ->with("item-read", "/item/{id}", Mockery::any())
                ->once();
            $router
                ->shouldReceive("post")
                ->with("item-create", "/item", Mockery::any())
                ->once();
            $router
                ->shouldReceive("put")
                ->with("item-custom", "/item/custom", Mockery::any())
                ->once();

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "item", "/item");

            $builder
                ->registerRead(fn() => $action)
                ->registerCreate(fn() => $action)
                ->registerCustom("PUT", "custom", "/custom", fn() => $action);

            // If we got here without exceptions, all registrations were called correctly
            expect(true)->toBeTrue();
        });
    });

    describe("lazy loading behavior", function () {
        test(
            "action factory not called during route registration",
            function () {
                $router = Mockery::mock(RouterInterface::class);
                $router->shouldReceive("get")->andReturn($router);

                $actionCalled = false;
                $actionFactory = function () use (&$actionCalled) {
                    $actionCalled = true;
                    return Mockery::mock(ActionInterface::class);
                };

                $builder = new ResourceRouteBuilder(
                    $router,
                    "product",
                    "/product",
                );
                $builder->registerRead($actionFactory);

                expect($actionCalled)->toBeFalse();
            },
        );

        test("action factory called when controller is invoked", function () {
            $router = Mockery::mock(RouterInterface::class);
            $capturedController = null;
            $router
                ->shouldReceive("get")
                ->with(
                    "product-read",
                    "/product/{id}",
                    Mockery::on(function ($controller) use (
                        &$capturedController,
                    ) {
                        $capturedController = $controller;
                        return true;
                    }),
                )
                ->andReturn($router);

            $actionCalled = false;
            $action = Mockery::mock(ActionInterface::class);
            $actionFactory = function () use (&$actionCalled, $action) {
                $actionCalled = true;
                return $action;
            };

            $builder = new ResourceRouteBuilder($router, "product", "/product");
            $builder->registerRead($actionFactory);

            // Before controller invocation
            expect($actionCalled)->toBeFalse();

            // After controller invocation
            $controller = $capturedController();
            expect($actionCalled)->toBeTrue();
            expect($controller)->toBeInstanceOf(ActionController::class);
        });

        test(
            "action factory called once per controller invocation",
            function () {
                $router = Mockery::mock(RouterInterface::class);
                $capturedController = null;
                $router
                    ->shouldReceive("get")
                    ->with(
                        "product-read",
                        "/product/{id}",
                        Mockery::on(function ($controller) use (
                            &$capturedController,
                        ) {
                            $capturedController = $controller;
                            return true;
                        }),
                    )
                    ->andReturn($router);

                $callCount = 0;
                $action = Mockery::mock(ActionInterface::class);
                $actionFactory = function () use (&$callCount, $action) {
                    $callCount++;
                    return $action;
                };

                $builder = new ResourceRouteBuilder(
                    $router,
                    "product",
                    "/product",
                );
                $builder->registerRead($actionFactory);

                $controller = $capturedController();
                expect($callCount)->toBe(1);

                // Calling controller again should call factory again
                $controller = $capturedController();
                expect($callCount)->toBe(2);
            },
        );

        test("independent lazy loading for each registered route", function () {
            $router = Mockery::mock(RouterInterface::class);
            $readController = null;
            $createController = null;

            $router
                ->shouldReceive("get")
                ->with(
                    "product-read",
                    "/product/{id}",
                    Mockery::on(function ($controller) use (&$readController) {
                        $readController = $controller;
                        return true;
                    }),
                )
                ->andReturn($router);

            $router
                ->shouldReceive("post")
                ->with(
                    "product-create",
                    "/product",
                    Mockery::on(function ($controller) use (
                        &$createController,
                    ) {
                        $createController = $controller;
                        return true;
                    }),
                )
                ->andReturn($router);

            $readCalled = false;
            $createCalled = false;

            $readFactory = function () use (&$readCalled) {
                $readCalled = true;
                return Mockery::mock(ActionInterface::class);
            };

            $createFactory = function () use (&$createCalled) {
                $createCalled = true;
                return Mockery::mock(ActionInterface::class);
            };

            $builder = new ResourceRouteBuilder($router, "product", "/product");
            $builder->registerRead($readFactory);
            $builder->registerCreate($createFactory);

            expect($readCalled)->toBeFalse();
            expect($createCalled)->toBeFalse();

            // Invoke read controller
            $readController();
            expect($readCalled)->toBeTrue();
            expect($createCalled)->toBeFalse();

            // Invoke create controller
            $createController();
            expect($readCalled)->toBeTrue();
            expect($createCalled)->toBeTrue();
        });
    });

    describe("routing configuration", function () {
        test("read route includes ID segment in path", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("get")
                ->with("product-read", "/product/{id}", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");
            $builder->registerRead(fn() => $action);
        });

        test("create route does not include ID segment", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("post")
                ->with("product-create", "/product", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");
            $builder->registerCreate(fn() => $action);
        });

        test("update route includes ID segment in path", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("put")
                ->with("product-update", "/product/{id}", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");
            $builder->registerUpdate(fn() => $action);
        });

        test("delete route includes ID segment in path", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("delete")
                ->with("product-delete", "/product/{id}", Mockery::any())
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder($router, "product", "/product");
            $builder->registerDelete(fn() => $action);
        });

        test("custom route with nested path segment", function () {
            $router = Mockery::mock(RouterInterface::class);
            $router
                ->shouldReceive("get")
                ->with(
                    "dashboard-favorite",
                    "/dashboard/{id}/favorites",
                    Mockery::any(),
                )
                ->once()
                ->andReturn($router);

            $action = Mockery::mock(ActionInterface::class);
            $builder = new ResourceRouteBuilder(
                $router,
                "dashboard",
                "/dashboard/{id}",
            );
            $builder->registerCustom(
                "GET",
                "favorite",
                "/favorites",
                fn() => $action,
            );
        });
    });

    describe("action controller creation", function () {
        test(
            "controller wraps action with JsonResponseTransformer",
            function () {
                $router = Mockery::mock(RouterInterface::class);
                $capturedController = null;
                $router
                    ->shouldReceive("post")
                    ->with(
                        "product-create",
                        "/product",
                        Mockery::on(function ($controller) use (
                            &$capturedController,
                        ) {
                            $capturedController = $controller;
                            return true;
                        }),
                    )
                    ->andReturn($router);

                $action = Mockery::mock(ActionInterface::class);
                $builder = new ResourceRouteBuilder(
                    $router,
                    "product",
                    "/product",
                );
                $builder->registerCreate(fn() => $action);

                $controller = $capturedController();
                expect($controller)->toBeInstanceOf(ActionController::class);
            },
        );
    });
});
