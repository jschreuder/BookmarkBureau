<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Command\Security\RateLimitCleanupCommand;
use jschreuder\BookmarkBureau\Service\RateLimitServiceInterface;
use Symfony\Component\Console\Tester\CommandTester;

describe("RateLimitCleanupCommand", function () {
    describe("execute", function () {
        test("should delete expired records and display count", function () {
            $rateLimitService = Mockery::mock(RateLimitServiceInterface::class);
            $rateLimitService
                ->shouldReceive("cleanup")
                ->once()
                ->andReturn(5);

            $command = new RateLimitCleanupCommand($rateLimitService);
            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain(
                "Cleaning up expired rate limiting data",
            );
            expect($tester->getDisplay())->toContain(
                "Deleted 5 expired record(s)",
            );
        });

        test("should handle zero expired records", function () {
            $rateLimitService = Mockery::mock(RateLimitServiceInterface::class);
            $rateLimitService
                ->shouldReceive("cleanup")
                ->once()
                ->andReturn(0);

            $command = new RateLimitCleanupCommand($rateLimitService);
            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain(
                "No expired records to delete",
            );
        });

        test("should handle large number of deletions", function () {
            $rateLimitService = Mockery::mock(RateLimitServiceInterface::class);
            $rateLimitService
                ->shouldReceive("cleanup")
                ->once()
                ->andReturn(1000);

            $command = new RateLimitCleanupCommand($rateLimitService);
            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(0);
            expect($tester->getDisplay())->toContain(
                "Deleted 1000 expired record(s)",
            );
        });

        test("should handle service exceptions", function () {
            $rateLimitService = Mockery::mock(RateLimitServiceInterface::class);
            $rateLimitService
                ->shouldReceive("cleanup")
                ->once()
                ->andThrow(new Exception("Database connection failed"));

            $command = new RateLimitCleanupCommand($rateLimitService);
            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain(
                "Cleanup error: Database connection failed",
            );
        });

        test("should handle PDOException from service", function () {
            $rateLimitService = Mockery::mock(RateLimitServiceInterface::class);
            $rateLimitService
                ->shouldReceive("cleanup")
                ->once()
                ->andThrow(new PDOException("Connection lost"));

            $command = new RateLimitCleanupCommand($rateLimitService);
            $tester = new CommandTester($command);
            $statusCode = $tester->execute([]);

            expect($statusCode)->toBe(1);
            expect($tester->getDisplay())->toContain(
                "Cleanup error: Connection lost",
            );
        });
    });
});
