<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Exception\RateLimitExceededException;

describe("RateLimitExceededException", function () {
    describe("constructor", function () {
        test("should create exception with default message", function () {
            $exception = new RateLimitExceededException();

            expect($exception->getMessage())->toBe(
                "Rate limit exceeded. Too many failed login attempts.",
            );
        });

        test("should include expiration time in message when provided", function () {
            $expiresAt = new \DateTimeImmutable("2025-11-20 15:30:00");
            $exception = new RateLimitExceededException(
                expiresAt: $expiresAt,
            );

            expect($exception->getMessage())->toContain(
                "Rate limit exceeded. Too many failed login attempts.",
            );
            expect($exception->getMessage())->toContain(
                "Try again after 2025-11-20 15:30:00",
            );
        });

        test("should store blocked username", function () {
            $exception = new RateLimitExceededException(
                blockedUsername: "john.doe",
            );

            expect($exception->getBlockedUsername())->toBe("john.doe");
        });

        test("should store blocked IP address", function () {
            $exception = new RateLimitExceededException(
                blockedIp: "192.168.1.100",
            );

            expect($exception->getBlockedIp())->toBe("192.168.1.100");
        });

        test("should store expiration time", function () {
            $expiresAt = new \DateTimeImmutable("2025-11-20 15:30:00");
            $exception = new RateLimitExceededException(
                expiresAt: $expiresAt,
            );

            expect($exception->getExpiresAt())->toBe($expiresAt);
        });

        test("should store all parameters", function () {
            $expiresAt = new \DateTimeImmutable("2025-11-20 15:30:00");
            $exception = new RateLimitExceededException(
                blockedUsername: "jane.doe",
                blockedIp: "203.0.113.45",
                expiresAt: $expiresAt,
            );

            expect($exception->getBlockedUsername())->toBe("jane.doe");
            expect($exception->getBlockedIp())->toBe("203.0.113.45");
            expect($exception->getExpiresAt())->toBe($expiresAt);
        });
    });

    describe("getBlockedUsername", function () {
        test("should return null when not provided", function () {
            $exception = new RateLimitExceededException();
            expect($exception->getBlockedUsername())->toBeNull();
        });

        test("should return provided username", function () {
            $exception = new RateLimitExceededException(
                blockedUsername: "admin",
            );
            expect($exception->getBlockedUsername())->toBe("admin");
        });
    });

    describe("getBlockedIp", function () {
        test("should return null when not provided", function () {
            $exception = new RateLimitExceededException();
            expect($exception->getBlockedIp())->toBeNull();
        });

        test("should return provided IP address", function () {
            $exception = new RateLimitExceededException(
                blockedIp: "10.0.0.1",
            );
            expect($exception->getBlockedIp())->toBe("10.0.0.1");
        });

        test("should handle IPv6 addresses", function () {
            $ipv6 = "2001:db8::1";
            $exception = new RateLimitExceededException(blockedIp: $ipv6);
            expect($exception->getBlockedIp())->toBe($ipv6);
        });
    });

    describe("getExpiresAt", function () {
        test("should return null when not provided", function () {
            $exception = new RateLimitExceededException();
            expect($exception->getExpiresAt())->toBeNull();
        });

        test("should return provided expiration time", function () {
            $expiresAt = new \DateTimeImmutable("2025-11-20 20:00:00");
            $exception = new RateLimitExceededException(expiresAt: $expiresAt);
            expect($exception->getExpiresAt())->toBe($expiresAt);
        });
    });

    describe("getRetryAfterSeconds", function () {
        test("should return null when expiration time not provided", function () {
            $exception = new RateLimitExceededException();
            expect($exception->getRetryAfterSeconds())->toBeNull();
        });

        test("should return positive seconds when expiration is in future", function () {
            $now = new \DateTimeImmutable();
            $expiresAt = $now->modify("+60 seconds");
            $exception = new RateLimitExceededException(expiresAt: $expiresAt);

            $seconds = $exception->getRetryAfterSeconds();
            expect($seconds)->toBeGreaterThanOrEqual(59);
            expect($seconds)->toBeLessThanOrEqual(60);
        });

        test("should return 0 when expiration time has passed", function () {
            $expiresAt = new \DateTimeImmutable("-10 seconds");
            $exception = new RateLimitExceededException(expiresAt: $expiresAt);

            expect($exception->getRetryAfterSeconds())->toBe(0);
        });

        test("should return 0 for expired times (using max)", function () {
            $expiresAt = new \DateTimeImmutable("-100 seconds");
            $exception = new RateLimitExceededException(expiresAt: $expiresAt);

            expect($exception->getRetryAfterSeconds())->toBe(0);
        });

        test("should return accurate seconds for various time spans", function () {
            $now = new \DateTimeImmutable();

            // Test 5 minutes
            $expiresAt = $now->modify("+300 seconds");
            $exception = new RateLimitExceededException(expiresAt: $expiresAt);
            $seconds = $exception->getRetryAfterSeconds();
            expect($seconds)->toBeGreaterThanOrEqual(299);
            expect($seconds)->toBeLessThanOrEqual(300);
        });
    });

    describe("exception inheritance", function () {
        test("should extend RuntimeException", function () {
            $exception = new RateLimitExceededException();
            expect($exception)->toBeInstanceOf(RuntimeException::class);
        });

        test("should be throwable", function () {
            expect(function () {
                throw new RateLimitExceededException(
                    blockedUsername: "user",
                );
            })->toThrow(RateLimitExceededException::class);
        });
    });
});
