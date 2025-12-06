<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Util;

use Psr\Http\Message\ServerRequestInterface;

final class IpAddress
{
    /**
     * Extract IP address from PSR-7 request with optional proxy header support.
     *
     * When trustProxyHeaders is enabled, checks headers in this priority:
     * 1. X-Real-IP (used by Synology, nginx, and many reverse proxies)
     * 2. X-Forwarded-For (standard header, uses first/leftmost IP)
     * 3. REMOTE_ADDR (direct connection, no proxy)
     */
    public static function fromRequest(
        ServerRequestInterface $request,
        bool $trustProxyHeaders = false,
    ): string {
        $serverParams = $request->getServerParams();

        // Check for proxy headers if configured
        if ($trustProxyHeaders) {
            // Try X-Real-IP first (used by Synology NAS, nginx, and many reverse proxies)
            $realIp =
                isset($serverParams["HTTP_X_REAL_IP"]) &&
                \is_string($serverParams["HTTP_X_REAL_IP"])
                    ? $serverParams["HTTP_X_REAL_IP"]
                    : null;
            if ($realIp) {
                return self::normalize($realIp);
            }

            // Fall back to X-Forwarded-For
            $forwardedFor =
                isset($serverParams["HTTP_X_FORWARDED_FOR"]) &&
                \is_string($serverParams["HTTP_X_FORWARDED_FOR"])
                    ? $serverParams["HTTP_X_FORWARDED_FOR"]
                    : null;
            if ($forwardedFor) {
                // X-Forwarded-For can contain multiple IPs: "client, proxy1, proxy2"
                // Use the first (leftmost) IP as the original client
                $ips = array_map("trim", explode(",", $forwardedFor));
                $clientIp = $ips[0];

                return self::normalize($clientIp);
            }
        }

        // Fall back to REMOTE_ADDR with normalization
        $remoteAddr =
            isset($serverParams["REMOTE_ADDR"]) &&
            \is_string($serverParams["REMOTE_ADDR"])
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

    /**
     * Check if an IP address is within a given range (supports CIDR notation).
     *
     * @param string $ip IP address to check
     * @param string $range IP or CIDR range (e.g., "192.168.1.0/24" or "10.0.0.5")
     */
    public static function inRange(string $ip, string $range): bool
    {
        // Single IP address (no CIDR)
        if (!str_contains($range, "/")) {
            return $ip === $range;
        }

        // CIDR range (e.g., 192.168.1.0/24)
        [$subnet, $mask] = explode("/", $range, 2);
        $mask = (int) $mask;

        // Validate CIDR mask
        if ($mask < 0 || $mask > 128) {
            return false;
        }

        // Convert IPs to binary for comparison
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        // IPv4 vs IPv6 check
        if (strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        // Calculate the number of bits to compare
        $bytesToCompare = (int) floor($mask / 8);
        $bitsInLastByte = $mask % 8;

        // Compare full bytes
        if (
            substr($ipBin, 0, $bytesToCompare) !==
            substr($subnetBin, 0, $bytesToCompare)
        ) {
            return false;
        }

        // Compare remaining bits in the last byte
        if ($bitsInLastByte > 0) {
            $ipLastByte = ord($ipBin[$bytesToCompare] ?? "\0");
            $subnetLastByte = ord($subnetBin[$bytesToCompare] ?? "\0");
            $bitMask = 0xff << 8 - $bitsInLastByte;

            if (($ipLastByte & $bitMask) !== ($subnetLastByte & $bitMask)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if an IP address matches any range in a list of allowed ranges.
     *
     * @param string $ip IP address to check
     * @param array<string> $allowedRanges Array of IPs or CIDR ranges
     */
    public static function matchesAnyRange(
        string $ip,
        array $allowedRanges,
    ): bool {
        foreach ($allowedRanges as $range) {
            if (self::inRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }
}
