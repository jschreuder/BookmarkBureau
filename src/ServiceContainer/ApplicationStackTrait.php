<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\ServiceContainer;

use Closure;
use jschreuder\Middle\ApplicationStack;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\ControllerRunner;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\SymfonyRouter;
use jschreuder\Middle\Router\UrlGeneratorInterface;
use jschreuder\Middle\ServerMiddleware\ErrorHandlerMiddleware;
use jschreuder\Middle\ServerMiddleware\JsonRequestParserMiddleware;
use jschreuder\Middle\ServerMiddleware\RoutingMiddleware;
use jschreuder\Middle\ServerMiddleware\RequestFilterMiddleware;
use jschreuder\Middle\ServerMiddleware\RequestValidatorMiddleware;
use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\BookmarkBureau\Controller\ErrorHandlerController;
use jschreuder\BookmarkBureau\Controller\NotFoundHandlerController;
use jschreuder\BookmarkBureau\HttpMiddleware\JwtAuthenticationMiddleware;
use jschreuder\BookmarkBureau\HttpMiddleware\RequireAuthenticationMiddleware;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Monolog;

trait ApplicationStackTrait
{
    abstract protected function config(string $key): mixed;
    abstract public function getJwtService(): JwtServiceInterface;

    public function getApp(): ApplicationStack
    {
        return new ApplicationStack(
            new ControllerRunner(),
            new RequestValidatorMiddleware($this->getValidationErrorHandler()),
            new RequestFilterMiddleware(),
            new JsonRequestParserMiddleware(),
            new RequireAuthenticationMiddleware([
                "home", // GET / - API info endpoint
                "auth.login", // POST /auth/login - User login
                "dashboard-view", // GET /{id} - Public dashboard view
                // All other routes require authentication
            ]),
            new JwtAuthenticationMiddleware($this->getJwtService()),
            new RoutingMiddleware(
                $this->getAppRouter(),
                $this->get404Handler(),
            ),
            new ErrorHandlerMiddleware(
                $this->getLogger(),
                $this->get500Handler(),
            ),
        );
    }

    public function getLogger(): LoggerInterface
    {
        $logger = new Monolog\Logger($this->config("logger.name"));
        $logger->pushHandler(
            new Monolog\Handler\StreamHandler(
                $this->config("logger.path"),
                Monolog\Level::Notice,
            )->setFormatter(new Monolog\Formatter\LineFormatter()),
        );
        return $logger;
    }

    public function getAppRouter(): RouterInterface
    {
        return new SymfonyRouter($this->config("site.url"));
    }

    public function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->getAppRouter()->getGenerator();
    }

    public function get404Handler(): ControllerInterface
    {
        return new NotFoundHandlerController();
    }

    public function get500Handler(): ControllerInterface
    {
        return new ErrorHandlerController($this->getLogger());
    }

    public function getValidationErrorHandler(): Closure
    {
        return fn(
            ServerRequestInterface $request,
            ValidationFailedException $error,
        ) => new JsonResponse(["errors" => $error->getValidationErrors()], 400);
    }
}
