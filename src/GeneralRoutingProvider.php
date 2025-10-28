<?php declare(strict_types = 1);

namespace jschreuder\BookmarkBureau;

use jschreuder\BookmarkBureau\Action\CategoryCreateAction;
use jschreuder\BookmarkBureau\Action\CategoryDeleteAction;
use jschreuder\BookmarkBureau\Action\CategoryReadAction;
use jschreuder\BookmarkBureau\Action\CategoryUpdateAction;
use jschreuder\BookmarkBureau\Action\DashboardCreateAction;
use jschreuder\BookmarkBureau\Action\DashboardDeleteAction;
use jschreuder\BookmarkBureau\Action\DashboardUpdateAction;
use jschreuder\BookmarkBureau\Action\LinkCreateAction;
use jschreuder\BookmarkBureau\Action\LinkDeleteAction;
use jschreuder\BookmarkBureau\Action\LinkReadAction;
use jschreuder\BookmarkBureau\Action\LinkUpdateAction;
use jschreuder\BookmarkBureau\Controller\ActionController;
use jschreuder\BookmarkBureau\InputSpec\CategoryInputSpec;
use jschreuder\BookmarkBureau\InputSpec\DashboardInputSpec;
use jschreuder\BookmarkBureau\InputSpec\LinkInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\CategoryOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\DashboardOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec;
use jschreuder\BookmarkBureau\Response\JsonResponseTransformer;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class GeneralRoutingProvider implements RoutingProviderInterface
{
    private ServiceContainer $container;

    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    public function registerRoutes(RouterInterface $router): void
    {
        $router->get('home', '/', fn () => new class implements ControllerInterface {
            public function execute(ServerRequestInterface $request): ResponseInterface
            {
                return new JsonResponse(['message' => 'Hello world!']);
            }
        });

        $router->get('link-read', '/link/:id', fn() => new ActionController(
            new LinkReadAction(
                $this->container->getLinkService(),
                new LinkInputSpec(),
                new LinkOutputSpec()
            ),
            new JsonResponseTransformer()
        ));
        $router->post('link-create', '/link', fn() => new ActionController(
            new LinkCreateAction(
                $this->container->getLinkService(),
                new LinkInputSpec(),
                new LinkOutputSpec()
            ),
            new JsonResponseTransformer()
        ));
        $router->put('link-update', '/link/:id', fn() => new ActionController(
            new LinkUpdateAction(
                $this->container->getLinkService(),
                new LinkInputSpec(),
                new LinkOutputSpec()
            ),
            new JsonResponseTransformer()
        ));
        $router->delete('link-delete', '/link/:id', fn() => new ActionController(
            new LinkDeleteAction(
                $this->container->getLinkService(),
                new LinkInputSpec()
            ),
            new JsonResponseTransformer()
        ));

        $router->get('category-read', '/category/:id', fn() => new ActionController(
            new CategoryReadAction(
                $this->container->getCategoryService(),
                new CategoryInputSpec(),
                new CategoryOutputSpec()
            ),
            new JsonResponseTransformer()
        ));
        $router->post('category-create', '/category', fn() => new ActionController(
            new CategoryCreateAction(
                $this->container->getCategoryService(),
                new CategoryInputSpec(),
                new CategoryOutputSpec()
            ),
            new JsonResponseTransformer()
        ));
        $router->put('category-update', '/category/:id', fn() => new ActionController(
            new CategoryUpdateAction(
                $this->container->getCategoryService(),
                new CategoryInputSpec(),
                new CategoryOutputSpec()
            ),
            new JsonResponseTransformer()
        ));
        $router->delete('category-delete', '/category/:id', fn() => new ActionController(
            new CategoryDeleteAction(
                $this->container->getCategoryService(),
                new CategoryInputSpec()
            ),
            new JsonResponseTransformer()
        ));

        $router->post('dashboard-create', '/dashboard', fn() => new ActionController(
            new DashboardCreateAction(
                $this->container->getDashboardService(),
                new DashboardInputSpec(),
                new DashboardOutputSpec()
            ),
            new JsonResponseTransformer()
        ));
        $router->put('dashboard-update', '/dashboard/:id', fn() => new ActionController(
            new DashboardUpdateAction(
                $this->container->getDashboardService(),
                new DashboardInputSpec(),
                new DashboardOutputSpec()
            ),
            new JsonResponseTransformer()
        ));
        $router->delete('dashboard-delete', '/dashboard/:id', fn() => new ActionController(
            new DashboardDeleteAction(
                $this->container->getDashboardService(),
                new DashboardInputSpec()
            ),
            new JsonResponseTransformer()
        ));
    }
}
