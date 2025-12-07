<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Util;

use jschreuder\BookmarkBureau\Action\ActionInterface;
use jschreuder\BookmarkBureau\Controller\ActionController;
use jschreuder\BookmarkBureau\Response\JsonResponseTransformer;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouterInterface;
use Closure;

/**
 * Helper for registering groups of CRUD routes with reduced verbosity.
 *
 * Maintains explicit, type-safe code by requiring all dependencies to be
 * passed directly - no magic, no array configuration, no hidden behavior.
 */
final readonly class ResourceRouteBuilder
{
    public function __construct(
        private RouterInterface $router,
        private string $resourceName,
        private string $pathSegment,
        private string $idSegment = "/{id}",
    ) {}

    /**
     * Register a GET route for reading a single resource.
     *
     * @param Closure(): ActionInterface $actionFactory Closure that creates the action when needed
     */
    public function registerRead(Closure $actionFactory): self
    {
        $this->router->get(
            "{$this->resourceName}-read",
            "{$this->pathSegment}{$this->idSegment}",
            fn(): ControllerInterface => new ActionController(
                $actionFactory(),
                new JsonResponseTransformer(),
            ),
        );
        return $this;
    }

    /**
     * Register a GET route for reading a list of resources.
     *
     * @param Closure(): ActionInterface $actionFactory Closure that creates the action when needed
     */
    public function registerList(Closure $actionFactory): self
    {
        $this->router->get(
            "{$this->resourceName}-list",
            $this->pathSegment,
            fn(): ControllerInterface => new ActionController(
                $actionFactory(),
                new JsonResponseTransformer(),
            ),
        );
        return $this;
    }

    /**
     * Register a POST route for creating a resource.
     *
     * @param Closure(): ActionInterface $actionFactory Closure that creates the action when needed
     */
    public function registerCreate(Closure $actionFactory): self
    {
        $this->router->post(
            "{$this->resourceName}-create",
            $this->pathSegment,
            fn(): ControllerInterface => new ActionController(
                $actionFactory(),
                new JsonResponseTransformer(),
            ),
        );
        return $this;
    }

    /**
     * Register a PUT route for updating a resource.
     *
     * @param Closure(): ActionInterface $actionFactory Closure that creates the action when needed
     */
    public function registerUpdate(Closure $actionFactory): self
    {
        $this->router->put(
            "{$this->resourceName}-update",
            "{$this->pathSegment}{$this->idSegment}",
            fn(): ControllerInterface => new ActionController(
                $actionFactory(),
                new JsonResponseTransformer(),
            ),
        );
        return $this;
    }

    /**
     * Register a DELETE route for deleting a resource.
     *
     * @param Closure(): ActionInterface $actionFactory Closure that creates the action when needed
     */
    public function registerDelete(Closure $actionFactory): self
    {
        $this->router->delete(
            "{$this->resourceName}-delete",
            "{$this->pathSegment}{$this->idSegment}",
            fn(): ControllerInterface => new ActionController(
                $actionFactory(),
                new JsonResponseTransformer(),
            ),
        );
        return $this;
    }

    /**
     * Register a custom route with explicit HTTP method.
     *
     * Use this for routes that don't fit the standard CRUD pattern.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $suffix Route name suffix (e.g., 'reorder' for 'favorite-reorder')
     * @param string $pathSuffix Additional path segment (can be empty string)
     * @param Closure(): ActionInterface $actionFactory Closure that creates the action when needed
     */
    public function registerCustom(
        string $method,
        string $suffix,
        string $pathSuffix,
        Closure $actionFactory,
    ): self {
        $routeName =
            $suffix !== ""
                ? "{$this->resourceName}-{$suffix}"
                : $this->resourceName;

        $path = "{$this->pathSegment}{$pathSuffix}";

        $controller = fn(): ControllerInterface => new ActionController(
            $actionFactory(),
            new JsonResponseTransformer(),
        );

        match (strtoupper($method)) {
            "GET" => $this->router->get($routeName, $path, $controller),
            "POST" => $this->router->post($routeName, $path, $controller),
            "PUT" => $this->router->put($routeName, $path, $controller),
            "DELETE" => $this->router->delete($routeName, $path, $controller),
            "PATCH" => $this->router->patch($routeName, $path, $controller),
            default => throw new \InvalidArgumentException(
                "Unsupported HTTP method: {$method}",
            ),
        };

        return $this;
    }
}
