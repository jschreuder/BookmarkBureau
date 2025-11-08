<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Command\User\ChangePasswordCommand;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

describe("ChangePasswordCommand", function () {
    describe("execute", function () {
        test("should change password with password argument", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new ChangePasswordCommand($userService);

            $email = "test@example.com";
            $newPassword = "newpassword123";
            $user = TestEntityFactory::createUser(
                email: new \jschreuder\BookmarkBureau\Entity\Value\Email(
                    $email,
                ),
            );

            $userService
                ->shouldReceive("getUserByEmail")
                ->with(Mockery::on(fn($e) => (string) $e === $email))
                ->andReturn($user);

            $userService
                ->shouldReceive("changePassword")
                ->with($user->userId, $newPassword)
                ->once();

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "email" => $email,
                "password" => $newPassword,
            ]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain(
                "Password changed successfully",
            );
        });

        test("should prompt for password when not provided", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new ChangePasswordCommand($userService);
            $command->setHelperSet(new HelperSet([new QuestionHelper()]));

            $email = "test@example.com";
            $interactivePassword = "interactivenewpass789";
            $user = TestEntityFactory::createUser(
                email: new \jschreuder\BookmarkBureau\Entity\Value\Email(
                    $email,
                ),
            );

            $userService
                ->shouldReceive("getUserByEmail")
                ->with(Mockery::on(fn($e) => (string) $e === $email))
                ->andReturn($user);

            $userService
                ->shouldReceive("changePassword")
                ->with($user->userId, $interactivePassword)
                ->once();

            $tester = new CommandTester($command);
            $tester->setInputs([$interactivePassword]); // Provide password via stdin
            $statusCode = $tester->execute(["email" => $email]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain(
                "Password changed successfully",
            );
        });

        test("should show error for non-existent user", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new ChangePasswordCommand($userService);

            $email = "nonexistent@example.com";

            $userService
                ->shouldReceive("getUserByEmail")
                ->andThrow(
                    new \jschreuder\BookmarkBureau\Exception\UserNotFoundException(),
                );

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "email" => $email,
                "password" => "newpass",
            ]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain("not found");
        });

        test("should reject invalid email", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new ChangePasswordCommand($userService);

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "email" => "invalid-email",
                "password" => "pass",
            ]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain("Error:");
        });

        test("should reject empty password when prompted", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new ChangePasswordCommand($userService);
            $command->setHelperSet(new HelperSet([new QuestionHelper()]));

            $email = "test@example.com";
            $user = TestEntityFactory::createUser(
                email: new \jschreuder\BookmarkBureau\Entity\Value\Email(
                    $email,
                ),
            );

            $userService
                ->shouldReceive("getUserByEmail")
                ->with(Mockery::on(fn($e) => (string) $e === $email))
                ->andReturn($user);

            $tester = new CommandTester($command);
            $tester->setInputs([""]); // Empty password
            $statusCode = $tester->execute(["email" => $email]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain(
                "Password cannot be empty",
            );
        });
    });
});
