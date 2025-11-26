<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Util;

use Psr\Http\Message\ServerRequestInterface;

final class IpAddress
{
    /**
     * Extract IP address from PSR-7 request with optional proxy header support.
     */
    public static function fromRequest(
        ServerRequestInterface $request,
        bool $trustProxyHeaders = false,
    ): string {
        $serverParams = $request->getServerParams();

        // Check for proxy headers (X-Forwarded-For) if configured
        if ($trustProxyHeaders) {
            $forwardedFor = \is_string($serverParams["HTTP_X_FORWARDED_FOR"])
                ? $serverParams["HTTP_X_FORWARDED_FOR"]
                : null;
            if ($forwardedFor) {
                // X-Forwarded-For can contain multiple IPs: "client, proxy1, proxy2"
                // Use the first (leftmost) IP as the original client
                $ips = array_map("trim", explode(",", $forwardedFor));
                $clientIp = $ips[0];

                // Normalize IPv6 addresses
                return self::normalize($clientIp);
            }
        }

        // Fall back to REMOTE_ADDR with normalization
        $remoteAddr = \is_string($serverParams["REMOTE_ADDR"])
            ? $serverParams["REMOTE_ADDR"]
            : "0.0.0.0";
        return self::normalize($remoteAddr);
    }

    /**
     * Normalize IP address to prevent bypass via format variations.
     *
     * - Converts IPv4-mapped IPv6 addresses to IPv4 (::ffff:192.168.1.1 → 192.168.1.1)
     * - Normalizes IPv6 addresses to canonical form
     */
    public static function normalize(string $ip): string
    {
        // Convert IPv4-mapped IPv6 addresses to IPv4
        // ::ffff:192.168.1.1 -> 192.168.1.1
        if (str_starts_with($ip, "::ffff:")) {
            $ipv4 = substr($ip, 7);
            if (filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ipv4;
            }
        }

        // Validate and normalize IPv6 addresses
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // inet_pton/inet_ntop normalizes IPv6 format
            $packed = @inet_pton($ip);
            if ($packed !== false) {
                return inet_ntop($packed) ?: $ip;
            }
        }

        // Return as-is if valid IPv4 or fallback
        return $ip;
    }
}
