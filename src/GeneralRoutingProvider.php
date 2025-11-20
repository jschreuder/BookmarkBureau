<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau;

use jschreuder\BookmarkBureau\Action\CategoryCreateAction;
use jschreuder\BookmarkBureau\Action\CategoryDeleteAction;
use jschreuder\BookmarkBureau\Action\CategoryLinkCreateAction;
use jschreuder\BookmarkBureau\Action\CategoryLinkDeleteAction;
use jschreuder\BookmarkBureau\Action\CategoryReadAction;
use jschreuder\BookmarkBureau\Action\CategoryUpdateAction;
use jschreuder\BookmarkBureau\Action\DashboardCreateAction;
use jschreuder\BookmarkBureau\Action\DashboardDeleteAction;
use jschreuder\BookmarkBureau\Action\DashboardListAction;
use jschreuder\BookmarkBureau\Action\DashboardReadAction;
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
use jschreuder\BookmarkBureau\Controller\DashboardViewController;
use jschreuder\BookmarkBureau\Controller\LoginController;
use jschreuder\BookmarkBureau\Controller\RefreshTokenController;
use jschreuder\BookmarkBureau\InputSpec\CategoryLinkInputSpec;
use jschreuder\BookmarkBureau\InputSpec\CategoryInputSpec;
use jschreuder\BookmarkBureau\InputSpec\LoginInputSpec;
use jschreuder\BookmarkBureau\InputSpec\DashboardInputSpec;
use jschreuder\BookmarkBureau\InputSpec\FavoriteInputSpec;
use jschreuder\BookmarkBureau\InputSpec\IdInputSpec;
use jschreuder\BookmarkBureau\InputSpec\LinkInputSpec;
use jschreuder\BookmarkBureau\InputSpec\LinkTagInputSpec;
use jschreuder\BookmarkBureau\InputSpec\ReorderFavoritesInputSpec;
use jschreuder\BookmarkBureau\InputSpec\TagInputSpec;
use jschreuder\BookmarkBureau\InputSpec\TagNameInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\CategoryLinkOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\CategoryOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\DashboardOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\FullDashboardOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\FavoriteOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TokenOutputSpec;
use jschreuder\BookmarkBureau\Response\JsonResponseTransformer;
use jschreuder\BookmarkBureau\Util\ResourceRouteBuilder;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class GeneralRoutingProvider implements RoutingProviderInterface
{
    private ServiceContainer $container;

    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public function registerRoutes(RouterInterface $router): void
    {
        $router->get(
            "home",
            "/",
            fn() => new class implements ControllerInterface {
                #[\Override]
                public function execute(
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    return new JsonResponse(["message" => "Hello world!"]);
                }
            },
        );

        // Authentication routes
        $router->post(
            "auth.login",
            "/auth/login",
            fn() => new LoginController(
                new LoginInputSpec(),
                $this->container->getUserService(),
                $this->container->getJwtService(),
                $this->container->getTotpVerifier(),
                new TokenOutputSpec(),
                new JsonResponseTransformer(),
                $this->container->getRateLimitService(),
                $this->container->config("ratelimit.trust_proxy_headers"),
            ),
        );

        $router->post(
            "auth.token-refresh",
            "/auth/token-refresh",
            fn() => new RefreshTokenController(
                $this->container->getJwtService(),
                $this->container->getUserService(),
                new TokenOutputSpec(),
                new JsonResponseTransformer(),
            ),
        );

        // Links
        new ResourceRouteBuilder($router, "link", "/link")
            ->registerRead(
                fn() => new LinkReadAction(
                    $this->container->getLinkService(),
                    new IdInputSpec(),
                    new LinkOutputSpec(new TagOutputSpec()),
                ),
            )
            ->registerCreate(
                fn() => new LinkCreateAction(
                    $this->container->getLinkService(),
                    new LinkInputSpec(),
                    new LinkOutputSpec(new TagOutputSpec()),
                ),
            )
            ->registerUpdate(
                fn() => new LinkUpdateAction(
                    $this->container->getLinkService(),
                    new LinkInputSpec(),
                    new LinkOutputSpec(new TagOutputSpec()),
                ),
            )
            ->registerDelete(
                fn() => new LinkDeleteAction(
                    $this->container->getLinkService(),
                    new IdInputSpec(),
                ),
            );

        // Tags
        new ResourceRouteBuilder($router, "tag", "/tag")
            ->registerRead(
                fn() => new TagReadAction(
                    $this->container->getTagService(),
                    new TagNameInputSpec(),
                    new TagOutputSpec(),
                ),
            )
            ->registerCreate(
                fn() => new TagCreateAction(
                    $this->container->getTagService(),
                    new TagInputSpec(),
                    new TagOutputSpec(),
                ),
            )
            ->registerUpdate(
                fn() => new TagUpdateAction(
                    $this->container->getTagService(),
                    new TagInputSpec(),
                    new TagOutputSpec(),
                ),
            )
            ->registerDelete(
                fn() => new TagDeleteAction(
                    $this->container->getTagService(),
                    new TagNameInputSpec(),
                ),
            );

        // Link-Tag associations
        new ResourceRouteBuilder($router, "link_tag", "/link/{id}/tag")
            ->registerCreate(
                fn() => new LinkTagCreateAction(
                    $this->container->getTagService(),
                    new LinkTagInputSpec(),
                ),
            )
            ->registerCustom(
                "DELETE",
                "delete",
                "/{tag_name}",
                fn() => new LinkTagDeleteAction(
                    $this->container->getTagService(),
                    new LinkTagInputSpec(),
                ),
            );

        // Dashboards
        new ResourceRouteBuilder($router, "dashboard", "/dashboard")
            ->registerCustom(
                "GET",
                "list",
                "",
                fn() => new DashboardListAction(
                    $this->container->getDashboardService(),
                    new DashboardOutputSpec(),
                ),
            )
            ->registerRead(
                fn() => new DashboardReadAction(
                    $this->container->getDashboardService(),
                    new IdInputSpec(),
                    new DashboardOutputSpec(),
                ),
            )
            ->registerCreate(
                fn() => new DashboardCreateAction(
                    $this->container->getDashboardService(),
                    new DashboardInputSpec(),
                    new DashboardOutputSpec(),
                ),
            )
            ->registerUpdate(
                fn() => new DashboardUpdateAction(
                    $this->container->getDashboardService(),
                    new DashboardInputSpec(),
                    new DashboardOutputSpec(),
                ),
            )
            ->registerDelete(
                fn() => new DashboardDeleteAction(
                    $this->container->getDashboardService(),
                    new DashboardInputSpec(),
                ),
            );

        // Favorites
        new ResourceRouteBuilder(
            $router,
            "favorite",
            "/dashboard/{id}/favorites",
        )
            ->registerCreate(
                fn() => new FavoriteCreateAction(
                    $this->container->getFavoriteService(),
                    new FavoriteInputSpec(),
                    new FavoriteOutputSpec(),
                ),
            )
            ->registerCustom(
                "DELETE",
                "delete",
                "",
                fn() => new FavoriteDeleteAction(
                    $this->container->getFavoriteService(),
                    new FavoriteInputSpec(),
                ),
            )
            ->registerCustom(
                "PUT",
                "reorder",
                "",
                fn() => new FavoriteReorderAction(
                    $this->container->getFavoriteService(),
                    new ReorderFavoritesInputSpec(),
                    new FavoriteOutputSpec(),
                ),
            );

        // Categories
        new ResourceRouteBuilder($router, "category", "/category")
            ->registerRead(
                fn() => new CategoryReadAction(
                    $this->container->getCategoryService(),
                    new IdInputSpec(),
                    new CategoryOutputSpec(),
                ),
            )
            ->registerCreate(
                fn() => new CategoryCreateAction(
                    $this->container->getCategoryService(),
                    new CategoryInputSpec(),
                    new CategoryOutputSpec(),
                ),
            )
            ->registerUpdate(
                fn() => new CategoryUpdateAction(
                    $this->container->getCategoryService(),
                    new CategoryInputSpec(),
                    new CategoryOutputSpec(),
                ),
            )
            ->registerDelete(
                fn() => new CategoryDeleteAction(
                    $this->container->getCategoryService(),
                    new CategoryInputSpec(),
                ),
            );

        // Category-Link associations
        new ResourceRouteBuilder(
            $router,
            "category_link",
            "/category/{id}/link",
        )
            ->registerCreate(
                fn() => new CategoryLinkCreateAction(
                    $this->container->getCategoryService(),
                    new CategoryLinkInputSpec(),
                    new CategoryLinkOutputSpec(),
                ),
            )
            ->registerCustom(
                "DELETE",
                "delete",
                "",
                fn() => new CategoryLinkDeleteAction(
                    $this->container->getCategoryService(),
                    new CategoryLinkInputSpec(),
                ),
            );

        // Dashboard view (complex operation with categories and favorites)
        // This route must be last as it uses a catch-all /{id} pattern with UUID validation
        $router->get(
            "dashboard-view",
            "/{id}",
            fn() => new DashboardViewController(
                $this->container->getDashboardService(),
                new JsonResponseTransformer(),
                new FullDashboardOutputSpec(
                    new DashboardOutputSpec(),
                    new CategoryOutputSpec(),
                    new LinkOutputSpec(new TagOutputSpec()),
                ),
            ),
            [],
            [
                "id" =>
                    "[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}",
            ],
        );
    }
}
