<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau;

use jschreuder\BookmarkBureau\Action\CategoryCreateAction;
use jschreuder\BookmarkBureau\Action\CategoryDeleteAction;
use jschreuder\BookmarkBureau\Action\CategoryLinkCreateAction;
use jschreuder\BookmarkBureau\Action\CategoryLinkDeleteAction;
use jschreuder\BookmarkBureau\Action\CategoryLinkReorderAction;
use jschreuder\BookmarkBureau\Action\CategoryReadAction;
use jschreuder\BookmarkBureau\Action\CategoryReorderAction;
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
use jschreuder\BookmarkBureau\Action\TagListAction;
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
use jschreuder\BookmarkBureau\InputSpec\ReorderCategoriesInputSpec;
use jschreuder\BookmarkBureau\InputSpec\ReorderCategoryLinksInputSpec;
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
use jschreuder\BookmarkBureau\ServiceContainer\DefaultServiceContainer;
use jschreuder\BookmarkBureau\Util\ResourceRouteBuilder;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class GeneralRoutingProvider implements RoutingProviderInterface
{
    public function __construct(private DefaultServiceContainer $container) {}

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
                $this->container->getRateLimitConfig()->trustProxyHeadersBool(),
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

        // Resources
        $this->registerLinkRoutes(
            new ResourceRouteBuilder($router, "link", "/link", "/{link_id}"),
        );

        $this->registerTagRoutes(
            new ResourceRouteBuilder($router, "tag", "/tag", "/{tag_name}"),
        );

        $this->registerLinkTagRoutes(
            new ResourceRouteBuilder(
                $router,
                "link_tag",
                "/link/{link_id}/tag",
                "/{tag_name}",
            ),
        );

        $this->registerDashboardRoutes(
            new ResourceRouteBuilder(
                $router,
                "dashboard",
                "/dashboard",
                "/{dashboard_id}",
            ),
        );

        $this->registerFavoriteRoutes(
            new ResourceRouteBuilder(
                $router,
                "favorite",
                "/dashboard/{dashboard_id}/favorite",
                "/{link_id}",
            ),
        );

        $this->registerCategoryRoutes(
            new ResourceRouteBuilder(
                $router,
                "category",
                "/category",
                "/{category_id}",
            ),
        );

        $this->registerCategoryLinkRoutes(
            new ResourceRouteBuilder(
                $router,
                "category_link",
                "/category/{category_id}/link",
                "/{link_id}",
            ),
        );

        // Dashboard view (complex operation with categories and favorites)
        // This route must be last as it uses a catch-all /{id} pattern with UUID validation
        $router->get(
            "dashboard-view",
            "/{dashboard_id}",
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
                "dashboard_id" =>
                    "[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}",
            ],
        );
    }

    private function registerLinkRoutes(ResourceRouteBuilder $builder): void
    {
        $builder
            ->registerRead(
                fn() => new LinkReadAction(
                    $this->container->getLinkService(),
                    new IdInputSpec("link_id"),
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
                    new IdInputSpec("link_id"),
                ),
            );
    }

    private function registerTagRoutes(ResourceRouteBuilder $builder): void
    {
        $builder
            ->registerList(
                fn() => new TagListAction(
                    $this->container->getTagService(),
                    new TagOutputSpec(),
                ),
            )
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
    }

    private function registerLinkTagRoutes(ResourceRouteBuilder $builder): void
    {
        $builder
            ->registerCreate(
                fn() => new LinkTagCreateAction(
                    $this->container->getTagService(),
                    new LinkTagInputSpec(),
                ),
            )
            ->registerDelete(
                fn() => new LinkTagDeleteAction(
                    $this->container->getTagService(),
                    new LinkTagInputSpec(),
                ),
            );
    }

    private function registerDashboardRoutes(
        ResourceRouteBuilder $builder,
    ): void {
        $builder
            ->registerList(
                fn() => new DashboardListAction(
                    $this->container->getDashboardService(),
                    new DashboardOutputSpec(),
                ),
            )
            ->registerRead(
                fn() => new DashboardReadAction(
                    $this->container->getDashboardService(),
                    new IdInputSpec("dashboard_id"),
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
                    new IdInputSpec("dashboard_id"),
                ),
            )
            ->registerCustom(
                "PUT",
                "reorder-categories",
                "/{dashboard_id}/categories",
                fn() => new CategoryReorderAction(
                    $this->container->getCategoryService(),
                    new ReorderCategoriesInputSpec(),
                    new CategoryOutputSpec(),
                ),
            );
    }

    private function registerFavoriteRoutes(ResourceRouteBuilder $builder): void
    {
        $builder
            ->registerCreate(
                fn() => new FavoriteCreateAction(
                    $this->container->getFavoriteService(),
                    new FavoriteInputSpec(),
                    new FavoriteOutputSpec(),
                ),
            )
            ->registerDelete(
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
    }

    private function registerCategoryRoutes(ResourceRouteBuilder $builder): void
    {
        $builder
            ->registerRead(
                fn() => new CategoryReadAction(
                    $this->container->getCategoryService(),
                    new IdInputSpec("category_id"),
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
                    new IdInputSpec("category_id"),
                ),
            );
    }

    private function registerCategoryLinkRoutes(
        ResourceRouteBuilder $builder,
    ): void {
        $builder
            ->registerCreate(
                fn() => new CategoryLinkCreateAction(
                    $this->container->getCategoryService(),
                    new CategoryLinkInputSpec(),
                    new CategoryLinkOutputSpec(),
                ),
            )
            ->registerDelete(
                fn() => new CategoryLinkDeleteAction(
                    $this->container->getCategoryService(),
                    new CategoryLinkInputSpec(),
                ),
            )
            ->registerCustom(
                "PUT",
                "reorder",
                "",
                fn() => new CategoryLinkReorderAction(
                    $this->container->getCategoryService(),
                    $this->container->getLinkRepository(),
                    new ReorderCategoryLinksInputSpec(),
                    new LinkOutputSpec(new TagOutputSpec()),
                ),
            );
    }
}
