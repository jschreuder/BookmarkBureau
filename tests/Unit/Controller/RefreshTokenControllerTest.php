<?php

use jschreuder\BookmarkBureau\Controller\RefreshTokenController;
use jschreuder\BookmarkBureau\Entity\Value\JwtToken;
use jschreuder\BookmarkBureau\Entity\Value\TokenClaims;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\OutputSpec\TokenOutputSpec;
use jschreuder\BookmarkBureau\Response\JsonResponseTransformer;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use jschreuder\Middle\Exception\AuthenticationException;
use Laminas\Diactoros\ServerRequest;

describe("RefreshTokenController", function () {
    describe("execute", function () {
        test("refreshes session token and returns new token", function () {
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $userService = Mockery::mock(UserServiceInterface::class);
            $tokenOutputSpec = new TokenOutputSpec();
            $responseTransformer = new JsonResponseTransformer();
            $controller = new RefreshTokenController(
                $jwtService,
                $userService,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $user = TestEntityFactory::createUser();
            $userService
                ->shouldReceive("getUser")
                ->with($user->userId)
                ->andReturn($user);

            $now = new DateTimeImmutable();
            $expiresAt = $now->modify("+24 hours");
            $oldClaims = new TokenClaims(
                $user->userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );

            $newToken = new JwtToken("new.jwt.token");
            $newExpiresAt = $now->modify("+24 hours");
            $newClaims = new TokenClaims(
                $user->userId,
                TokenType::SESSION_TOKEN,
                $now,
                $newExpiresAt,
            );

            $jwtService
                ->shouldReceive("refresh")
                ->with($oldClaims)
                ->andReturn($newToken);
            $jwtService
                ->shouldReceive("verify")
                ->with($newToken)
                ->andReturn($newClaims);

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/token/refresh",
                method: "POST",
                serverParams: [],
            );
            $request = $request
                ->withAttribute("authenticatedUserId", $user->userId)
                ->withAttribute("tokenClaims", $oldClaims);

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(200);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["success"])->toBeTrue();
            expect($body["data"]["type"])->toBe("session");
            expect($body["data"]["token"])->toBe("new.jwt.token");
        });

        test("refreshes remember-me token and returns new token", function () {
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $userService = Mockery::mock(UserServiceInterface::class);
            $tokenOutputSpec = new TokenOutputSpec();
            $responseTransformer = new JsonResponseTransformer();
            $controller = new RefreshTokenController(
                $jwtService,
                $userService,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $user = TestEntityFactory::createUser();
            $userService
                ->shouldReceive("getUser")
                ->with($user->userId)
                ->andReturn($user);
            $now = new DateTimeImmutable();
            $expiresAt = $now->modify("+14 days");
            $oldClaims = new TokenClaims(
                $user->userId,
                TokenType::REMEMBER_ME_TOKEN,
                $now,
                $expiresAt,
            );

            $newToken = new JwtToken("new.jwt.token");
            $newExpiresAt = $now->modify("+14 days");
            $newClaims = new TokenClaims(
                $user->userId,
                TokenType::REMEMBER_ME_TOKEN,
                $now,
                $newExpiresAt,
            );

            $jwtService
                ->shouldReceive("refresh")
                ->with($oldClaims)
                ->andReturn($newToken);
            $jwtService
                ->shouldReceive("verify")
                ->with($newToken)
                ->andReturn($newClaims);

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/token/refresh",
                method: "POST",
                serverParams: [],
            );
            $request = $request
                ->withAttribute("authenticatedUserId", $user->userId)
                ->withAttribute("tokenClaims", $oldClaims);

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(200);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["success"])->toBeTrue();
            expect($body["data"]["type"])->toBe("remember_me");
        });

        test(
            "throws AuthenticationException when authenticatedUser is null",
            function () {
                $jwtService = Mockery::mock(JwtServiceInterface::class);
                $userService = Mockery::mock(UserServiceInterface::class);
                $tokenOutputSpec = new TokenOutputSpec();
                $responseTransformer = new JsonResponseTransformer();
                $controller = new RefreshTokenController(
                    $jwtService,
                    $userService,
                    $tokenOutputSpec,
                    $responseTransformer,
                );

                $now = new DateTimeImmutable();
                $expiresAt = $now->modify("+24 hours");
                $claims = new TokenClaims(
                    TestEntityFactory::createUser()->userId,
                    TokenType::SESSION_TOKEN,
                    $now,
                    $expiresAt,
                );

                $request = new ServerRequest(
                    uri: "http://example.com/api/auth/token/refresh",
                    method: "POST",
                    serverParams: [],
                );
                $request = $request->withAttribute("tokenClaims", $claims);

                expect(fn() => $controller->execute($request))->toThrow(
                    AuthenticationException::class,
                );
            },
        );

        test(
            "throws AuthenticationException when tokenClaims is null",
            function () {
                $jwtService = Mockery::mock(JwtServiceInterface::class);
                $userService = Mockery::mock(UserServiceInterface::class);
                $tokenOutputSpec = new TokenOutputSpec();
                $responseTransformer = new JsonResponseTransformer();
                $controller = new RefreshTokenController(
                    $jwtService,
                    $userService,
                    $tokenOutputSpec,
                    $responseTransformer,
                );

                $user = TestEntityFactory::createUser();
                $request = new ServerRequest(
                    uri: "http://example.com/api/auth/token/refresh",
                    method: "POST",
                    serverParams: [],
                );
                $request = $request->withAttribute(
                    "authenticatedUserId",
                    $user->userId,
                );

                expect(fn() => $controller->execute($request))->toThrow(
                    AuthenticationException::class,
                );
            },
        );

        test("returns token response with correct format", function () {
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $userService = Mockery::mock(UserServiceInterface::class);
            $tokenOutputSpec = new TokenOutputSpec();
            $responseTransformer = new JsonResponseTransformer();
            $controller = new RefreshTokenController(
                $jwtService,
                $userService,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $user = TestEntityFactory::createUser();
            $userService
                ->shouldReceive("getUser")
                ->with($user->userId)
                ->andReturn($user);

            $now = new DateTimeImmutable();
            $expiresAt = $now->modify("+24 hours");
            $oldClaims = new TokenClaims(
                $user->userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );

            $newToken = new JwtToken("test.jwt.token");
            $newExpiresAt = $now->modify("+24 hours");
            $newClaims = new TokenClaims(
                $user->userId,
                TokenType::SESSION_TOKEN,
                $now,
                $newExpiresAt,
            );

            $jwtService
                ->shouldReceive("refresh")
                ->with($oldClaims)
                ->andReturn($newToken);
            $jwtService
                ->shouldReceive("verify")
                ->with($newToken)
                ->andReturn($newClaims);

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/token/refresh",
                method: "POST",
                serverParams: [],
            );
            $request = $request
                ->withAttribute("authenticatedUserId", $user->userId)
                ->withAttribute("tokenClaims", $oldClaims);

            $response = $controller->execute($request);
            $body = json_decode($response->getBody()->getContents(), true);

            expect($body["data"])->toHaveKey("token");
            expect($body["data"])->toHaveKey("type");
            expect($body["data"])->toHaveKey("expires_at");
            expect($body["data"]["token"])->toBe("test.jwt.token");
        });
    });

    describe("interface implementation", function () {
        test("implements ControllerInterface", function () {
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $userService = Mockery::mock(UserServiceInterface::class);
            $tokenOutputSpec = new TokenOutputSpec();
            $responseTransformer = new JsonResponseTransformer();
            $controller = new RefreshTokenController(
                $jwtService,
                $userService,
                $tokenOutputSpec,
                $responseTransformer,
            );

            expect($controller)->toBeInstanceOf(
                jschreuder\Middle\Controller\ControllerInterface::class,
            );
        });
    });
});
