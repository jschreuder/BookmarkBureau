<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Middleware\RequireAuthenticationMiddleware;
use jschreuder\Middle\Exception\AuthenticationException;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;

describe("RequireAuthenticationMiddleware", function () {
    describe("process", function () {
        test("allows public routes without authentication", function () {
            $publicRoutes = ["home", "auth.login"];
            $middleware = new RequireAuthenticationMiddleware($publicRoutes);

            $request = new ServerRequest();
            $request = $request->withAttribute("route", "home");

            $handler = Mockery::mock(RequestHandlerInterface::class);
            $expectedResponse = new JsonResponse(["message" => "ok"]);
            $handler
                ->shouldReceive("handle")
                ->once()
                ->with($request)
                ->andReturn($expectedResponse);

            $response = $middleware->process($request, $handler);

            expect($response)->toBe($expectedResponse);
        });

        test("allows authenticated requests to protected routes", function () {
            $publicRoutes = ["home"];
            $middleware = new RequireAuthenticationMiddleware($publicRoutes);

            $userId = Uuid::uuid4();
            $request = new ServerRequest();
            $request = $request
                ->withAttribute("route", "dashboard.list")
                ->withAttribute("authenticatedUserId", $userId);

            $handler = Mockery::mock(RequestHandlerInterface::class);
            $expectedResponse = new JsonResponse(["data" => "protected"]);
            $handler
                ->shouldReceive("handle")
                ->once()
                ->with($request)
                ->andReturn($expectedResponse);

            $response = $middleware->process($request, $handler);

            expect($response)->toBe($expectedResponse);
        });

        test(
            "blocks unauthenticated requests to protected routes",
            function () {
                $publicRoutes = ["home"];
                $middleware = new RequireAuthenticationMiddleware(
                    $publicRoutes,
                );

                $request = new ServerRequest();
                $request = $request->withAttribute("route", "dashboard.list");
                // No authenticatedUser attribute

                $handler = Mockery::mock(RequestHandlerInterface::class);
                $handler->shouldNotReceive("handle");

                expect(
                    fn() => $middleware->process($request, $handler),
                )->toThrow(
                    AuthenticationException::class,
                    "Authentication required",
                );
            },
        );

        test(
            "blocks requests to protected routes when authenticatedUser is null",
            function () {
                $publicRoutes = ["home"];
                $middleware = new RequireAuthenticationMiddleware(
                    $publicRoutes,
                );

                $request = new ServerRequest();
                $request = $request
                    ->withAttribute("route", "link.create")
                    ->withAttribute("authenticatedUserId", null);

                $handler = Mockery::mock(RequestHandlerInterface::class);
                $handler->shouldNotReceive("handle");

                expect(
                    fn() => $middleware->process($request, $handler),
                )->toThrow(
                    AuthenticationException::class,
                    "Authentication required",
                );
            },
        );

        test(
            "allows request when no route match exists (edge case)",
            function () {
                $publicRoutes = ["home"];
                $middleware = new RequireAuthenticationMiddleware(
                    $publicRoutes,
                );

                $request = new ServerRequest();
                // No route attribute - this shouldn't happen in practice but we handle it gracefully

                $handler = Mockery::mock(RequestHandlerInterface::class);
                $handler->shouldNotReceive("handle");

                expect(
                    fn() => $middleware->process($request, $handler),
                )->toThrow(
                    AuthenticationException::class,
                    "Authentication required",
                );
            },
        );

        test("handles multiple public routes correctly", function () {
            $publicRoutes = ["home", "auth.login", "auth.token-refresh"];
            $middleware = new RequireAuthenticationMiddleware($publicRoutes);

            // Test each public route
            foreach ($publicRoutes as $routeName) {
                $request = new ServerRequest();
                $request = $request->withAttribute("route", $routeName);

                $handler = Mockery::mock(RequestHandlerInterface::class);
                $expectedResponse = new JsonResponse(["ok" => true]);
                $handler
                    ->shouldReceive("handle")
                    ->once()
                    ->with($request)
                    ->andReturn($expectedResponse);

                $response = $middleware->process($request, $handler);
                expect($response)->toBe($expectedResponse);
            }
        });

        test(
            "public route names must match exactly (case sensitive)",
            function () {
                $publicRoutes = ["home"];
                $middleware = new RequireAuthenticationMiddleware(
                    $publicRoutes,
                );

                $request = new ServerRequest();
                $request = $request->withAttribute("route", "Home"); // Different case

                $handler = Mockery::mock(RequestHandlerInterface::class);
                $handler->shouldNotReceive("handle");

                expect(
                    fn() => $middleware->process($request, $handler),
                )->toThrow(
                    AuthenticationException::class,
                    "Authentication required",
                );
            },
        );
    });
});
