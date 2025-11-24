<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Config;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final readonly class MonologLoggerConfig implements LoggerConfigInterface
{
    public function __construct(
        public string $name,
        public string $logPath,
        public bool $enableRequestLogging = true,
        public Level $level = Level::Notice,
    ) {}

    #[\Override]
    public function createLogger(): LoggerInterface
    {
        $logger = new Logger($this->name);
        $logger->pushHandler(
            new StreamHandler($this->logPath, $this->level)
                ->setFormatter(new LineFormatter()),
        );
        return $logger;
    }
}
