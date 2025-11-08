<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Command\User\CreateCommand;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

describe("CreateCommand", function () {
    describe("execute", function () {
        test("should create user with password argument", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new CreateCommand($userService);

            $email = "test@example.com";
            $password = "testpassword123";
            $user = TestEntityFactory::createUser(
                email: new \jschreuder\BookmarkBureau\Entity\Value\Email(
                    $email,
                ),
            );

            $userService
                ->shouldReceive("createUser")
                ->with(Mockery::on(fn($e) => (string) $e === $email), $password)
                ->andReturn($user);

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "email" => $email,
                "password" => $password,
            ]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain(
                "User created successfully!",
            );
            expect($tester->getDisplay())->toContain($email);
            expect($tester->getDisplay())->toContain($user->userId);
        });

        test("should prompt for password when not provided", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new CreateCommand($userService);
            $command->setHelperSet(new HelperSet([new QuestionHelper()]));

            $email = "test@example.com";
            $interactivePassword = "interactivepassword456";
            $user = TestEntityFactory::createUser(
                email: new \jschreuder\BookmarkBureau\Entity\Value\Email(
                    $email,
                ),
            );

            $userService
                ->shouldReceive("createUser")
                ->with(
                    Mockery::on(fn($e) => (string) $e === $email),
                    $interactivePassword,
                )
                ->andReturn($user);

            $tester = new CommandTester($command);
            $tester->setInputs([$interactivePassword]); // Provide password via stdin
            $statusCode = $tester->execute(["email" => $email]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain(
                "User created successfully!",
            );
            expect($tester->getDisplay())->toContain($email);
        });

        test("should reject empty password when prompted", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new CreateCommand($userService);
            $command->setHelperSet(new HelperSet([new QuestionHelper()]));

            $email = "test@example.com";

            $userService->shouldReceive("createUser")->never();

            $tester = new CommandTester($command);
            $tester->setInputs([""]); // Empty password
            $statusCode = $tester->execute(["email" => $email]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain(
                "Password cannot be empty",
            );
        });

        test("should reject duplicate email", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new CreateCommand($userService);

            $email = "duplicate@example.com";
            $password = "testpassword123";

            $userService
                ->shouldReceive("createUser")
                ->andThrow(
                    new \jschreuder\BookmarkBureau\Exception\DuplicateEmailException(),
                );

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "email" => $email,
                "password" => $password,
            ]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain("already exists");
        });

        test("should reject invalid email", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new CreateCommand($userService);

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "email" => "not-an-email",
                "password" => "password",
            ]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain("Error:");
        });

        test("should create user with TOTP enabled", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new CreateCommand($userService);

            $email = "test@example.com";
            $password = "testpassword123";
            $user = TestEntityFactory::createUser(
                email: new \jschreuder\BookmarkBureau\Entity\Value\Email(
                    $email,
                ),
            );
            $totpSecret = new \jschreuder\BookmarkBureau\Entity\Value\TotpSecret(
                "JBSWY3DPEBLW64TMMQ",
            );

            $userService
                ->shouldReceive("createUser")
                ->with(Mockery::on(fn($e) => (string) $e === $email), $password)
                ->andReturn($user);

            $userService
                ->shouldReceive("enableTotp")
                ->with($user->userId)
                ->andReturn($totpSecret);

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([
                "email" => $email,
                "password" => $password,
                "--enable-totp" => true,
            ]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain("TOTP enabled!");
            expect($tester->getDisplay())->toContain("JBSWY3DPEBLW64TMMQ");
        });
    });
});
