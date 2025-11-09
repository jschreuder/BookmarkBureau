<?php

use jschreuder\BookmarkBureau\Entity\Value\JwtToken;
use jschreuder\BookmarkBureau\Entity\Value\TokenClaims;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Exception\InvalidTokenException;
use jschreuder\BookmarkBureau\Middleware\JwtAuthenticationMiddleware;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\Middle\Exception\AuthenticationException;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\UuidInterface;

describe("JwtAuthenticationMiddleware", function () {
    describe("with valid token", function () {
        test(
            "extracts token and attaches authenticated user to request",
            function () {
                $user = TestEntityFactory::createUser();
                $now = new DateTimeImmutable(
                    "2024-01-01 12:00:00",
                    new DateTimeZone("UTC"),
                );
                $expiresAt = $now->modify("+86400 seconds");
                $claims = new TokenClaims(
                    $user->userId,
                    TokenType::SESSION_TOKEN,
                    $now,
                    $expiresAt,
                );

                $jwtService = Mockery::mock(JwtServiceInterface::class);
                $jwtService
                    ->shouldReceive("verify")
                    ->withAnyArgs()
                    ->andReturn($claims);

                $middleware = new JwtAuthenticationMiddleware($jwtService);

                $request = new ServerRequest();
                $request = $request->withHeader(
                    "Authorization",
                    "Bearer valid.jwt.token",
                );

                $nextHandler = Mockery::mock(RequestHandlerInterface::class);
                $nextHandler
                    ->shouldReceive("handle")
                    ->andReturnUsing(function (ServerRequestInterface $req) {
                        // Verify the request has the authenticated user attached
                        expect(
                            $req->getAttribute("authenticatedUserId"),
                        )->toBeInstanceOf(UuidInterface::class);
                        expect(
                            $req->getAttribute("tokenClaims"),
                        )->toBeInstanceOf(TokenClaims::class);
                        return new TextResponse("OK");
                    });

                $response = $middleware->process($request, $nextHandler);

                expect($response)->toBeInstanceOf(ResponseInterface::class);
            },
        );

        test("attaches tokenClaims to request", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $expiresAt = $now->modify("+86400 seconds");
            $claims = new TokenClaims(
                $user->userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );

            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $jwtService
                ->shouldReceive("verify")
                ->withAnyArgs()
                ->andReturn($claims);

            $middleware = new JwtAuthenticationMiddleware($jwtService);

            $request = new ServerRequest();
            $request = $request->withHeader(
                "Authorization",
                "Bearer valid.jwt.token",
            );

            $nextHandler = Mockery::mock(RequestHandlerInterface::class);
            $nextHandler
                ->shouldReceive("handle")
                ->andReturnUsing(function (ServerRequestInterface $req) {
                    expect($req->getAttribute("tokenClaims")->tokenType)->toBe(
                        TokenType::SESSION_TOKEN,
                    );
                    expect(
                        $req
                            ->getAttribute("tokenClaims")
                            ->issuedAt->getTimestamp(),
                    )->toBe(
                        new DateTimeImmutable(
                            "2024-01-01 12:00:00",
                            new DateTimeZone("UTC"),
                        )->getTimestamp(),
                    );
                    return new TextResponse("OK");
                });

            $middleware->process($request, $nextHandler);
        });
    });

    describe("with invalid token", function () {
        test(
            "throws AuthenticationException when token is invalid",
            function () {
                $token = new JwtToken("invalid.jwt.token");

                $jwtService = Mockery::mock(JwtServiceInterface::class);
                $jwtService
                    ->shouldReceive("verify")
                    ->with($token)
                    ->andThrow(new InvalidTokenException("Invalid signature"));

                $middleware = new JwtAuthenticationMiddleware($jwtService);

                $request = new ServerRequest();
                $request = $request->withHeader(
                    "Authorization",
                    "Bearer invalid.jwt.token",
                );

                $nextHandler = Mockery::mock(RequestHandlerInterface::class);

                expect(
                    fn() => $middleware->process($request, $nextHandler),
                )->toThrow(AuthenticationException::class);
            },
        );

        test(
            "throws AuthenticationException when token is expired",
            function () {
                $token = new JwtToken("expired.jwt.token");

                $jwtService = Mockery::mock(JwtServiceInterface::class);
                $jwtService
                    ->shouldReceive("verify")
                    ->with($token)
                    ->andThrow(new InvalidTokenException("Token has expired"));

                $middleware = new JwtAuthenticationMiddleware($jwtService);

                $request = new ServerRequest();
                $request = $request->withHeader(
                    "Authorization",
                    "Bearer expired.jwt.token",
                );

                $nextHandler = Mockery::mock(RequestHandlerInterface::class);

                expect(
                    fn() => $middleware->process($request, $nextHandler),
                )->toThrow(AuthenticationException::class);
            },
        );
    });

    describe("without token", function () {
        test("allows request through without authentication", function () {
            $jwtService = Mockery::mock(JwtServiceInterface::class);

            $middleware = new JwtAuthenticationMiddleware($jwtService);

            $request = new ServerRequest(); // No Authorization header

            $nextHandler = Mockery::mock(RequestHandlerInterface::class);
            $nextHandler
                ->shouldReceive("handle")
                ->andReturnUsing(function (ServerRequestInterface $req) {
                    // Request should not have authenticatedUser attribute
                    expect($req->getAttribute("authenticatedUser"))->toBeNull();
                    expect($req->getAttribute("tokenClaims"))->toBeNull();
                    return new TextResponse("OK");
                });

            $response = $middleware->process($request, $nextHandler);

            expect($response)->toBeInstanceOf(ResponseInterface::class);
        });

        test("allows empty Authorization header through", function () {
            $jwtService = Mockery::mock(JwtServiceInterface::class);

            $middleware = new JwtAuthenticationMiddleware($jwtService);

            $request = new ServerRequest();
            $request = $request->withHeader("Authorization", "");

            $nextHandler = Mockery::mock(RequestHandlerInterface::class);
            $nextHandler
                ->shouldReceive("handle")
                ->andReturnUsing(function (ServerRequestInterface $req) {
                    expect($req->getAttribute("authenticatedUser"))->toBeNull();
                    return new TextResponse("OK");
                });

            $response = $middleware->process($request, $nextHandler);

            expect($response)->toBeInstanceOf(ResponseInterface::class);
        });
    });

    describe("Authorization header parsing", function () {
        test(
            "throws AuthenticationException for malformed Authorization header",
            function () {
                $jwtService = Mockery::mock(JwtServiceInterface::class);

                $middleware = new JwtAuthenticationMiddleware($jwtService);

                $request = new ServerRequest();
                $request = $request->withHeader(
                    "Authorization",
                    "InvalidFormat",
                );

                $nextHandler = Mockery::mock(RequestHandlerInterface::class);

                expect(
                    fn() => $middleware->process($request, $nextHandler),
                )->toThrow(AuthenticationException::class);
            },
        );

        test(
            "throws AuthenticationException for missing token in header",
            function () {
                $jwtService = Mockery::mock(JwtServiceInterface::class);

                $middleware = new JwtAuthenticationMiddleware($jwtService);

                $request = new ServerRequest();
                $request = $request->withHeader("Authorization", "Bearer");

                $nextHandler = Mockery::mock(RequestHandlerInterface::class);

                expect(
                    fn() => $middleware->process($request, $nextHandler),
                )->toThrow(AuthenticationException::class);
            },
        );

        test("accepts case-insensitive Bearer scheme", function () {
            $user = TestEntityFactory::createUser();
            $now = new DateTimeImmutable(
                "2024-01-01 12:00:00",
                new DateTimeZone("UTC"),
            );
            $expiresAt = $now->modify("+86400 seconds");
            $claims = new TokenClaims(
                $user->userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );

            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $jwtService
                ->shouldReceive("verify")
                ->withAnyArgs()
                ->andReturn($claims);

            $middleware = new JwtAuthenticationMiddleware($jwtService);

            $request = new ServerRequest();
            $request = $request->withHeader(
                "Authorization",
                "bearer valid.jwt.token",
            );

            $nextHandler = Mockery::mock(RequestHandlerInterface::class);
            $nextHandler
                ->shouldReceive("handle")
                ->andReturn(new TextResponse("OK"));

            $response = $middleware->process($request, $nextHandler);

            expect($response)->toBeInstanceOf(ResponseInterface::class);
        });
    });
});
