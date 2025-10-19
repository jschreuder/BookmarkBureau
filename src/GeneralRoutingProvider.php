<?php declare(strict_types = 1);

namespace jschreuder\BookmarkBureau;

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use jschreuder\BookmarkBureau\Controller\ExampleController;

class GeneralRoutingProvider implements RoutingProviderInterface
{
    private ServiceContainer $container;

    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    public function registerRoutes(RouterInterface $router): void
    {
        $router->get('home', '/', function () {
            return new ExampleController();
        });
    }
}
