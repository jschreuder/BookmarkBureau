<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

/**
 * Configuration for IP-based access restrictions.
 *
 * Allows restricting access to specific IP addresses or ranges (CIDR notation).
 * Useful for limiting admin access to local network or specific locations.
 */
interface IpWhitelistConfigInterface
{
    /**
     * Get list of allowed IP addresses or CIDR ranges.
     *
     * @return array<string> Empty array means no IP whitelist (all IPs allowed)
     */
    public function getAllowedIpRanges(): array;

    /**
     * Whether to trust proxy headers (X-Forwarded-For) for client IP detection.
     *
     * Should match the rate limit config setting.
     */
    public function trustProxyHeaders(): bool;
}
