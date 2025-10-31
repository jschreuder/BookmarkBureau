<?php declare(strict_types = 1);

namespace jschreuder\BookmarkBureau\Util;

use jschreuder\BookmarkBureau\Action\ActionInterface;
use jschreuder\BookmarkBureau\Controller\ActionController;
use jschreuder\BookmarkBureau\Response\JsonResponseTransformer;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouterInterface;

/**
 * Helper for registering groups of CRUD routes with reduced verbosity.
 *
 * Maintains explicit, type-safe code by requiring all dependencies to be
 * passed directly - no magic, no array configuration, no hidden behavior.
 */
final readonly class ResourceRouteBuilder
{
    private const string ID_SEGMENT = '/:id';

    public function __construct(
        private RouterInterface $router,
        private string $resourceName,
        private string $pathSegment
    ) {
    }

    /**
     * Register a GET route for reading a single resource.
     *
     * @param ActionInterface $action The action to execute (explicitly constructed)
     */
    public function registerRead(ActionInterface $action): self
    {
        $this->router->get(
            $this->resourceName . '-read',
            $this->pathSegment . self::ID_SEGMENT,
            fn(): ControllerInterface => new ActionController($action, new JsonResponseTransformer())
        );
        return $this;
    }

    /**
     * Register a POST route for creating a resource.
     *
     * @param ActionInterface $action The action to execute (explicitly constructed)
     */
    public function registerCreate(ActionInterface $action): self
    {
        $this->router->post(
            $this->resourceName . '-create',
            $this->pathSegment,
            fn(): ControllerInterface => new ActionController($action, new JsonResponseTransformer())
        );
        return $this;
    }

    /**
     * Register a PUT route for updating a resource.
     *
     * @param ActionInterface $action The action to execute (explicitly constructed)
     */
    public function registerUpdate(ActionInterface $action): self
    {
        $this->router->put(
            $this->resourceName . '-update',
            $this->pathSegment . self::ID_SEGMENT,
            fn(): ControllerInterface => new ActionController($action, new JsonResponseTransformer())
        );
        return $this;
    }

    /**
     * Register a DELETE route for deleting a resource.
     *
     * @param ActionInterface $action The action to execute (explicitly constructed)
     */
    public function registerDelete(ActionInterface $action): self
    {
        $this->router->delete(
            $this->resourceName . '-delete',
            $this->pathSegment . self::ID_SEGMENT,
            fn(): ControllerInterface => new ActionController($action, new JsonResponseTransformer())
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
     * @param ActionInterface $action The action to execute (explicitly constructed)
     */
    public function registerCustom(
        string $method,
        string $suffix,
        string $pathSuffix,
        ActionInterface $action
    ): self {
        $routeName = $suffix !== ''
            ? $this->resourceName . '-' . $suffix
            : $this->resourceName;

        $path = $this->pathSegment . $pathSuffix;

        $controller = fn(): ControllerInterface => new ActionController(
            $action,
            new JsonResponseTransformer()
        );

        match (strtoupper($method)) {
            'GET' => $this->router->get($routeName, $path, $controller),
            'POST' => $this->router->post($routeName, $path, $controller),
            'PUT' => $this->router->put($routeName, $path, $controller),
            'DELETE' => $this->router->delete($routeName, $path, $controller),
            'PATCH' => $this->router->patch($routeName, $path, $controller),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
        };

        return $this;
    }
}
