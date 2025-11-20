<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Command\User\TotpCommand;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Symfony\Component\Console\Tester\CommandTester;

describe("TotpCommand", function () {
    describe("execute", function () {
        test("should enable TOTP for user", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new TotpCommand($userService);

            $email = "test@example.com";
            $user = TestEntityFactory::createUser(email: new Email($email));
            $totpSecret = new TotpSecret("JBSWY3DPEBLW64TMMQ");

            $userService
                ->shouldReceive("getUserByEmail")
                ->with(Mockery::on(fn($e) => (string) $e === $email))
                ->andReturn($user);

            $userService
                ->shouldReceive("enableTotp")
                ->with($user->userId)
                ->andReturn($totpSecret);

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "action" => "enable",
                "email" => $email,
            ]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain("TOTP enabled");
            expect($tester->getDisplay())->toContain("JBSWY3DPEBLW64TMMQ");
        });

        test("should disable TOTP for user", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new TotpCommand($userService);

            $email = "test@example.com";
            $user = TestEntityFactory::createUser(email: new Email($email));

            $userService
                ->shouldReceive("getUserByEmail")
                ->with(Mockery::on(fn($e) => (string) $e === $email))
                ->andReturn($user);

            $userService
                ->shouldReceive("disableTotp")
                ->with($user->userId)
                ->once();

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "action" => "disable",
                "email" => $email,
            ]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain("TOTP disabled");
        });

        test("should reject invalid action", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new TotpCommand($userService);

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "action" => "invalid",
                "email" => "test@example.com",
            ]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain("Invalid action");
        });

        test("should show error for non-existent user", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new TotpCommand($userService);

            $email = "nonexistent@example.com";

            $userService
                ->shouldReceive("getUserByEmail")
                ->andThrow(new UserNotFoundException());

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "action" => "enable",
                "email" => $email,
            ]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain("not found");
        });

        test("should reject invalid email", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new TotpCommand($userService);

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "action" => "enable",
                "email" => "invalid-email",
            ]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain("Error:");
        });

        test("should be case-insensitive for action", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new TotpCommand($userService);

            $email = "test@example.com";
            $user = TestEntityFactory::createUser(email: new Email($email));

            $userService
                ->shouldReceive("getUserByEmail")
                ->with(Mockery::on(fn($e) => (string) $e === $email))
                ->andReturn($user);

            $userService
                ->shouldReceive("disableTotp")
                ->with($user->userId)
                ->once();

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "action" => "DISABLE",
                "email" => $email,
            ]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain("TOTP disabled");
        });
    });
});
