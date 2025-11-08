<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Command\User\DeleteCommand;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

describe("DeleteCommand", function () {
    describe("execute", function () {
        test("should delete user when confirmed", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new DeleteCommand($userService);
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

            $userService
                ->shouldReceive("deleteUser")
                ->with($user->userId)
                ->once();

            $tester = new CommandTester($command);
            $tester->setInputs(["y"]); // Confirm deletion
            $statusCode = $tester->execute(["email" => $email]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain("deleted successfully");
        });

        test("should cancel deletion when not confirmed", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new DeleteCommand($userService);
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

            $userService->shouldReceive("deleteUser")->never();

            $tester = new CommandTester($command);
            $tester->setInputs(["n"]); // Don't confirm
            $statusCode = $tester->execute(["email" => $email]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain("Cancelled");
        });

        test("should show error for non-existent user", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new DeleteCommand($userService);

            $email = "nonexistent@example.com";

            $userService
                ->shouldReceive("getUserByEmail")
                ->andThrow(
                    new \jschreuder\BookmarkBureau\Exception\UserNotFoundException(),
                );

            $tester = new CommandTester($command);
            $statusCode = $tester->execute(["email" => $email]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain("not found");
        });

        test("should reject invalid email", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new DeleteCommand($userService);

            $tester = new CommandTester($command);
            $statusCode = $tester->execute(["email" => "invalid-email"]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain("Error:");
        });
    });
});
