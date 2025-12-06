<?php

use jschreuder\BookmarkBureau\HttpMiddleware\IpWhitelistMiddleware;
use jschreuder\Middle\Exception\AuthorizationException;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

describe("IpWhitelistMiddleware", function () {
    describe("process method", function () {
        test("should allow all IPs when whitelist is empty", function () {
            $middleware = new IpWhitelistMiddleware([], []);
            $request = new ServerRequest(
                ["REMOTE_ADDR" => "203.0.113.5"],
                [],
                "/test",
                "GET",
            );

            $handler = new class implements RequestHandlerInterface {
                public function handle(
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    return new JsonResponse(["success" => true]);
                }
            };

            $response = $middleware->process($request, $handler);

            expect($response->getStatusCode())->toBe(200);
        });

        test("should allow IP in whitelist", function () {
            $middleware = new IpWhitelistMiddleware(
                ["192.168.1.0/24", "10.0.0.5"],
                [],
            );
            $request = new ServerRequest(
                ["REMOTE_ADDR" => "192.168.1.100"],
                [],
                "/test",
                "GET",
            );
            $request = $request->withAttribute("route", "dashboard.create");

            $handler = new class implements RequestHandlerInterface {
                public function handle(
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    return new JsonResponse(["success" => true]);
                }
            };

            $response = $middleware->process($request, $handler);

            expect($response->getStatusCode())->toBe(200);
        });

        test("should block IP not in whitelist", function () {
            $middleware = new IpWhitelistMiddleware(["192.168.1.0/24"], []);
            $request = new ServerRequest(
                ["REMOTE_ADDR" => "203.0.113.5"],
                [],
                "/test",
                "POST",
            );
            $request = $request->withAttribute("route", "dashboard.create");

            $handler = new class implements RequestHandlerInterface {
                public function handle(
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    return new JsonResponse(["success" => true]);
                }
            };

            expect(fn() => $middleware->process($request, $handler))->toThrow(
                AuthorizationException::class,
                "Access denied: IP address 203.0.113.5 not in whitelist",
            );
        });

        test("should exempt public routes from IP whitelist", function () {
            $middleware = new IpWhitelistMiddleware(
                ["192.168.1.0/24"],
                ["home", "auth.login"],
            );
            $request = new ServerRequest(
                ["REMOTE_ADDR" => "203.0.113.5"],
                [],
                "/auth/login",
                "POST",
            );
            $request = $request->withAttribute("route", "auth.login");

            $handler = new class implements RequestHandlerInterface {
                public function handle(
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    return new JsonResponse(["success" => true]);
                }
            };

            $response = $middleware->process($request, $handler);

            expect($response->getStatusCode())->toBe(200);
        });

        test("should trust X-Forwarded-For when configured", function () {
            $middleware = new IpWhitelistMiddleware(
                ["192.168.1.0/24"],
                [],
                trustProxyHeaders: true,
            );
            $request = new ServerRequest(
                [
                    "REMOTE_ADDR" => "10.0.0.1",
                    "HTTP_X_FORWARDED_FOR" => "192.168.1.100",
                ],
                [],
                "/test",
                "GET",
            );
            $request = $request->withAttribute("route", "dashboard.create");

            $handler = new class implements RequestHandlerInterface {
                public function handle(
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    return new JsonResponse(["success" => true]);
                }
            };

            $response = $middleware->process($request, $handler);

            expect($response->getStatusCode())->toBe(200);
        });

        test("should not trust X-Forwarded-For when disabled", function () {
            $middleware = new IpWhitelistMiddleware(
                ["192.168.1.0/24"],
                [],
                trustProxyHeaders: false,
            );
            $request = new ServerRequest(
                [
                    "REMOTE_ADDR" => "203.0.113.5",
                    "HTTP_X_FORWARDED_FOR" => "192.168.1.100",
                ],
                [],
                "/test",
                "GET",
            );
            $request = $request->withAttribute("route", "dashboard.create");

            $handler = new class implements RequestHandlerInterface {
                public function handle(
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    return new JsonResponse(["success" => true]);
                }
            };

            expect(fn() => $middleware->process($request, $handler))->toThrow(
                AuthorizationException::class,
            );
        });

        test("should handle multiple CIDR ranges", function () {
            $middleware = new IpWhitelistMiddleware(
                ["192.168.1.0/24", "10.0.0.0/8", "172.16.0.5"],
                [],
            );

            $handler = new class implements RequestHandlerInterface {
                public function handle(
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    return new JsonResponse(["success" => true]);
                }
            };

            // Should allow 192.168.1.x
            $request1 = new ServerRequest(
                ["REMOTE_ADDR" => "192.168.1.50"],
                [],
                "/test",
                "GET",
            );
            $request1 = $request1->withAttribute("route", "link.create");
            $response1 = $middleware->process($request1, $handler);
            expect($response1->getStatusCode())->toBe(200);

            // Should allow 10.x.x.x
            $request2 = new ServerRequest(
                ["REMOTE_ADDR" => "10.5.5.5"],
                [],
                "/test",
                "GET",
            );
            $request2 = $request2->withAttribute("route", "link.create");
            $response2 = $middleware->process($request2, $handler);
            expect($response2->getStatusCode())->toBe(200);

            // Should allow exact IP
            $request3 = new ServerRequest(
                ["REMOTE_ADDR" => "172.16.0.5"],
                [],
                "/test",
                "GET",
            );
            $request3 = $request3->withAttribute("route", "link.create");
            $response3 = $middleware->process($request3, $handler);
            expect($response3->getStatusCode())->toBe(200);

            // Should block others
            $request4 = new ServerRequest(
                ["REMOTE_ADDR" => "203.0.113.5"],
                [],
                "/test",
                "GET",
            );
            $request4 = $request4->withAttribute("route", "link.create");
            expect(fn() => $middleware->process($request4, $handler))->toThrow(
                AuthorizationException::class,
            );
        });

        test("should handle null route attribute", function () {
            $middleware = new IpWhitelistMiddleware(
                ["192.168.1.0/24"],
                ["public"],
            );
            $request = new ServerRequest(
                ["REMOTE_ADDR" => "192.168.1.100"],
                [],
                "/test",
                "GET",
            );
            // No route attribute set

            $handler = new class implements RequestHandlerInterface {
                public function handle(
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    return new JsonResponse(["success" => true]);
                }
            };

            $response = $middleware->process($request, $handler);

            expect($response->getStatusCode())->toBe(200);
        });
    });
});
