<?php

use jschreuder\BookmarkBureau\Util\IpAddress;
use Laminas\Diactoros\ServerRequest;

describe("IpAddress", function () {
    describe("fromRequest method", function () {
        test("should extract IP from REMOTE_ADDR", function () {
            $request = new ServerRequest(
                ["REMOTE_ADDR" => "192.168.1.100"],
                [],
                "/test",
                "GET",
            );

            $ip = IpAddress::fromRequest($request);

            expect($ip)->toBe("192.168.1.100");
        });

        test("should use fallback when REMOTE_ADDR is missing", function () {
            $request = new ServerRequest([], [], "/test", "GET");

            $ip = IpAddress::fromRequest($request);

            expect($ip)->toBe("0.0.0.0");
        });

        test(
            "should extract IP from X-Forwarded-For when trust is enabled",
            function () {
                $request = new ServerRequest(
                    [
                        "REMOTE_ADDR" => "10.0.0.1",
                        "HTTP_X_FORWARDED_FOR" => "203.0.113.5",
                    ],
                    [],
                    "/test",
                    "GET",
                );

                $ip = IpAddress::fromRequest($request, trustProxyHeaders: true);

                expect($ip)->toBe("203.0.113.5");
            },
        );

        test("should use first IP from X-Forwarded-For chain", function () {
            $request = new ServerRequest(
                [
                    "REMOTE_ADDR" => "10.0.0.1",
                    "HTTP_X_FORWARDED_FOR" => "203.0.113.5, 10.0.0.2, 10.0.0.3",
                ],
                [],
                "/test",
                "GET",
            );

            $ip = IpAddress::fromRequest($request, trustProxyHeaders: true);

            expect($ip)->toBe("203.0.113.5");
        });

        test("should trim whitespace from X-Forwarded-For IPs", function () {
            $request = new ServerRequest(
                [
                    "REMOTE_ADDR" => "10.0.0.1",
                    "HTTP_X_FORWARDED_FOR" => "  203.0.113.5  ,  10.0.0.2  ",
                ],
                [],
                "/test",
                "GET",
            );

            $ip = IpAddress::fromRequest($request, trustProxyHeaders: true);

            expect($ip)->toBe("203.0.113.5");
        });

        test(
            "should ignore X-Forwarded-For when trust is disabled",
            function () {
                $request = new ServerRequest(
                    [
                        "REMOTE_ADDR" => "192.168.1.100",
                        "HTTP_X_FORWARDED_FOR" => "203.0.113.5",
                    ],
                    [],
                    "/test",
                    "GET",
                );

                $ip = IpAddress::fromRequest(
                    $request,
                    trustProxyHeaders: false,
                );

                expect($ip)->toBe("192.168.1.100");
            },
        );

        test(
            "should fall back to REMOTE_ADDR when X-Forwarded-For is missing",
            function () {
                $request = new ServerRequest(
                    ["REMOTE_ADDR" => "192.168.1.100"],
                    [],
                    "/test",
                    "GET",
                );

                $ip = IpAddress::fromRequest($request, trustProxyHeaders: true);

                expect($ip)->toBe("192.168.1.100");
            },
        );

        test("should normalize IPv6 from X-Forwarded-For", function () {
            $request = new ServerRequest(
                [
                    "REMOTE_ADDR" => "10.0.0.1",
                    "HTTP_X_FORWARDED_FOR" => "::ffff:192.168.1.1",
                ],
                [],
                "/test",
                "GET",
            );

            $ip = IpAddress::fromRequest($request, trustProxyHeaders: true);

            expect($ip)->toBe("192.168.1.1");
        });

        test("should normalize IPv6 from REMOTE_ADDR", function () {
            $request = new ServerRequest(
                ["REMOTE_ADDR" => "::ffff:192.168.1.1"],
                [],
                "/test",
                "GET",
            );

            $ip = IpAddress::fromRequest($request);

            expect($ip)->toBe("192.168.1.1");
        });
    });

    describe("normalize method", function () {
        test("should pass through IPv4 addresses unchanged", function () {
            expect(IpAddress::normalize("192.168.1.1"))->toBe("192.168.1.1");
            expect(IpAddress::normalize("10.0.0.1"))->toBe("10.0.0.1");
            expect(IpAddress::normalize("203.0.113.5"))->toBe("203.0.113.5");
        });

        test("should convert IPv4-mapped IPv6 to IPv4", function () {
            expect(IpAddress::normalize("::ffff:192.168.1.1"))->toBe(
                "192.168.1.1",
            );
            expect(IpAddress::normalize("::ffff:10.0.0.1"))->toBe("10.0.0.1");
            expect(IpAddress::normalize("::ffff:203.0.113.5"))->toBe(
                "203.0.113.5",
            );
        });

        test("should normalize IPv6 addresses to canonical form", function () {
            // Compressed form
            expect(IpAddress::normalize("2001:db8::1"))->toBe("2001:db8::1");

            // Full form should be normalized
            expect(
                IpAddress::normalize("2001:0db8:0000:0000:0000:0000:0000:0001"),
            )->toBe("2001:db8::1");

            // Mixed compression
            expect(IpAddress::normalize("2001:db8:0:0:1:0:0:1"))->toBe(
                "2001:db8::1:0:0:1",
            );
        });

        test("should handle localhost addresses", function () {
            expect(IpAddress::normalize("127.0.0.1"))->toBe("127.0.0.1");
            expect(IpAddress::normalize("::1"))->toBe("::1");
        });

        test("should handle invalid IP-like strings gracefully", function () {
            // Invalid IPs are returned as-is
            expect(IpAddress::normalize("not-an-ip"))->toBe("not-an-ip");
            expect(IpAddress::normalize("999.999.999.999"))->toBe(
                "999.999.999.999",
            );
        });

        test("should handle fallback IP", function () {
            expect(IpAddress::normalize("0.0.0.0"))->toBe("0.0.0.0");
        });

        test("should not convert regular IPv6 to IPv4", function () {
            // Regular IPv6 addresses should stay as IPv6
            $ipv6 = "2001:db8::1";
            expect(IpAddress::normalize($ipv6))->toBe($ipv6);
        });

        test("should handle malformed IPv4-mapped IPv6", function () {
            // If the part after ::ffff: is not a valid IPv4, return as-is
            expect(IpAddress::normalize("::ffff:not-valid"))->toBe(
                "::ffff:not-valid",
            );
        });
    });

    describe("inRange method", function () {
        test("should match exact IP addresses", function () {
            expect(IpAddress::inRange("192.168.1.100", "192.168.1.100"))->toBe(
                true,
            );
            expect(IpAddress::inRange("10.0.0.5", "10.0.0.5"))->toBe(true);
            expect(IpAddress::inRange("192.168.1.100", "192.168.1.101"))->toBe(
                false,
            );
        });

        test("should match IPv4 CIDR ranges", function () {
            // /24 range (192.168.1.0 - 192.168.1.255)
            expect(IpAddress::inRange("192.168.1.100", "192.168.1.0/24"))->toBe(
                true,
            );
            expect(IpAddress::inRange("192.168.1.1", "192.168.1.0/24"))->toBe(
                true,
            );
            expect(IpAddress::inRange("192.168.1.255", "192.168.1.0/24"))->toBe(
                true,
            );
            expect(IpAddress::inRange("192.168.2.1", "192.168.1.0/24"))->toBe(
                false,
            );

            // /16 range (10.0.0.0 - 10.0.255.255)
            expect(IpAddress::inRange("10.0.1.5", "10.0.0.0/16"))->toBe(true);
            expect(IpAddress::inRange("10.0.255.255", "10.0.0.0/16"))->toBe(
                true,
            );
            expect(IpAddress::inRange("10.1.0.1", "10.0.0.0/16"))->toBe(false);

            // /32 range (single IP)
            expect(IpAddress::inRange("192.168.1.1", "192.168.1.1/32"))->toBe(
                true,
            );
            expect(IpAddress::inRange("192.168.1.2", "192.168.1.1/32"))->toBe(
                false,
            );
        });

        test("should match IPv6 CIDR ranges", function () {
            // /64 range
            expect(IpAddress::inRange("2001:db8::1", "2001:db8::/64"))->toBe(
                true,
            );
            expect(IpAddress::inRange("2001:db8::ffff", "2001:db8::/64"))->toBe(
                true,
            );
            expect(IpAddress::inRange("2001:db8:1::1", "2001:db8::/64"))->toBe(
                false,
            );

            // /128 range (single IP)
            expect(IpAddress::inRange("2001:db8::1", "2001:db8::1/128"))->toBe(
                true,
            );
            expect(IpAddress::inRange("2001:db8::2", "2001:db8::1/128"))->toBe(
                false,
            );
        });

        test("should handle localhost ranges", function () {
            expect(IpAddress::inRange("127.0.0.1", "127.0.0.0/8"))->toBe(true);
            expect(IpAddress::inRange("127.0.0.1", "127.0.0.1/32"))->toBe(true);
            expect(IpAddress::inRange("::1", "::1/128"))->toBe(true);
        });

        test("should reject invalid CIDR masks", function () {
            expect(IpAddress::inRange("192.168.1.1", "192.168.1.0/33"))->toBe(
                false,
            );
            expect(IpAddress::inRange("192.168.1.1", "192.168.1.0/-1"))->toBe(
                false,
            );
            expect(IpAddress::inRange("2001:db8::1", "2001:db8::/129"))->toBe(
                false,
            );
        });

        test("should reject mismatched IP versions", function () {
            // IPv4 IP against IPv6 range
            expect(IpAddress::inRange("192.168.1.1", "2001:db8::/64"))->toBe(
                false,
            );
            // IPv6 IP against IPv4 range
            expect(IpAddress::inRange("2001:db8::1", "192.168.1.0/24"))->toBe(
                false,
            );
        });

        test("should handle invalid IP addresses", function () {
            expect(IpAddress::inRange("not-an-ip", "192.168.1.0/24"))->toBe(
                false,
            );
            expect(IpAddress::inRange("192.168.1.1", "not-valid/24"))->toBe(
                false,
            );
        });
    });

    describe("matchesAnyRange method", function () {
        test("should return true if IP matches any range", function () {
            $ranges = ["192.168.1.0/24", "10.0.0.0/8", "172.16.0.5"];

            expect(IpAddress::matchesAnyRange("192.168.1.100", $ranges))->toBe(
                true,
            );
            expect(IpAddress::matchesAnyRange("10.5.5.5", $ranges))->toBe(true);
            expect(IpAddress::matchesAnyRange("172.16.0.5", $ranges))->toBe(
                true,
            );
        });

        test("should return false if IP does not match any range", function () {
            $ranges = ["192.168.1.0/24", "10.0.0.0/8"];

            expect(IpAddress::matchesAnyRange("172.16.0.1", $ranges))->toBe(
                false,
            );
            expect(IpAddress::matchesAnyRange("203.0.113.5", $ranges))->toBe(
                false,
            );
        });

        test("should return false for empty ranges array", function () {
            expect(IpAddress::matchesAnyRange("192.168.1.1", []))->toBe(false);
        });

        test("should handle mixed IPv4 and IPv6 ranges", function () {
            $ranges = ["192.168.1.0/24", "2001:db8::/64"];

            expect(IpAddress::matchesAnyRange("192.168.1.100", $ranges))->toBe(
                true,
            );
            expect(IpAddress::matchesAnyRange("2001:db8::1", $ranges))->toBe(
                true,
            );
            expect(IpAddress::matchesAnyRange("10.0.0.1", $ranges))->toBe(
                false,
            );
        });
    });
});
