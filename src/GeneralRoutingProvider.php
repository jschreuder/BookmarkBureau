<?php declare(strict_types = 1);

namespace jschreuder\BookmarkBureau;

use jschreuder\BookmarkBureau\Action\CategoryCreateAction;
use jschreuder\BookmarkBureau\Action\CategoryDeleteAction;
use jschreuder\BookmarkBureau\Action\CategoryReadAction;
use jschreuder\BookmarkBureau\Action\CategoryUpdateAction;
use jschreuder\BookmarkBureau\Action\DashboardCreateAction;
use jschreuder\BookmarkBureau\Action\DashboardDeleteAction;
use jschreuder\BookmarkBureau\Action\DashboardUpdateAction;
use jschreuder\BookmarkBureau\Action\FavoriteCreateAction;
use jschreuder\BookmarkBureau\Action\FavoriteDeleteAction;
use jschreuder\BookmarkBureau\Action\FavoriteReorderAction;
use jschreuder\BookmarkBureau\Action\LinkCreateAction;
use jschreuder\BookmarkBureau\Action\LinkDeleteAction;
use jschreuder\BookmarkBureau\Action\LinkReadAction;
use jschreuder\BookmarkBureau\Action\LinkUpdateAction;
use jschreuder\BookmarkBureau\Action\LinkTagCreateAction;
use jschreuder\BookmarkBureau\Action\LinkTagDeleteAction;
use jschreuder\BookmarkBureau\Action\TagCreateAction;
use jschreuder\BookmarkBureau\Action\TagDeleteAction;
use jschreuder\BookmarkBureau\Action\TagReadAction;
use jschreuder\BookmarkBureau\Action\TagUpdateAction;
use jschreuder\BookmarkBureau\InputSpec\CategoryInputSpec;
use jschreuder\BookmarkBureau\InputSpec\DashboardInputSpec;
use jschreuder\BookmarkBureau\InputSpec\FavoriteInputSpec;
use jschreuder\BookmarkBureau\InputSpec\LinkInputSpec;
use jschreuder\BookmarkBureau\InputSpec\LinkTagInputSpec;
use jschreuder\BookmarkBureau\InputSpec\ReorderFavoritesInputSpec;
use jschreuder\BookmarkBureau\InputSpec\TagInputSpec;
use jschreuder\BookmarkBureau\InputSpec\TagNameInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\CategoryOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\DashboardOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\FavoriteOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\BookmarkBureau\Util\ResourceRouteBuilder;
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

    #[\Override]
    public function registerRoutes(RouterInterface $router): void
    {
        $router->get('home', '/', fn () => new class implements ControllerInterface {
            public function execute(ServerRequestInterface $request): ResponseInterface
            {
                return new JsonResponse(['message' => 'Hello world!']);
            }
        });

        $linkService = $this->container->getLinkService();
        (new ResourceRouteBuilder($router, 'link', '/link'))
            ->registerRead(new LinkReadAction($linkService, new LinkInputSpec(), new LinkOutputSpec()))
            ->registerCreate(new LinkCreateAction($linkService, new LinkInputSpec(), new LinkOutputSpec()))
            ->registerUpdate(new LinkUpdateAction($linkService, new LinkInputSpec(), new LinkOutputSpec()))
            ->registerDelete(new LinkDeleteAction($linkService, new LinkInputSpec()));

        $categoryService = $this->container->getCategoryService();
        (new ResourceRouteBuilder($router, 'category', '/category'))
            ->registerRead(new CategoryReadAction($categoryService, new CategoryInputSpec(), new CategoryOutputSpec()))
            ->registerCreate(new CategoryCreateAction($categoryService, new CategoryInputSpec(), new CategoryOutputSpec()))
            ->registerUpdate(new CategoryUpdateAction($categoryService, new CategoryInputSpec(), new CategoryOutputSpec()))
            ->registerDelete(new CategoryDeleteAction($categoryService, new CategoryInputSpec()));

        $dashboardService = $this->container->getDashboardService();
        (new ResourceRouteBuilder($router, 'dashboard', '/dashboard'))
            ->registerCreate(new DashboardCreateAction($dashboardService, new DashboardInputSpec(), new DashboardOutputSpec()))
            ->registerUpdate(new DashboardUpdateAction($dashboardService, new DashboardInputSpec(), new DashboardOutputSpec()))
            ->registerDelete(new DashboardDeleteAction($dashboardService, new DashboardInputSpec()));

        $favoriteService = $this->container->getFavoriteService();
        (new ResourceRouteBuilder($router, 'favorite', '/dashboard/:id/favorites'))
            ->registerCreate(new FavoriteCreateAction($favoriteService, new FavoriteInputSpec(), new FavoriteOutputSpec()))
            ->registerCustom('DELETE', 'delete', '', new FavoriteDeleteAction($favoriteService, new FavoriteInputSpec()))
            ->registerCustom('PUT', 'reorder', '', new FavoriteReorderAction(
                $favoriteService,
                new ReorderFavoritesInputSpec(),
                new FavoriteOutputSpec()
            ));

        $tagService = $this->container->getTagService();
        (new ResourceRouteBuilder($router, 'tag', '/tag'))
            ->registerRead(new TagReadAction($tagService, new TagNameInputSpec(), new TagOutputSpec()))
            ->registerCreate(new TagCreateAction($tagService, new TagInputSpec(), new TagOutputSpec()))
            ->registerUpdate(new TagUpdateAction($tagService, new TagInputSpec(), new TagOutputSpec()))
            ->registerDelete(new TagDeleteAction($tagService, new TagNameInputSpec()));

        (new ResourceRouteBuilder($router, 'link_tag', '/link/:id/tag'))
            ->registerCreate(new LinkTagCreateAction($tagService, new LinkTagInputSpec()))
            ->registerCustom('DELETE', 'delete', '/:tag_name', new LinkTagDeleteAction($tagService, new LinkTagInputSpec()));
    }
}
