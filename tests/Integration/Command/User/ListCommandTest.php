<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Command\User\ListCommand;
use jschreuder\BookmarkBureau\Collection\UserCollection;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Symfony\Component\Console\Tester\CommandTester;

describe("ListCommand", function () {
    describe("execute", function () {
        test("should display no users message when empty", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new ListCommand($userService);

            $userService
                ->shouldReceive("listAllUsers")
                ->andReturn(new UserCollection());

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain("No users found");
        });

        test("should display users in table format", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new ListCommand($userService);

            $user1 = TestEntityFactory::createUser(
                email: new Email("user1@example.com"),
            );
            $user2 = TestEntityFactory::createUser(
                email: new Email("user2@example.com"),
            );

            $userService
                ->shouldReceive("listAllUsers")
                ->andReturn(new UserCollection($user1, $user2));

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain("user1@example.com");
            expect($tester->getDisplay())->toContain("user2@example.com");
            expect($tester->getDisplay())->toContain("Email");
            expect($tester->getDisplay())->toContain("Has TOTP");
        });

        test("should indicate TOTP status correctly", function () {
            $userService = Mockery::mock(UserServiceInterface::class);
            $command = new ListCommand($userService);

            $userWithTotp = TestEntityFactory::createUser(
                email: new Email("with-totp@example.com"),
                totpSecret: new TotpSecret("JBSWY3DPEBLW64TMMQ"),
            );
            $userWithoutTotp = TestEntityFactory::createUser(
                email: new Email("without-totp@example.com"),
            );

            $userService
                ->shouldReceive("listAllUsers")
                ->andReturn(
                    new UserCollection($userWithTotp, $userWithoutTotp),
                );

            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(0);
            $output = $tester->getDisplay();
            expect($output)->toContain("with-totp@example.com");
            expect($output)->toContain("without-totp@example.com");
            expect($output)->toContain("Yes");
            expect($output)->toContain("No");
        });
    });
});
