<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

final readonly class DefaultIpWhitelistConfig implements
    IpWhitelistConfigInterface
{
    /**
     * @param array<string> $allowedIpRanges Array of IPs or CIDR ranges (e.g., ["192.168.1.0/24", "10.0.0.5"])
     * @param bool $trustProxyHeaders Whether to trust X-Forwarded-For headers
     */
    public function __construct(
        private array $allowedIpRanges = [],
        private bool $trustProxyHeaders = false,
    ) {}

    #[\Override]
    public function getAllowedIpRanges(): array
    {
        return $this->allowedIpRanges;
    }

    #[\Override]
    public function trustProxyHeaders(): bool
    {
        return $this->trustProxyHeaders;
    }
}
