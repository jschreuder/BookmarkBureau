<?php

use jschreuder\BookmarkBureau\Controller\ErrorHandlerController;
use Laminas\Diactoros\ServerRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

describe("ErrorHandlerController", function () {
    describe("initialization", function () {
        test("creates controller with logger", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $controller = new ErrorHandlerController($logger);

            expect($controller)->toBeInstanceOf(ErrorHandlerController::class);
        });
    });

    describe("execute method", function () {
        test("returns 503 for PDOException", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(LogLevel::CRITICAL, "Storage engine error")
                ->once();

            $controller = new ErrorHandlerController($logger);
            $exception = new PDOException("Database connection failed");
            $request = new ServerRequest(
                uri: "http://example.com/api",
                method: "GET",
                serverParams: [],
            );
            $request = $request->withAttribute("error", $exception);

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(503);
        });

        test("returns 400 for bad input exception with code 400", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(LogLevel::WARNING, "Bad input")
                ->once();

            $controller = new ErrorHandlerController($logger);
            $exception = new Exception("Invalid input", 400);
            $request = new ServerRequest(
                uri: "http://example.com/api",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withAttribute("error", $exception);

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(400);
        });

        test(
            "returns 401 for unauthenticated exception with code 401",
            function () {
                $logger = Mockery::mock(LoggerInterface::class);
                $logger
                    ->shouldReceive("log")
                    ->with(LogLevel::WARNING, "Unauthenticated")
                    ->once();

                $controller = new ErrorHandlerController($logger);
                $exception = new Exception("Not authenticated", 401);
                $request = new ServerRequest(
                    uri: "http://example.com/api",
                    method: "GET",
                    serverParams: [],
                );
                $request = $request->withAttribute("error", $exception);

                $response = $controller->execute($request);

                expect($response->getStatusCode())->toBe(401);
            },
        );

        test(
            "returns 403 for unauthorized exception with code 403",
            function () {
                $logger = Mockery::mock(LoggerInterface::class);
                $logger
                    ->shouldReceive("log")
                    ->with(LogLevel::WARNING, "Unauthorized")
                    ->once();

                $controller = new ErrorHandlerController($logger);
                $exception = new Exception("Access denied", 403);
                $request = new ServerRequest(
                    uri: "http://example.com/api",
                    method: "DELETE",
                    serverParams: [],
                );
                $request = $request->withAttribute("error", $exception);

                $response = $controller->execute($request);

                expect($response->getStatusCode())->toBe(403);
            },
        );

        test("returns 500 for generic exception with no code", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(LogLevel::ERROR, "Server error")
                ->once();

            $controller = new ErrorHandlerController($logger);
            $exception = new Exception("Something went wrong");
            $request = new ServerRequest(
                uri: "http://example.com/api",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withAttribute("error", $exception);

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(500);
        });

        test("returns 500 for exception with invalid code", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(LogLevel::ERROR, "Server error")
                ->once();

            $controller = new ErrorHandlerController($logger);
            $exception = new Exception("Unknown error", 999);
            $request = new ServerRequest(
                uri: "http://example.com/api",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withAttribute("error", $exception);

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(500);
        });

        test("returns correct JSON response body with message", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(LogLevel::WARNING, "Unauthenticated")
                ->once();

            $controller = new ErrorHandlerController($logger);
            $exception = new Exception("Not authenticated", 401);
            $request = new ServerRequest(
                uri: "http://example.com/api",
                method: "GET",
                serverParams: [],
            );
            $request = $request->withAttribute("error", $exception);

            $response = $controller->execute($request);
            $body = json_decode($response->getBody()->getContents(), true);

            expect($body)->toHaveKey("message", "Unauthenticated");
        });

        test(
            "logs exception with correct severity level and message",
            function () {
                $logger = Mockery::mock(LoggerInterface::class);
                $logger
                    ->shouldReceive("log")
                    ->with(LogLevel::ERROR, "Server error")
                    ->once();

                $controller = new ErrorHandlerController($logger);
                $exception = new Exception("Not found", 404);
                $request = new ServerRequest(
                    uri: "http://example.com/api",
                    method: "GET",
                    serverParams: [],
                );
                $request = $request->withAttribute("error", $exception);

                $response = $controller->execute($request);

                expect($response->getStatusCode())->toBe(404);
            },
        );

        test("response is JsonResponse instance", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger->shouldReceive("log")->once();

            $controller = new ErrorHandlerController($logger);
            $exception = new Exception("Server error", 500);
            $request = new ServerRequest(
                uri: "http://example.com/api",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withAttribute("error", $exception);

            $response = $controller->execute($request);

            expect($response)->toBeInstanceOf(
                Laminas\Diactoros\Response\JsonResponse::class,
            );
        });
    });

    describe("interface implementation", function () {
        test("implements ControllerInterface", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $controller = new ErrorHandlerController($logger);

            expect($controller)->toBeInstanceOf(
                jschreuder\Middle\Controller\ControllerInterface::class,
            );
        });
    });
});
