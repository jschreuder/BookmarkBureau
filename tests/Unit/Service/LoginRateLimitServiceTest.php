<?php

use jschreuder\BookmarkBureau\Exception\RateLimitExceededException;
use jschreuder\BookmarkBureau\Repository\LoginRateLimitRepositoryInterface;
use jschreuder\BookmarkBureau\Service\LoginRateLimitService;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;

describe("LoginRateLimitService", function () {
    describe("checkBlock method", function () {
        test("should not throw when user is not blocked", function () {
            $clock = new MockClock("2024-01-01 12:00:00");
            $repository = Mockery::mock(
                LoginRateLimitRepositoryInterface::class,
            );
            $repository
                ->shouldReceive("getBlockInfo")
                ->with("user@example.com", "192.168.1.1", "2024-01-01 12:00:00")
                ->andReturn([
                    "blocked" => false,
                    "username" => null,
                    "ip" => null,
                    "expires_at" => null,
                ]);

            $service = new LoginRateLimitService($repository, $clock);

            $service->checkBlock("user@example.com", "192.168.1.1");

            expect(true)->toBeTrue(); // No exception thrown
        });

        test(
            "should throw RateLimitExceededException when user is blocked",
            function () {
                $clock = new MockClock("2024-01-01 12:00:00");
                $repository = Mockery::mock(
                    LoginRateLimitRepositoryInterface::class,
                );
                $repository
                    ->shouldReceive("getBlockInfo")
                    ->with(
                        "user@example.com",
                        "192.168.1.1",
                        "2024-01-01 12:00:00",
                    )
                    ->andReturn([
                        "blocked" => true,
                        "username" => "user@example.com",
                        "ip" => null,
                        "expires_at" => "2024-01-01 12:10:00",
                    ]);

                $service = new LoginRateLimitService($repository, $clock);

                expect(
                    fn() => $service->checkBlock(
                        "user@example.com",
                        "192.168.1.1",
                    ),
                )->toThrow(RateLimitExceededException::class);
            },
        );

        test(
            "should throw RateLimitExceededException when IP is blocked",
            function () {
                $clock = new MockClock("2024-01-01 12:00:00");
                $repository = Mockery::mock(
                    LoginRateLimitRepositoryInterface::class,
                );
                $repository
                    ->shouldReceive("getBlockInfo")
                    ->with(
                        "user@example.com",
                        "192.168.1.100",
                        "2024-01-01 12:00:00",
                    )
                    ->andReturn([
                        "blocked" => true,
                        "username" => null,
                        "ip" => "192.168.1.100",
                        "expires_at" => "2024-01-01 12:10:00",
                    ]);

                $service = new LoginRateLimitService($repository, $clock);

                expect(
                    fn() => $service->checkBlock(
                        "user@example.com",
                        "192.168.1.100",
                    ),
                )->toThrow(RateLimitExceededException::class);
            },
        );

        test("should use current time from clock", function () {
            $clock = new MockClock("2024-06-15 14:30:45");
            $repository = Mockery::mock(
                LoginRateLimitRepositoryInterface::class,
            );
            $repository
                ->shouldReceive("getBlockInfo")
                ->with("user@example.com", "192.168.1.1", "2024-06-15 14:30:45")
                ->once()
                ->andReturn([
                    "blocked" => false,
                    "username" => null,
                    "ip" => null,
                    "expires_at" => null,
                ]);

            $service = new LoginRateLimitService($repository, $clock);

            $service->checkBlock("user@example.com", "192.168.1.1");

            expect(true)->toBeTrue();
        });
    });

    describe("recordFailure method", function () {
        test(
            "should record failed attempt without creating blocks",
            function () {
                $clock = new MockClock("2024-01-01 12:00:00");
                $repository = Mockery::mock(
                    LoginRateLimitRepositoryInterface::class,
                );
                $repository
                    ->shouldReceive("insertFailedAttempt")
                    ->with(
                        "user@example.com",
                        "192.168.1.1",
                        "2024-01-01 12:00:00",
                    )
                    ->once();
                $repository
                    ->shouldReceive("countAttempts")
                    ->with(
                        "user@example.com",
                        "192.168.1.1",
                        "2024-01-01 12:00:00",
                    )
                    ->andReturn(["user_count" => 5, "ip_count" => 8]);
                $repository->shouldNotReceive("insertBlock");

                $service = new LoginRateLimitService($repository, $clock);

                $service->recordFailure("user@example.com", "192.168.1.1");

                expect(true)->toBeTrue();
            },
        );

        test(
            "should create username block when threshold exceeded",
            function () {
                $clock = new MockClock("2024-01-01 12:00:00");
                $repository = Mockery::mock(
                    LoginRateLimitRepositoryInterface::class,
                );
                $repository
                    ->shouldReceive("insertFailedAttempt")
                    ->with(
                        "user@example.com",
                        "192.168.1.1",
                        "2024-01-01 12:00:00",
                    )
                    ->once();
                $repository
                    ->shouldReceive("countAttempts")
                    ->with(
                        "user@example.com",
                        "192.168.1.1",
                        "2024-01-01 12:00:00",
                    )
                    ->andReturn(["user_count" => 11, "ip_count" => 15]);
                $repository
                    ->shouldReceive("insertBlock")
                    ->with("user@example.com", null, "2024-01-01 12:10:00")
                    ->once();

                $service = new LoginRateLimitService($repository, $clock);

                $service->recordFailure("user@example.com", "192.168.1.1");

                expect(true)->toBeTrue();
            },
        );

        test("should create IP block when threshold exceeded", function () {
            $clock = new MockClock("2024-01-01 12:00:00");
            $repository = Mockery::mock(
                LoginRateLimitRepositoryInterface::class,
            );
            $repository
                ->shouldReceive("insertFailedAttempt")
                ->with("user@example.com", "192.168.1.1", "2024-01-01 12:00:00")
                ->once();
            $repository
                ->shouldReceive("countAttempts")
                ->with("user@example.com", "192.168.1.1", "2024-01-01 12:00:00")
                ->andReturn(["user_count" => 5, "ip_count" => 101]);
            $repository
                ->shouldReceive("insertBlock")
                ->with(null, "192.168.1.1", "2024-01-01 12:10:00")
                ->once();

            $service = new LoginRateLimitService($repository, $clock);

            $service->recordFailure("user@example.com", "192.168.1.1");

            expect(true)->toBeTrue();
        });

        test(
            "should create both blocks when both thresholds exceeded",
            function () {
                $clock = new MockClock("2024-01-01 12:00:00");
                $repository = Mockery::mock(
                    LoginRateLimitRepositoryInterface::class,
                );
                $repository
                    ->shouldReceive("insertFailedAttempt")
                    ->with(
                        "user@example.com",
                        "192.168.1.1",
                        "2024-01-01 12:00:00",
                    )
                    ->once();
                $repository
                    ->shouldReceive("countAttempts")
                    ->with(
                        "user@example.com",
                        "192.168.1.1",
                        "2024-01-01 12:00:00",
                    )
                    ->andReturn(["user_count" => 15, "ip_count" => 150]);
                $repository
                    ->shouldReceive("insertBlock")
                    ->with("user@example.com", null, "2024-01-01 12:10:00")
                    ->once();
                $repository
                    ->shouldReceive("insertBlock")
                    ->with(null, "192.168.1.1", "2024-01-01 12:10:00")
                    ->once();

                $service = new LoginRateLimitService($repository, $clock);

                $service->recordFailure("user@example.com", "192.168.1.1");

                expect(true)->toBeTrue();
            },
        );

        test("should calculate expiration time correctly", function () {
            $clock = new MockClock("2024-01-01 12:00:00");
            $repository = Mockery::mock(
                LoginRateLimitRepositoryInterface::class,
            );
            $repository->shouldReceive("insertFailedAttempt")->once();
            $repository
                ->shouldReceive("countAttempts")
                ->andReturn(["user_count" => 11, "ip_count" => 5]);
            $repository
                ->shouldReceive("insertBlock")
                ->with("user@example.com", null, "2024-01-01 12:10:00")
                ->once();

            $service = new LoginRateLimitService($repository, $clock);

            $service->recordFailure("user@example.com", "192.168.1.1");

            expect(true)->toBeTrue();
        });

        test("should respect custom thresholds", function () {
            $clock = new MockClock("2024-01-01 12:00:00");
            $repository = Mockery::mock(
                LoginRateLimitRepositoryInterface::class,
            );
            $repository->shouldReceive("insertFailedAttempt")->once();
            $repository
                ->shouldReceive("countAttempts")
                ->andReturn(["user_count" => 4, "ip_count" => 40]);
            $repository
                ->shouldReceive("insertBlock")
                ->with("user@example.com", null, "2024-01-01 12:05:00")
                ->once();
            $repository
                ->shouldReceive("insertBlock")
                ->with(null, "192.168.1.1", "2024-01-01 12:05:00")
                ->once();

            // Custom thresholds: 3 for username, 30 for IP, 5 minute window
            $service = new LoginRateLimitService(
                $repository,
                $clock,
                usernameThreshold: 3,
                ipThreshold: 30,
                windowMinutes: 5,
            );

            $service->recordFailure("user@example.com", "192.168.1.1");

            expect(true)->toBeTrue();
        });
    });

    describe("clearUsername method", function () {
        test("should clear username from attempts", function () {
            $clock = new MockClock("2024-01-01 12:00:00");
            $repository = Mockery::mock(
                LoginRateLimitRepositoryInterface::class,
            );
            $repository
                ->shouldReceive("clearUsernameFromAttempts")
                ->with("user@example.com")
                ->once();

            $service = new LoginRateLimitService($repository, $clock);

            $service->clearUsername("user@example.com");

            expect(true)->toBeTrue();
        });

        test("should delegate to repository", function () {
            $clock = new MockClock("2024-01-01 12:00:00");
            $repository = Mockery::mock(
                LoginRateLimitRepositoryInterface::class,
            );
            $repository
                ->shouldReceive("clearUsernameFromAttempts")
                ->with("different@example.com")
                ->once();

            $service = new LoginRateLimitService($repository, $clock);

            $service->clearUsername("different@example.com");

            expect(true)->toBeTrue();
        });
    });

    describe("cleanup method", function () {
        test("should return number of deleted records", function () {
            $clock = new MockClock("2024-01-01 12:00:00");
            $repository = Mockery::mock(
                LoginRateLimitRepositoryInterface::class,
            );
            $repository
                ->shouldReceive("deleteExpired")
                ->with("2024-01-01 12:00:00")
                ->andReturn(42);

            $service = new LoginRateLimitService($repository, $clock);

            $result = $service->cleanup();

            expect($result)->toBe(42);
        });

        test("should use current time from clock", function () {
            $clock = new MockClock("2024-06-15 14:30:45");
            $repository = Mockery::mock(
                LoginRateLimitRepositoryInterface::class,
            );
            $repository
                ->shouldReceive("deleteExpired")
                ->with("2024-06-15 14:30:45")
                ->once()
                ->andReturn(0);

            $service = new LoginRateLimitService($repository, $clock);

            $service->cleanup();

            expect(true)->toBeTrue();
        });

        test("should handle zero deletions", function () {
            $clock = new MockClock("2024-01-01 12:00:00");
            $repository = Mockery::mock(
                LoginRateLimitRepositoryInterface::class,
            );
            $repository
                ->shouldReceive("deleteExpired")
                ->with("2024-01-01 12:00:00")
                ->andReturn(0);

            $service = new LoginRateLimitService($repository, $clock);

            $result = $service->cleanup();

            expect($result)->toBe(0);
        });
    });

    describe("integration scenarios", function () {
        test(
            "should handle full attack scenario: multiple failures leading to block",
            function () {
                $clock = new MockClock("2024-01-01 12:00:00");
                $repository = Mockery::mock(
                    LoginRateLimitRepositoryInterface::class,
                );

                // First 10 failures don't trigger block
                $repository->shouldReceive("insertFailedAttempt")->times(11);
                $repository
                    ->shouldReceive("countAttempts")
                    ->times(10)
                    ->andReturn(["user_count" => 5, "ip_count" => 5]);

                // 11th failure triggers block
                $repository
                    ->shouldReceive("countAttempts")
                    ->once()
                    ->andReturn(["user_count" => 11, "ip_count" => 11]);
                $repository
                    ->shouldReceive("insertBlock")
                    ->with("attacker@example.com", null, "2024-01-01 12:10:00")
                    ->once();

                // Block check after threshold reached
                $repository
                    ->shouldReceive("getBlockInfo")
                    ->with(
                        "attacker@example.com",
                        "192.168.1.100",
                        "2024-01-01 12:00:00",
                    )
                    ->andReturn([
                        "blocked" => true,
                        "username" => "attacker@example.com",
                        "ip" => null,
                        "expires_at" => "2024-01-01 12:10:00",
                    ]);

                $service = new LoginRateLimitService($repository, $clock);

                // Record 11 failures
                for ($i = 0; $i < 11; $i++) {
                    $service->recordFailure(
                        "attacker@example.com",
                        "192.168.1.100",
                    );
                }

                // Now should be blocked
                expect(
                    fn() => $service->checkBlock(
                        "attacker@example.com",
                        "192.168.1.100",
                    ),
                )->toThrow(RateLimitExceededException::class);
            },
        );

        test(
            "should handle successful login after failed attempts",
            function () {
                $clock = new MockClock("2024-01-01 12:00:00");
                $repository = Mockery::mock(
                    LoginRateLimitRepositoryInterface::class,
                );

                // User not blocked
                $repository
                    ->shouldReceive("getBlockInfo")
                    ->with(
                        "user@example.com",
                        "192.168.1.1",
                        "2024-01-01 12:00:00",
                    )
                    ->andReturn([
                        "blocked" => false,
                        "username" => null,
                        "ip" => null,
                        "expires_at" => null,
                    ]);

                // Record a few failures
                $repository->shouldReceive("insertFailedAttempt")->times(3);
                $repository
                    ->shouldReceive("countAttempts")
                    ->times(3)
                    ->andReturn(["user_count" => 3, "ip_count" => 3]);

                // Then successful login clears username
                $repository
                    ->shouldReceive("clearUsernameFromAttempts")
                    ->with("user@example.com")
                    ->once();

                $service = new LoginRateLimitService($repository, $clock);

                // Check not blocked
                $service->checkBlock("user@example.com", "192.168.1.1");

                // Record failures
                $service->recordFailure("user@example.com", "192.168.1.1");
                $service->recordFailure("user@example.com", "192.168.1.1");
                $service->recordFailure("user@example.com", "192.168.1.1");

                // Successful login
                $service->clearUsername("user@example.com");

                expect(true)->toBeTrue();
            },
        );

        test("should handle distributed attack from multiple IPs", function () {
            $clock = new MockClock("2024-01-01 12:00:00");
            $repository = Mockery::mock(
                LoginRateLimitRepositoryInterface::class,
            );

            // Same username, different IPs
            $repository->shouldReceive("insertFailedAttempt")->times(3);
            $repository
                ->shouldReceive("countAttempts")
                ->times(3)
                ->andReturn(["user_count" => 15, "ip_count" => 5]);

            // Username block created for each
            $repository
                ->shouldReceive("insertBlock")
                ->with("target@example.com", null, "2024-01-01 12:10:00")
                ->times(3);

            $service = new LoginRateLimitService($repository, $clock);

            // Attacks from different IPs
            $service->recordFailure("target@example.com", "192.168.1.1");
            $service->recordFailure("target@example.com", "192.168.1.2");
            $service->recordFailure("target@example.com", "192.168.1.3");

            expect(true)->toBeTrue();
        });
    });
});
