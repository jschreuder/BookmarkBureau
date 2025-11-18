<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationMiddleware;

use jschreuder\BookmarkBureau\OperationPipeline\PipelineMiddlewareInterface;
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
    public function process(?object $data, callable $next): ?object
    {
        $inputType = $data === null ? "null" : \get_class($data);
        $this->logger->log(
            level: $this->logLevel,
            message: "{$this->operationName} started with object of type {$inputType}",
        );
        $return = $next($data);

        $outputType = $return === null ? "null" : \get_class($return);
        $this->logger->log(
            level: $this->logLevel,
            message: "{$this->operationName} completed with object of type {$outputType}",
        );
        return $return;
    }
}
