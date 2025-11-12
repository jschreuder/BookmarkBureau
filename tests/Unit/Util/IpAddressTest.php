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

        test("should extract IP from X-Forwarded-For when trust is enabled", function () {
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
        });

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

        test("should ignore X-Forwarded-For when trust is disabled", function () {
            $request = new ServerRequest(
                [
                    "REMOTE_ADDR" => "192.168.1.100",
                    "HTTP_X_FORWARDED_FOR" => "203.0.113.5",
                ],
                [],
                "/test",
                "GET",
            );

            $ip = IpAddress::fromRequest($request, trustProxyHeaders: false);

            expect($ip)->toBe("192.168.1.100");
        });

        test("should fall back to REMOTE_ADDR when X-Forwarded-For is missing", function () {
            $request = new ServerRequest(
                ["REMOTE_ADDR" => "192.168.1.100"],
                [],
                "/test",
                "GET",
            );

            $ip = IpAddress::fromRequest($request, trustProxyHeaders: true);

            expect($ip)->toBe("192.168.1.100");
        });

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
});
