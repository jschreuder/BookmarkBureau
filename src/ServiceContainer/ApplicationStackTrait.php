<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\ServiceContainer;

use Closure;
use jschreuder\BookmarkBureau\Config\LoggerConfigInterface;
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
use jschreuder\BookmarkBureau\Config\IpWhitelistConfigInterface;
use jschreuder\BookmarkBureau\Controller\ErrorHandlerController;
use jschreuder\BookmarkBureau\Controller\NotFoundHandlerController;
use jschreuder\BookmarkBureau\HttpMiddleware\IpWhitelistMiddleware;
use jschreuder\BookmarkBureau\HttpMiddleware\JwtAuthenticationMiddleware;
use jschreuder\BookmarkBureau\HttpMiddleware\RequireAuthenticationMiddleware;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

trait ApplicationStackTrait
{
    abstract public function getLoggerConfig(): LoggerConfigInterface;
    abstract public function getIpWhitelistConfig(): IpWhitelistConfigInterface;
    abstract public function siteUrlString(): string;
    abstract public function getJwtService(): JwtServiceInterface;

    public function getApp(): ApplicationStack
    {
        $publicRoutes = ["home", "auth.login", "dashboard-view"];

        return new ApplicationStack(
            new ControllerRunner(),
            new RequestValidatorMiddleware($this->getValidationErrorHandler()),
            new RequestFilterMiddleware(),
            new JsonRequestParserMiddleware(),
            new IpWhitelistMiddleware(
                $this->getIpWhitelistConfig()->getAllowedIpRanges(),
                $publicRoutes,
                $this->getIpWhitelistConfig()->trustProxyHeaders(),
            ),
            new RequireAuthenticationMiddleware($publicRoutes),
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
        return $this->getLoggerConfig()->createLogger();
    }

    public function getAppRouter(): RouterInterface
    {
        return new SymfonyRouter($this->siteUrlString());
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
