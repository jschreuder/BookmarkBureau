<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

use jschreuder\BookmarkBureau\Service\RateLimitServiceInterface;

/**
 * Rate limiting configuration interface.
 * Implementations define rate limit thresholds, time windows, and storage strategy.
 */
interface RateLimitConfigInterface
{
    /**
     * Create a rate limit service with this configuration
     */
    public function createRateLimitService(): RateLimitServiceInterface;

    /**
     * Whether to trust proxy headers for IP address resolution.
     */
    public function trustProxyHeadersBool(): bool;
}
