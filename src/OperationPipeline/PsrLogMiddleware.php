<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationPipeline;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final readonly class PsrLogMiddleware implements PipelineMiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private string $operationName,
        private string $logLevel = LogLevel::DEBUG,
    ) {}

    #[\Override]
    public function process(object $data, callable $next): object
    {
        $this->logger->log(
            level: $this->logLevel,
            message: "{$this->operationName} started with object of type " .
                \get_class($data),
        );
        $return = $next($data);
        $this->logger->log(
            level: $this->logLevel,
            message: "{$this->operationName} completed with object of type " .
                \get_class($return),
        );
        return $return;
    }
}
