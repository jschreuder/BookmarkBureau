<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

use Psr\Log\LoggerInterface;

/**
 * Logger configuration interface.
 * Implementations define logger setup, including handlers and formatting.
 */
interface LoggerConfigInterface
{
    /**
     * Create and return a configured logger instance
     */
    public function createLogger(): LoggerInterface;
}
