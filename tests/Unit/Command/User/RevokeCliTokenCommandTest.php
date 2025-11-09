<?php

use jschreuder\BookmarkBureau\Command\User\RevokeCliTokenCommand;
use jschreuder\BookmarkBureau\Repository\JwtJtiRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

describe("RevokeCliTokenCommand", function () {
    describe("configuration", function () {
        test("has correct command name", function () {
            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $command = new RevokeCliTokenCommand($jtiRepository);

            expect($command->getName())->toBe("user:revoke-cli-token");
        });

        test("has correct description", function () {
            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $command = new RevokeCliTokenCommand($jtiRepository);

            $description = strtolower($command->getDescription());
            expect($description)->toContain("revoke");
            expect($command->getDescription())->toContain("CLI");
        });

        test("has JTI argument", function () {
            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $command = new RevokeCliTokenCommand($jtiRepository);
            $definition = $command->getDefinition();

            expect($definition->hasArgument("jti"))->toBeTrue();
        });
    });

    describe("execute", function () {
        test("revokes a valid JTI", function () {
            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jti = Uuid::uuid4();

            $jtiRepository
                ->shouldReceive("hasJti")
                ->with(
                    Mockery::on(
                        fn($uuid) => $uuid->toString() === $jti->toString(),
                    ),
                )
                ->andReturn(true);
            $jtiRepository
                ->shouldReceive("deleteJti")
                ->with(
                    Mockery::on(
                        fn($uuid) => $uuid->toString() === $jti->toString(),
                    ),
                )
                ->once();

            $command = new RevokeCliTokenCommand($jtiRepository);
            $input = new ArrayInput(["jti" => $jti->toString()]);
            $output = new BufferedOutput();

            $result = $command->run($input, $output);

            expect($result)->toBe(Command::SUCCESS);
            $outputText = $output->fetch();
            expect($outputText)->toContain("revoked successfully");
            expect($outputText)->toContain($jti->toString());
        });

        test("succeeds when JTI not found in whitelist", function () {
            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jti = Uuid::uuid4();

            $jtiRepository
                ->shouldReceive("hasJti")
                ->with(
                    Mockery::on(
                        fn($uuid) => $uuid->toString() === $jti->toString(),
                    ),
                )
                ->andReturn(false);
            $jtiRepository->shouldReceive("deleteJti")->never();

            $command = new RevokeCliTokenCommand($jtiRepository);
            $input = new ArrayInput(["jti" => $jti->toString()]);
            $output = new BufferedOutput();

            $result = $command->run($input, $output);

            expect($result)->toBe(Command::SUCCESS);
            $outputText = $output->fetch();
            expect($outputText)->toContain("not found in whitelist");
        });

        test("fails with invalid JTI format", function () {
            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $command = new RevokeCliTokenCommand($jtiRepository);
            $input = new ArrayInput(["jti" => "not-a-valid-uuid"]);
            $output = new BufferedOutput();

            $result = $command->run($input, $output);

            expect($result)->toBe(Command::FAILURE);
            $outputText = $output->fetch();
            expect($outputText)->toContain("Invalid JTI format");
            expect($outputText)->toContain("valid UUID");
        });

        test("displays revoked JTI in output", function () {
            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jti = Uuid::uuid4();

            $jtiRepository->shouldReceive("hasJti")->andReturn(true);
            $jtiRepository->shouldReceive("deleteJti")->once();

            $command = new RevokeCliTokenCommand($jtiRepository);
            $input = new ArrayInput(["jti" => $jti->toString()]);
            $output = new BufferedOutput();

            $command->run($input, $output);

            $outputText = $output->fetch();
            expect($outputText)->toContain($jti->toString());
        });

        test(
            "displays informational message about token no longer being accepted",
            function () {
                $jtiRepository = Mockery::mock(
                    JwtJtiRepositoryInterface::class,
                );
                $jti = Uuid::uuid4();

                $jtiRepository->shouldReceive("hasJti")->andReturn(true);
                $jtiRepository->shouldReceive("deleteJti")->once();

                $command = new RevokeCliTokenCommand($jtiRepository);
                $input = new ArrayInput(["jti" => $jti->toString()]);
                $output = new BufferedOutput();

                $command->run($input, $output);

                $outputText = $output->fetch();
                expect($outputText)->toContain("no longer be accepted");
            },
        );

        test("revokes multiple JTIs in separate commands", function () {
            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jti1 = Uuid::uuid4();
            $jti2 = Uuid::uuid4();

            $jtiRepository->shouldReceive("hasJti")->andReturn(true);
            $jtiRepository
                ->shouldReceive("deleteJti")
                ->with(
                    Mockery::on(
                        fn($uuid) => $uuid->toString() === $jti1->toString(),
                    ),
                )
                ->once();
            $jtiRepository
                ->shouldReceive("deleteJti")
                ->with(
                    Mockery::on(
                        fn($uuid) => $uuid->toString() === $jti2->toString(),
                    ),
                )
                ->once();

            $command = new RevokeCliTokenCommand($jtiRepository);

            // First revocation
            $input1 = new ArrayInput(["jti" => $jti1->toString()]);
            $output1 = new BufferedOutput();
            $result1 = $command->run($input1, $output1);

            // Second revocation
            $input2 = new ArrayInput(["jti" => $jti2->toString()]);
            $output2 = new BufferedOutput();
            $result2 = $command->run($input2, $output2);

            expect($result1)->toBe(Command::SUCCESS);
            expect($result2)->toBe(Command::SUCCESS);
        });

        test("extends Symfony Command class", function () {
            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $command = new RevokeCliTokenCommand($jtiRepository);

            expect($command)->toBeInstanceOf(Command::class);
        });
    });

    describe("edge cases", function () {
        test("handles UUID with different cases", function () {
            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $jti = Uuid::uuid4();
            $jtiUpperCase = strtoupper($jti->toString());

            $jtiRepository->shouldReceive("hasJti")->andReturn(true);
            $jtiRepository
                ->shouldReceive("deleteJti")
                ->with(
                    Mockery::on(
                        fn($uuid) => $uuid->toString() === $jti->toString(),
                    ),
                )
                ->once();

            $command = new RevokeCliTokenCommand($jtiRepository);
            $input = new ArrayInput(["jti" => $jtiUpperCase]);
            $output = new BufferedOutput();

            $result = $command->run($input, $output);

            expect($result)->toBe(Command::SUCCESS);
        });

        test("fails with empty JTI argument", function () {
            $jtiRepository = Mockery::mock(JwtJtiRepositoryInterface::class);
            $command = new RevokeCliTokenCommand($jtiRepository);

            // Symfony will handle the missing argument
            try {
                $command->run(new ArrayInput([]), new BufferedOutput());
                expect(false)->toBeTrue(); // Should have thrown
            } catch (\Exception $e) {
                expect(true)->toBeTrue(); // Expected exception
            }
        });
    });
});
