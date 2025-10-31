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

        // Links
        $linkService = $this->container->getLinkService();
        $linkInput = new LinkInputSpec();
        $linkOutput = new LinkOutputSpec();
        (new ResourceRouteBuilder($router, 'link', '/link'))
            ->registerRead(new LinkReadAction($linkService, $linkInput, $linkOutput))
            ->registerCreate(new LinkCreateAction($linkService, $linkInput, $linkOutput))
            ->registerUpdate(new LinkUpdateAction($linkService, $linkInput, $linkOutput))
            ->registerDelete(new LinkDeleteAction($linkService, $linkInput));

        // Tags
        $tagService = $this->container->getTagService();
        $tagInput = new TagInputSpec();
        $tagNameInput = new TagNameInputSpec();
        $tagOutput = new TagOutputSpec();
        (new ResourceRouteBuilder($router, 'tag', '/tag'))
            ->registerRead(new TagReadAction($tagService, $tagNameInput, $tagOutput))
            ->registerCreate(new TagCreateAction($tagService, $tagInput, $tagOutput))
            ->registerUpdate(new TagUpdateAction($tagService, $tagInput, $tagOutput))
            ->registerDelete(new TagDeleteAction($tagService, $tagNameInput));

        // Link-Tag associations
        $linkTagInput = new LinkTagInputSpec();
        (new ResourceRouteBuilder($router, 'link_tag', '/link/:id/tag'))
            ->registerCreate(new LinkTagCreateAction($tagService, $linkTagInput))
            ->registerCustom('DELETE', 'delete', '/:tag_name', new LinkTagDeleteAction($tagService, $linkTagInput));

        // Dashboards
        $dashboardService = $this->container->getDashboardService();
        $dashboardInput = new DashboardInputSpec();
        $dashboardOutput = new DashboardOutputSpec();
        (new ResourceRouteBuilder($router, 'dashboard', '/dashboard'))
            ->registerCreate(new DashboardCreateAction($dashboardService, $dashboardInput, $dashboardOutput))
            ->registerUpdate(new DashboardUpdateAction($dashboardService, $dashboardInput, $dashboardOutput))
            ->registerDelete(new DashboardDeleteAction($dashboardService, $dashboardInput));

        // Favorites
        $favoriteService = $this->container->getFavoriteService();
        $favoriteInput = new FavoriteInputSpec();
        $favoriteOutput = new FavoriteOutputSpec();
        $reorderFavoritesInput = new ReorderFavoritesInputSpec();
        (new ResourceRouteBuilder($router, 'favorite', '/dashboard/:id/favorites'))
            ->registerCreate(new FavoriteCreateAction($favoriteService, $favoriteInput, $favoriteOutput))
            ->registerCustom('DELETE', 'delete', '', new FavoriteDeleteAction($favoriteService, $favoriteInput))
            ->registerCustom('PUT', 'reorder', '', new FavoriteReorderAction(
                $favoriteService,
                $reorderFavoritesInput,
                $favoriteOutput
            ));

        // Categories
        $categoryService = $this->container->getCategoryService();
        $categoryInput = new CategoryInputSpec();
        $categoryOutput = new CategoryOutputSpec();
        (new ResourceRouteBuilder($router, 'category', '/category'))
            ->registerRead(new CategoryReadAction($categoryService, $categoryInput, $categoryOutput))
            ->registerCreate(new CategoryCreateAction($categoryService, $categoryInput, $categoryOutput))
            ->registerUpdate(new CategoryUpdateAction($categoryService, $categoryInput, $categoryOutput))
            ->registerDelete(new CategoryDeleteAction($categoryService, $categoryInput));
    }
}
