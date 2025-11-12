<?php

use jschreuder\BookmarkBureau\Collection\CategoryWithLinks;
use jschreuder\BookmarkBureau\Collection\CategoryWithLinksCollection;
use jschreuder\BookmarkBureau\Collection\DashboardWithCategoriesAndFavorites;
use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Controller\DashboardViewController;
use jschreuder\BookmarkBureau\OutputSpec\CategoryOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\DashboardOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\FullDashboardOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\BookmarkBureau\Response\JsonResponseTransformer;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use Laminas\Diactoros\ServerRequest;
use Ramsey\Uuid\Uuid;

describe("DashboardViewController", function () {
    // Helper function to create the controller with proper dependencies
    $createController = function (
        ?DashboardServiceInterface $dashboardService = null,
    ) {
        $dashboardService =
            $dashboardService ??
            Mockery::mock(DashboardServiceInterface::class);
        $responseTransformer = new JsonResponseTransformer();
        $dashboardOutputSpec = new DashboardOutputSpec();
        $categoryOutputSpec = new CategoryOutputSpec();
        $linkOutputSpec = new LinkOutputSpec(new TagOutputSpec());
        $compositeOutputSpec = new FullDashboardOutputSpec(
            $dashboardOutputSpec,
            $categoryOutputSpec,
            $linkOutputSpec,
        );

        return new DashboardViewController(
            $dashboardService,
            $responseTransformer,
            $compositeOutputSpec,
        );
    };

    describe("initialization", function () use ($createController) {
        test("creates controller with required dependencies", function () use (
            $createController,
        ) {
            $controller = $createController();

            expect($controller)->toBeInstanceOf(DashboardViewController::class);
        });
    });

    describe("filterRequest method", function () use ($createController) {
        test(
            "extracts id from route attributes and sets as parsed body",
            function () use ($createController) {
                $controller = $createController();

                $dashboardId = Uuid::uuid4()->toString();
                $request = new ServerRequest(
                    uri: "http://example.com/dashboard/" . $dashboardId,
                    method: "GET",
                    serverParams: [],
                );
                $request = $request->withAttribute("id", $dashboardId);

                $filtered = $controller->filterRequest($request);

                expect($filtered->getParsedBody())->toBe([
                    "id" => $dashboardId,
                ]);
            },
        );

        test(
            "returns empty parsed body when id is not in route attributes",
            function () use ($createController) {
                $controller = $createController();

                $request = new ServerRequest(
                    uri: "http://example.com/dashboard",
                    method: "GET",
                    serverParams: [],
                );

                $filtered = $controller->filterRequest($request);

                expect($filtered->getParsedBody())->toBe([]);
            },
        );

        test("handles null id attribute", function () use ($createController) {
            $controller = $createController();

            $request = new ServerRequest(
                uri: "http://example.com/dashboard",
                method: "GET",
                serverParams: [],
            );
            $request = $request->withAttribute("id", null);

            $filtered = $controller->filterRequest($request);

            expect($filtered->getParsedBody())->toBe([]);
        });
    });

    describe("validateRequest method", function () use ($createController) {
        test("passes validation with valid UUID", function () use (
            $createController,
        ) {
            $controller = $createController();

            $dashboardId = Uuid::uuid4()->toString();
            $request = new ServerRequest(
                uri: "http://example.com/dashboard/" . $dashboardId,
                method: "GET",
                serverParams: [],
            );
            $request = $request->withParsedBody(["id" => $dashboardId]);

            // Should not throw exception
            $controller->validateRequest($request);
            expect(true)->toBeTrue();
        });

        test(
            "throws InvalidArgumentException when id is missing",
            function () use ($createController) {
                $controller = $createController();

                $request = new ServerRequest(
                    uri: "http://example.com/dashboard",
                    method: "GET",
                    serverParams: [],
                );
                $request = $request->withParsedBody([]);

                expect(fn() => $controller->validateRequest($request))->toThrow(
                    InvalidArgumentException::class,
                    "Dashboard ID is required",
                );
            },
        );

        test(
            "throws InvalidArgumentException when id is not a valid UUID",
            function () use ($createController) {
                $controller = $createController();

                $request = new ServerRequest(
                    uri: "http://example.com/dashboard/invalid-uuid",
                    method: "GET",
                    serverParams: [],
                );
                $request = $request->withParsedBody(["id" => "invalid-uuid"]);

                expect(fn() => $controller->validateRequest($request))->toThrow(
                    InvalidArgumentException::class,
                    "Invalid dashboard ID format",
                );
            },
        );

        test("handles null parsed body gracefully", function () use (
            $createController,
        ) {
            $controller = $createController();

            $request = new ServerRequest(
                uri: "http://example.com/dashboard",
                method: "GET",
                serverParams: [],
            );
            $request = $request->withParsedBody(null);

            expect(fn() => $controller->validateRequest($request))->toThrow(
                InvalidArgumentException::class,
                "Dashboard ID is required",
            );
        });
    });

    describe("execute method", function () use ($createController) {
        test(
            "returns complete dashboard view with categories and favorites",
            function () use ($createController) {
                $dashboardId = Uuid::uuid4();
                $dashboard = TestEntityFactory::createDashboard(
                    id: $dashboardId,
                );
                $category1 = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );
                $category2 = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                );
                $link1 = TestEntityFactory::createLink();
                $link2 = TestEntityFactory::createLink();
                $link3 = TestEntityFactory::createLink();
                $favorite1 = TestEntityFactory::createLink();
                $favorite2 = TestEntityFactory::createLink();

                $categoryWithLinks1 = new CategoryWithLinks(
                    $category1,
                    new LinkCollection($link1, $link2),
                );
                $categoryWithLinks2 = new CategoryWithLinks(
                    $category2,
                    new LinkCollection($link3),
                );

                $dashboardView = new DashboardWithCategoriesAndFavorites(
                    $dashboard,
                    new CategoryWithLinksCollection(
                        $categoryWithLinks1,
                        $categoryWithLinks2,
                    ),
                    new LinkCollection($favorite1, $favorite2),
                );

                $dashboardService = Mockery::mock(
                    DashboardServiceInterface::class,
                );
                $dashboardService
                    ->shouldReceive("getFullDashboard")
                    ->with(
                        Mockery::on(
                            fn($uuid) => $uuid->toString() ===
                                $dashboardId->toString(),
                        ),
                    )
                    ->andReturn($dashboardView);

                $controller = $createController($dashboardService);

                $request = new ServerRequest(
                    uri: "http://example.com/dashboard/" .
                        $dashboardId->toString(),
                    method: "GET",
                    serverParams: [],
                );
                $request = $request->withParsedBody([
                    "id" => $dashboardId->toString(),
                ]);

                $response = $controller->execute($request);

                expect($response->getStatusCode())->toBe(200);
                expect($response->getHeader("Content-Type")[0])->toContain(
                    "application/json",
                );

                $body = json_decode($response->getBody()->getContents(), true);
                expect($body)->toHaveKey("success", true);
                expect($body)->toHaveKey("data");
                expect($body["data"])->toHaveKey("categories");
                expect($body["data"])->toHaveKey("favorites");
                expect($body["data"]["categories"])->toHaveCount(2);
                expect($body["data"]["favorites"])->toHaveCount(2);
            },
        );

        test(
            "returns dashboard with empty categories and favorites",
            function () use ($createController) {
                $dashboardId = Uuid::uuid4();
                $dashboard = TestEntityFactory::createDashboard(
                    id: $dashboardId,
                );

                $dashboardView = new DashboardWithCategoriesAndFavorites(
                    $dashboard,
                    new CategoryWithLinksCollection(),
                    new LinkCollection(),
                );

                $dashboardService = Mockery::mock(
                    DashboardServiceInterface::class,
                );
                $dashboardService
                    ->shouldReceive("getFullDashboard")
                    ->with(
                        Mockery::on(
                            fn($uuid) => $uuid->toString() ===
                                $dashboardId->toString(),
                        ),
                    )
                    ->andReturn($dashboardView);

                $controller = $createController($dashboardService);

                $request = new ServerRequest(
                    uri: "http://example.com/dashboard/" .
                        $dashboardId->toString(),
                    method: "GET",
                    serverParams: [],
                );
                $request = $request->withParsedBody([
                    "id" => $dashboardId->toString(),
                ]);

                $response = $controller->execute($request);

                expect($response->getStatusCode())->toBe(200);

                $body = json_decode($response->getBody()->getContents(), true);
                expect($body["success"])->toBeTrue();
                expect($body["data"]["categories"])->toBe([]);
                expect($body["data"]["favorites"])->toBe([]);
            },
        );

        test("includes links within categories in response", function () use (
            $createController,
        ) {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();

            $categoryWithLinks = new CategoryWithLinks(
                $category,
                new LinkCollection($link1, $link2),
            );

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection($categoryWithLinks),
                new LinkCollection(),
            );

            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardService
                ->shouldReceive("getFullDashboard")
                ->with(
                    Mockery::on(
                        fn($uuid) => $uuid->toString() ===
                            $dashboardId->toString(),
                    ),
                )
                ->andReturn($dashboardView);

            $controller = $createController($dashboardService);

            $request = new ServerRequest(
                uri: "http://example.com/dashboard/" . $dashboardId->toString(),
                method: "GET",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "id" => $dashboardId->toString(),
            ]);

            $response = $controller->execute($request);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["data"]["categories"][0])->toHaveKey("links");
            expect($body["data"]["categories"][0]["links"])->toHaveCount(2);
        });

        test("handles category with no links", function () use (
            $createController,
        ) {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );

            $categoryWithLinks = new CategoryWithLinks(
                $category,
                new LinkCollection(),
            );

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection($categoryWithLinks),
                new LinkCollection(),
            );

            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardService
                ->shouldReceive("getFullDashboard")
                ->with(
                    Mockery::on(
                        fn($uuid) => $uuid->toString() ===
                            $dashboardId->toString(),
                    ),
                )
                ->andReturn($dashboardView);

            $controller = $createController($dashboardService);

            $request = new ServerRequest(
                uri: "http://example.com/dashboard/" . $dashboardId->toString(),
                method: "GET",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "id" => $dashboardId->toString(),
            ]);

            $response = $controller->execute($request);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["data"]["categories"][0]["links"])->toBe([]);
        });

        test("response is JsonResponse instance", function () use (
            $createController,
        ) {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection(),
                new LinkCollection(),
            );

            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardService
                ->shouldReceive("getFullDashboard")
                ->andReturn($dashboardView);

            $controller = $createController($dashboardService);

            $request = new ServerRequest(
                uri: "http://example.com/dashboard/" . $dashboardId->toString(),
                method: "GET",
                serverParams: [],
            );
            $request = $request->withParsedBody([
                "id" => $dashboardId->toString(),
            ]);

            $response = $controller->execute($request);

            expect($response)->toBeInstanceOf(
                Laminas\Diactoros\Response\JsonResponse::class,
            );
        });
    });

    describe("interface implementation", function () use ($createController) {
        test("implements ControllerInterface", function () use (
            $createController,
        ) {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $controller = $createController($dashboardService);

            expect($controller)->toBeInstanceOf(
                jschreuder\Middle\Controller\ControllerInterface::class,
            );
        });

        test("implements RequestFilterInterface", function () use (
            $createController,
        ) {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $controller = $createController($dashboardService);

            expect($controller)->toBeInstanceOf(
                jschreuder\Middle\Controller\RequestFilterInterface::class,
            );
        });

        test("implements RequestValidatorInterface", function () use (
            $createController,
        ) {
            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $controller = $createController($dashboardService);

            expect($controller)->toBeInstanceOf(
                jschreuder\Middle\Controller\RequestValidatorInterface::class,
            );
        });
    });

    describe("integration scenarios", function () use ($createController) {
        test("full request lifecycle for dashboard retrieval", function () use (
            $createController,
        ) {
            $dashboardId = Uuid::uuid4();
            $dashboard = TestEntityFactory::createDashboard(id: $dashboardId);
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link = TestEntityFactory::createLink();
            $favorite = TestEntityFactory::createLink();

            $categoryWithLinks = new CategoryWithLinks(
                $category,
                new LinkCollection($link),
            );

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection($categoryWithLinks),
                new LinkCollection($favorite),
            );

            $dashboardService = Mockery::mock(DashboardServiceInterface::class);
            $dashboardService
                ->shouldReceive("getFullDashboard")
                ->with(
                    Mockery::on(
                        fn($uuid) => $uuid->toString() ===
                            $dashboardId->toString(),
                    ),
                )
                ->andReturn($dashboardView);

            $controller = $createController($dashboardService);

            $request = new ServerRequest(
                uri: "http://example.com/dashboard/" . $dashboardId->toString(),
                method: "GET",
                serverParams: [],
            );
            $request = $request->withAttribute("id", $dashboardId->toString());

            // Filter the request
            $filtered = $controller->filterRequest($request);
            expect($filtered->getParsedBody())->toHaveKey("id");

            // Validate the request
            $controller->validateRequest($filtered);

            // Execute the request
            $response = $controller->execute($filtered);

            expect($response->getStatusCode())->toBe(200);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["success"])->toBeTrue();
            expect($body["data"]["categories"])->toHaveCount(1);
            expect($body["data"]["favorites"])->toHaveCount(1);
            expect($body["data"]["categories"][0]["links"])->toHaveCount(1);
        });

        test(
            "full request lifecycle with invalid UUID throws exception",
            function () use ($createController) {
                $dashboardService = Mockery::mock(
                    DashboardServiceInterface::class,
                );
                $controller = $createController($dashboardService);

                $request = new ServerRequest(
                    uri: "http://example.com/dashboard/not-a-uuid",
                    method: "GET",
                    serverParams: [],
                );
                $request = $request->withAttribute("id", "not-a-uuid");

                $filtered = $controller->filterRequest($request);

                expect(
                    fn() => $controller->validateRequest($filtered),
                )->toThrow(InvalidArgumentException::class);
            },
        );

        test(
            "full request lifecycle with missing ID throws exception",
            function () use ($createController) {
                $dashboardService = Mockery::mock(
                    DashboardServiceInterface::class,
                );
                $controller = $createController($dashboardService);

                $request = new ServerRequest(
                    uri: "http://example.com/dashboard",
                    method: "GET",
                    serverParams: [],
                );

                $filtered = $controller->filterRequest($request);

                expect(
                    fn() => $controller->validateRequest($filtered),
                )->toThrow(
                    InvalidArgumentException::class,
                    "Dashboard ID is required",
                );
            },
        );
    });
});
