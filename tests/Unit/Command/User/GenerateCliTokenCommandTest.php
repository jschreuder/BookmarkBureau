<?php

use jschreuder\BookmarkBureau\Command\User\GenerateCliTokenCommand;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\JwtToken;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

describe("GenerateCliTokenCommand", function () {
    describe("configuration", function () {
        test("has correct command name", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $command = new GenerateCliTokenCommand($userService, $jwtService);

            expect($command->getName())->toBe("user:generate-cli-token");
        });

        test("has correct description", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $command = new GenerateCliTokenCommand($userService, $jwtService);

            expect($command->getDescription())->toContain("CLI token");
        });
    });

    describe("execute", function () {
        test("generates CLI token with valid credentials", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $command = new GenerateCliTokenCommand($userService, $jwtService);

            $user = TestEntityFactory::createUser();
            $userService
                ->shouldReceive("getUserByEmail")
                ->with(
                    Mockery::on(fn($e) => (string) $e === "test@example.com"),
                )
                ->andReturn($user);
            $userService
                ->shouldReceive("verifyPassword")
                ->with($user, "password123")
                ->andReturn(true);

            $token = new JwtToken("cli.token.value");
            $jwtService
                ->shouldReceive("generate")
                ->with($user, TokenType::CLI_TOKEN)
                ->andReturn($token);

            $input = new ArrayInput([
                "email" => "test@example.com",
                "password" => "password123",
            ]);
            $output = new BufferedOutput();

            $result = $command->run($input, $output);

            expect($result)->toBe(Command::SUCCESS);
            $outputText = $output->fetch();
            expect($outputText)->toContain("CLI token generated successfully");
            expect($outputText)->toContain("cli.token.value");
        });

        test("fails with invalid credentials", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $command = new GenerateCliTokenCommand($userService, $jwtService);

            $user = TestEntityFactory::createUser();
            $userService
                ->shouldReceive("getUserByEmail")
                ->with(
                    Mockery::on(fn($e) => (string) $e === "test@example.com"),
                )
                ->andReturn($user);
            $userService
                ->shouldReceive("verifyPassword")
                ->with($user, "wrongpassword")
                ->andReturn(false);

            $input = new ArrayInput([
                "email" => "test@example.com",
                "password" => "wrongpassword",
            ]);
            $output = new BufferedOutput();

            $result = $command->run($input, $output);

            expect($result)->toBe(Command::FAILURE);
            expect($output->fetch())->toContain("Invalid credentials");
        });

        test("fails when user not found", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $command = new GenerateCliTokenCommand($userService, $jwtService);

            $userService
                ->shouldReceive("getUserByEmail")
                ->with(
                    Mockery::on(
                        fn($e) => (string) $e === "nonexistent@example.com",
                    ),
                )
                ->andThrow(new UserNotFoundException("User not found"));

            $input = new ArrayInput([
                "email" => "nonexistent@example.com",
                "password" => "password123",
            ]);
            $output = new BufferedOutput();

            $result = $command->run($input, $output);

            expect($result)->toBe(Command::FAILURE);
            expect($output->fetch())->toContain("User not found");
        });

        test("fails with invalid email format", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $command = new GenerateCliTokenCommand($userService, $jwtService);

            $input = new ArrayInput([
                "email" => "not-an-email",
                "password" => "password123",
            ]);
            $output = new BufferedOutput();

            $result = $command->run($input, $output);

            expect($result)->toBe(Command::FAILURE);
            expect($output->fetch())->toContain(
                "Email Value object must get a valid e-mail address",
            );
        });

        test("generates CLI_TOKEN type", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $command = new GenerateCliTokenCommand($userService, $jwtService);

            $user = TestEntityFactory::createUser();
            $userService->shouldReceive("getUserByEmail")->andReturn($user);
            $userService->shouldReceive("verifyPassword")->andReturn(true);

            $tokenGenerated = false;
            $jwtService
                ->shouldReceive("generate")
                ->andReturnUsing(function ($u, $type) use (&$tokenGenerated) {
                    $tokenGenerated = $type === TokenType::CLI_TOKEN;
                    return new JwtToken("token");
                });

            $input = new ArrayInput([
                "email" => "test@example.com",
                "password" => "password123",
            ]);
            $output = new BufferedOutput();

            $command->run($input, $output);

            expect($tokenGenerated)->toBeTrue();
        });

        test("displays token in output", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $command = new GenerateCliTokenCommand($userService, $jwtService);

            $user = TestEntityFactory::createUser();
            $userService->shouldReceive("getUserByEmail")->andReturn($user);
            $userService->shouldReceive("verifyPassword")->andReturn(true);

            $token = new JwtToken("my.special.token");
            $jwtService->shouldReceive("generate")->andReturn($token);

            $input = new ArrayInput([
                "email" => "test@example.com",
                "password" => "password123",
            ]);
            $output = new BufferedOutput();

            $command->run($input, $output);

            expect($output->fetch())->toContain("my.special.token");
        });
    });

    describe("extends Command", function () {
        test("extends Symfony Command class", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $jwtService = Mockery::mock(JwtServiceInterface::class);
            $command = new GenerateCliTokenCommand($userService, $jwtService);

            expect($command)->toBeInstanceOf(Command::class);
        });
    });
});
