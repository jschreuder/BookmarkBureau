<?php

use jschreuder\BookmarkBureau\Controller\LoginController;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\JwtToken;
use jschreuder\BookmarkBureau\Entity\Value\TokenClaims;
use jschreuder\BookmarkBureau\Entity\Value\TokenResponse;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\InputSpec\LoginInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TokenOutputSpec;
use jschreuder\BookmarkBureau\Response\JsonResponseTransformer;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\TotpVerifierInterface;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Laminas\Diactoros\ServerRequest;

describe("LoginController", function () {
    describe("filterRequest", function () {
        test("filters email to lowercase and trims whitespace", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "  Test@EXAMPLE.COM  ",
                "password" => "password123",
                "remember_me" => false,
            ]);

            $filtered = $controller->filterRequest($request);

            expect($filtered->getParsedBody()["email"])->toBe(
                "test@example.com",
            );
        });

        test("converts rememberMe to boolean", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "test@example.com",
                "password" => "password123",
                "remember_me" => 1,
            ]);

            $filtered = $controller->filterRequest($request);

            expect($filtered->getParsedBody()["remember_me"])->toBeTrue();
        });

        test("defaults rememberMe to false when missing", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "test@example.com",
                "password" => "password123",
            ]);

            $filtered = $controller->filterRequest($request);

            expect($filtered->getParsedBody()["remember_me"])->toBeFalse();
        });
    });

    describe("validateRequest", function () {
        test("validates valid login credentials", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "test@example.com",
                "password" => "password123",
                "remember_me" => false,
                "totp_code" => "",
            ]);

            $controller->validateRequest($request);
            expect(true)->toBeTrue();
        });

        test("throws on invalid email format", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "not-an-email",
                "password" => "password123",
                "remember_me" => false,
            ]);

            expect(fn() => $controller->validateRequest($request))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws on empty password", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "test@example.com",
                "password" => "",
                "remember_me" => false,
            ]);

            expect(fn() => $controller->validateRequest($request))->toThrow(
                ValidationFailedException::class,
            );
        });
    });

    describe("execute", function () {
        test(
            "returns SESSION_TOKEN by default when rememberMe is false",
            function () {
                $inputSpec = new LoginInputSpec();
                $userService = Mockery::mock(UserServiceInterface::class);
                $jwtService = Mockery::mock(JwtServiceInterface::class);
                $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
                $responseTransformer = new JsonResponseTransformer();
                $tokenOutputSpec = new TokenOutputSpec();
                $controller = new LoginController(
                    $inputSpec,
                    $userService,
                    $jwtService,
                    $totpVerifier,
                    $tokenOutputSpec,
                    $responseTransformer,
                );

                $user = TestEntityFactory::createUser();
                $userService->shouldReceive("getUserByEmail")->andReturn($user);
                $userService
                    ->shouldReceive("verifyPassword")
                    ->with($user, "password123")
                    ->andReturn(true);

                $now = new DateTimeImmutable();
                $expiresAt = $now->modify("+24 hours");
                $jwtToken = new JwtToken("test.jwt.token");
                $claims = new TokenClaims(
                    $user->userId,
                    TokenType::SESSION_TOKEN,
                    $now,
                    $expiresAt,
                );

                $jwtService
                    ->shouldReceive("generate")
                    ->with($user, TokenType::SESSION_TOKEN)
                    ->andReturn($jwtToken);
                $jwtService
                    ->shouldReceive("verify")
                    ->with($jwtToken)
                    ->andReturn($claims);

                $request = new ServerRequest(
                    uri: "http://example.com/api/auth/login",
                    method: "POST",
                    serverParams: [],
                );
                $request = $request->withParsedBody([
                    "email" => "test@example.com",
                    "password" => "password123",
                    "remember_me" => false,
                ]);

                $response = $controller->execute($request);

                expect($response->getStatusCode())->toBe(200);
                $body = json_decode($response->getBody()->getContents(), true);
                expect($body["success"])->toBeTrue();
                expect($body["data"]["type"])->toBe("session");
            },
        );

        test("returns REMEMBER_ME_TOKEN when rememberMe is true", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $user = TestEntityFactory::createUser();
            $userService->shouldReceive("getUserByEmail")->andReturn($user);
            $userService
                ->shouldReceive("verifyPassword")
                ->with($user, "password123")
                ->andReturn(true);

            $now = new DateTimeImmutable();
            $expiresAt = $now->modify("+14 days");
            $jwtToken = new JwtToken("test.jwt.token");
            $claims = new TokenClaims(
                $user->userId,
                TokenType::REMEMBER_ME_TOKEN,
                $now,
                $expiresAt,
            );

            $jwtService
                ->shouldReceive("generate")
                ->with($user, TokenType::REMEMBER_ME_TOKEN)
                ->andReturn($jwtToken);
            $jwtService
                ->shouldReceive("verify")
                ->with($jwtToken)
                ->andReturn($claims);

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "test@example.com",
                "password" => "password123",
                "remember_me" => true,
            ]);

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(200);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["success"])->toBeTrue();
            expect($body["data"]["type"])->toBe("remember_me");
        });

        test(
            "throws InvalidArgumentException on invalid credentials",
            function () {
                $inputSpec = new LoginInputSpec();
                $userService = Mockery::mock(UserServiceInterface::class);
                $jwtService = Mockery::mock(JwtServiceInterface::class);
                $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
                $responseTransformer = new JsonResponseTransformer();
                $tokenOutputSpec = new TokenOutputSpec();
                $controller = new LoginController(
                    $inputSpec,
                    $userService,
                    $jwtService,
                    $totpVerifier,
                    $tokenOutputSpec,
                    $responseTransformer,
                );

                $user = TestEntityFactory::createUser();
                $userService->shouldReceive("getUserByEmail")->andReturn($user);
                $userService
                    ->shouldReceive("verifyPassword")
                    ->with($user, "wrongpassword")
                    ->andReturn(false);

                $request = new ServerRequest(
                    uri: "http://example.com/api/auth/login",
                    method: "POST",
                    serverParams: [],
                );
                $request = $request->withParsedBody([
                    "email" => "test@example.com",
                    "password" => "wrongpassword",
                    "remember_me" => false,
                ]);

                expect(fn() => $controller->execute($request))->toThrow(
                    \InvalidArgumentException::class,
                );
            },
        );

        test(
            "throws InvalidArgumentException when user not found",
            function () {
                $inputSpec = new LoginInputSpec();
                $userService = Mockery::mock(UserServiceInterface::class);
                $jwtService = Mockery::mock(JwtServiceInterface::class);
                $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
                $responseTransformer = new JsonResponseTransformer();
                $tokenOutputSpec = new TokenOutputSpec();
                $controller = new LoginController(
                    $inputSpec,
                    $userService,
                    $jwtService,
                    $totpVerifier,
                    $tokenOutputSpec,
                    $responseTransformer,
                );

                $userService
                    ->shouldReceive("getUserByEmail")
                    ->andThrow(new UserNotFoundException("User not found"));

                $request = new ServerRequest(
                    uri: "http://example.com/api/auth/login",
                    method: "POST",
                    serverParams: [],
                );
                $request = $request->withParsedBody([
                    "email" => "nonexistent@example.com",
                    "password" => "password123",
                    "remember_me" => false,
                ]);

                expect(fn() => $controller->execute($request))->toThrow(
                    \InvalidArgumentException::class,
                );
            },
        );

        test("requires TOTP code when user has TOTP enabled", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $totpSecret = new TotpSecret(
                "JBSWY3DPEBLW64TMMQQQQQQQQQQQQQQQQQQQQQQQQQQ",
            );
            $user = TestEntityFactory::createUser(totpSecret: $totpSecret);
            $userService->shouldReceive("getUserByEmail")->andReturn($user);
            $userService
                ->shouldReceive("verifyPassword")
                ->with($user, "password123")
                ->andReturn(true);

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "test@example.com",
                "password" => "password123",
                "remember_me" => false,
                "totp_code" => "",
            ]);

            expect(fn() => $controller->execute($request))->toThrow(
                \InvalidArgumentException::class,
            );
        });

        test("verifies TOTP code and allows login when valid", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $totpSecret = new TotpSecret(
                "JBSWY3DPEBLW64TMMQQQQQQQQQQQQQQQQQQQQQQQQQQ",
            );
            $user = TestEntityFactory::createUser(totpSecret: $totpSecret);
            $userService->shouldReceive("getUserByEmail")->andReturn($user);
            $userService
                ->shouldReceive("verifyPassword")
                ->with($user, "password123")
                ->andReturn(true);
            $totpVerifier
                ->shouldReceive("verify")
                ->with("123456", $totpSecret)
                ->andReturn(true);

            $now = new DateTimeImmutable();
            $expiresAt = $now->modify("+24 hours");
            $jwtToken = new JwtToken("test.jwt.token");
            $claims = new TokenClaims(
                $user->userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );

            $jwtService
                ->shouldReceive("generate")
                ->with($user, TokenType::SESSION_TOKEN)
                ->andReturn($jwtToken);
            $jwtService
                ->shouldReceive("verify")
                ->with($jwtToken)
                ->andReturn($claims);

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "test@example.com",
                "password" => "password123",
                "remember_me" => false,
                "totp_code" => "123456",
            ]);

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(200);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["success"])->toBeTrue();
            expect($body["data"]["type"])->toBe("session");
        });

        test("rejects login with invalid TOTP code", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $totpSecret = new TotpSecret(
                "JBSWY3DPEBLW64TMMQQQQQQQQQQQQQQQQQQQQQQQQQQ",
            );
            $user = TestEntityFactory::createUser(totpSecret: $totpSecret);
            $userService->shouldReceive("getUserByEmail")->andReturn($user);
            $userService
                ->shouldReceive("verifyPassword")
                ->with($user, "password123")
                ->andReturn(true);
            $totpVerifier
                ->shouldReceive("verify")
                ->with("000000", $totpSecret)
                ->andReturn(false);

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "test@example.com",
                "password" => "password123",
                "remember_me" => false,
                "totp_code" => "000000",
            ]);

            expect(fn() => $controller->execute($request))->toThrow(
                \InvalidArgumentException::class,
            );
        });

        test(
            "allows login without TOTP code when user has no TOTP enabled",
            function () {
                $inputSpec = new LoginInputSpec();
                $userService = Mockery::mock(UserServiceInterface::class);
                $jwtService = Mockery::mock(JwtServiceInterface::class);
                $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
                $responseTransformer = new JsonResponseTransformer();
                $tokenOutputSpec = new TokenOutputSpec();
                $controller = new LoginController(
                    $inputSpec,
                    $userService,
                    $jwtService,
                    $totpVerifier,
                    $tokenOutputSpec,
                    $responseTransformer,
                );

                $user = TestEntityFactory::createUser();
                $userService->shouldReceive("getUserByEmail")->andReturn($user);
                $userService
                    ->shouldReceive("verifyPassword")
                    ->with($user, "password123")
                    ->andReturn(true);

                $now = new DateTimeImmutable();
                $expiresAt = $now->modify("+24 hours");
                $jwtToken = new JwtToken("test.jwt.token");
                $claims = new TokenClaims(
                    $user->userId,
                    TokenType::SESSION_TOKEN,
                    $now,
                    $expiresAt,
                );

                $jwtService
                    ->shouldReceive("generate")
                    ->with($user, TokenType::SESSION_TOKEN)
                    ->andReturn($jwtToken);
                $jwtService
                    ->shouldReceive("verify")
                    ->with($jwtToken)
                    ->andReturn($claims);

                $request = new ServerRequest(
                    uri: "http://example.com/api/auth/login",
                    method: "POST",
                    serverParams: [],
                );
                $request = $request->withParsedBody([
                    "email" => "test@example.com",
                    "password" => "password123",
                    "remember_me" => false,
                ]);

                $response = $controller->execute($request);

                expect($response->getStatusCode())->toBe(200);
                $body = json_decode($response->getBody()->getContents(), true);
                expect($body["success"])->toBeTrue();
            },
        );

        test("returns token response with correct format", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $user = TestEntityFactory::createUser();
            $userService->shouldReceive("getUserByEmail")->andReturn($user);
            $userService
                ->shouldReceive("verifyPassword")
                ->with($user, "password123")
                ->andReturn(true);

            $now = new DateTimeImmutable();
            $expiresAt = $now->modify("+24 hours");
            $jwtToken = new JwtToken("test.jwt.token");
            $claims = new TokenClaims(
                $user->userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );

            $jwtService
                ->shouldReceive("generate")
                ->with($user, TokenType::SESSION_TOKEN)
                ->andReturn($jwtToken);
            $jwtService
                ->shouldReceive("verify")
                ->with($jwtToken)
                ->andReturn($claims);

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "test@example.com",
                "password" => "password123",
                "remember_me" => false,
            ]);

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
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            expect($controller)->toBeInstanceOf(
                jschreuder\Middle\Controller\ControllerInterface::class,
            );
        });

        test("implements RequestFilterInterface", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            expect($controller)->toBeInstanceOf(
                jschreuder\Middle\Controller\RequestFilterInterface::class,
            );
        });

        test("implements RequestValidatorInterface", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            expect($controller)->toBeInstanceOf(
                jschreuder\Middle\Controller\RequestValidatorInterface::class,
            );
        });
    });

    describe("full request lifecycle", function () {
        test("processes login request from start to finish", function () {
            $inputSpec = new LoginInputSpec();
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $totpVerifier = Mockery::mock(TotpVerifierInterface::class);
            $responseTransformer = new JsonResponseTransformer();
            $tokenOutputSpec = new TokenOutputSpec();
            $controller = new LoginController(
                $inputSpec,
                $userService,
                $jwtService,
                $totpVerifier,
                $tokenOutputSpec,
                $responseTransformer,
            );

            $user = TestEntityFactory::createUser();
            $userService->shouldReceive("getUserByEmail")->andReturn($user);
            $userService
                ->shouldReceive("verifyPassword")
                ->with($user, "password123")
                ->andReturn(true);

            $now = new DateTimeImmutable();
            $expiresAt = $now->modify("+24 hours");
            $jwtToken = new JwtToken("test.jwt.token");
            $claims = new TokenClaims(
                $user->userId,
                TokenType::SESSION_TOKEN,
                $now,
                $expiresAt,
            );

            $jwtService
                ->shouldReceive("generate")
                ->with($user, TokenType::SESSION_TOKEN)
                ->andReturn($jwtToken);
            $jwtService
                ->shouldReceive("verify")
                ->with($jwtToken)
                ->andReturn($claims);

            $request = new ServerRequest(
                uri: "http://example.com/api/auth/login",
                method: "POST",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "email" => "  Test@Example.Com  ",
                "password" => "password123",
            ]);

            $filtered = $controller->filterRequest($request);
            expect($filtered->getParsedBody()["email"])->toBe(
                "test@example.com",
            );

            $controller->validateRequest($filtered);

            $response = $controller->execute($filtered);
            expect($response->getStatusCode())->toBe(200);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["success"])->toBeTrue();
            expect($body["data"]["type"])->toBe("session");
        });
    });
});
