<?php declare(strict_types = 1);

namespace jschreuder\BookmarkBureau;

use jschreuder\Middle\ApplicationStack;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\ControllerRunner;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\SymfonyRouter;
use jschreuder\Middle\Router\UrlGeneratorInterface;
use jschreuder\Middle\ServerMiddleware\ErrorHandlerMiddleware;
use jschreuder\Middle\ServerMiddleware\JsonRequestParserMiddleware;
use jschreuder\Middle\ServerMiddleware\RoutingMiddleware;
use jschreuder\MiddleDi\ConfigTrait;
use jschreuder\BookmarkBureau\Controller\ErrorHandlerController;
use jschreuder\BookmarkBureau\Controller\NotFoundHandlerController;
use jschreuder\BookmarkBureau\Service\ExampleService;
use PDO;
use Psr\Log\LoggerInterface;

class ServiceContainer
{
    use ConfigTrait;

    public function getApp(): ApplicationStack
    {
        return new ApplicationStack(
            new ControllerRunner(),
            new JsonRequestParserMiddleware(),
            new RoutingMiddleware(
                $this->getAppRouter(),
                $this->get404Handler()
            ),
            new ErrorHandlerMiddleware(
                $this->getLogger(),
                $this->get500Handler()
            )
        );
    }

    public function getLogger(): LoggerInterface
    {
        $logger = new \Monolog\Logger($this->config('logger.name'));
        $logger->pushHandler((new \Monolog\Handler\StreamHandler(
            $this->config('logger.path'),
            \Monolog\Logger::NOTICE
        ))->setFormatter(new \Monolog\Formatter\LineFormatter()));
        return $logger;
    }

    public function getAppRouter(): RouterInterface
    {
        return new SymfonyRouter($this->config('site.url'));
    }

    public function getUrlGenerator() : UrlGeneratorInterface
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

    public function getDb(): PDO
    {
        return new PDO(
            $this->config('db.dsn') . ';dbname=' . $this->config('db.dbname'),
            $this->config('db.user'),
            $this->config('db.pass'),
            [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]
        );
    }
}
