<?php declare(strict_types = 1);

namespace jschreuder\BookmarkBureau\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class ErrorHandlerController implements ControllerInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[\Override]
    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        /** @var  \Throwable $exception */
        $exception = $request->getAttribute('error');
        $code = $this->getCode($exception);
        $message = $this->getMessage($code);

        $this->logger->log($code, $message);
        return new JsonResponse(
            [
                'message' => $message,
            ],
            $code
        );
    }

    private function getCode(\Throwable $exception) : int
    {
        if ($exception instanceof \PDOException) {
            return 503;
        }

        $code = $exception->getCode();
        if ($code >= 400 && $code < 600) {
            return $code;
        }

        return 500;
    }

    private function getMessage(int $code) : string
    {
        return match ($code) {
            400 => 'Bad input',
            401 => 'Unauthenticated',
            403 => 'Unauthorized',
            503 => 'Storage engine error',
            default => 'Server error'
        };
    }
}
