<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller;

use jschreuder\BookmarkBureau\Exception\RateLimitExceededException;
use jschreuder\Middle\Controller\ControllerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

final readonly class ErrorHandlerController implements ControllerInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[\Override]
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $exception = $request->getAttribute("error");
        assert(
            $exception instanceof Throwable,
            "Error attribute must be a Throwable instance",
        );
        $code = $this->getCode($exception);
        $message = $this->getMessage($exception, $code);

        $logLevel = $this->getLogLevel($code);
        $this->logger->log($logLevel, $message);

        $response = new JsonResponse(
            [
                "message" => $message,
            ],
            $code,
        );

        // Add Retry-After header for rate limit responses
        if ($exception instanceof RateLimitExceededException) {
            $retryAfter = $exception->getRetryAfterSeconds();
            if ($retryAfter !== null) {
                $response = $response->withHeader(
                    "Retry-After",
                    (string) $retryAfter,
                );
            }
        }

        return $response;
    }

    private function getCode(Throwable $exception): int
    {
        $code = $exception->getCode();
        return match (true) {
            $exception instanceof RateLimitExceededException => 429,
            $exception instanceof \PDOException => 503,
            $code >= 400 && $code < 600 => $code,
            default => 500,
        };
    }

    private function getMessage(Throwable $exception, int $code): string
    {
        return match (true) {
            $exception instanceof RateLimitExceededException
                => $exception->getMessage(),
            $code === 400 => "Bad input",
            $code === 401 => "Unauthenticated",
            $code === 403 => "Unauthorized",
            $code === 429 => "Too many requests",
            $code === 503 => "Storage engine error",
            default => "Server error",
        };
    }

    private function getLogLevel(int $code): string
    {
        return match ($code) {
            400, 401, 403, 429 => LogLevel::WARNING,
            503 => LogLevel::CRITICAL,
            default => LogLevel::ERROR,
        };
    }
}
