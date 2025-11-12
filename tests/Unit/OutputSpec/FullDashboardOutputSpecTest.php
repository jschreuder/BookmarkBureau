<?php

use jschreuder\BookmarkBureau\Collection\CategoryWithLinks;
use jschreuder\BookmarkBureau\Collection\CategoryWithLinksCollection;
use jschreuder\BookmarkBureau\Collection\DashboardWithCategoriesAndFavorites;
use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\OutputSpec\CategoryOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\DashboardOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\FullDashboardOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;

describe("FullDashboardOutputSpec", function () {
    // Helper to create a FullDashboardOutputSpec with all dependencies
    $createSpec = function () {
        return new FullDashboardOutputSpec(
            new DashboardOutputSpec(),
            new CategoryOutputSpec(),
            new LinkOutputSpec(new TagOutputSpec()),
        );
    };

    describe("initialization", function () use ($createSpec) {
        test("creates OutputSpec instance", function () use ($createSpec) {
            $spec = $createSpec();

            expect($spec)->toBeInstanceOf(FullDashboardOutputSpec::class);
        });

        test("implements OutputSpecInterface", function () use ($createSpec) {
            $spec = $createSpec();

            expect($spec)->toBeInstanceOf(
                \jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface::class,
            );
        });

        test("is readonly", function () use ($createSpec) {
            $spec = $createSpec();

            expect($spec)->toBeInstanceOf(FullDashboardOutputSpec::class);
        });

        test("requires DashboardOutputSpec dependency", function () {
            $spec = new FullDashboardOutputSpec(
                new DashboardOutputSpec(),
                new CategoryOutputSpec(),
                new LinkOutputSpec(new TagOutputSpec()),
            );

            expect($spec)->toBeInstanceOf(FullDashboardOutputSpec::class);
        });

        test("requires CategoryOutputSpec dependency", function () {
            $spec = new FullDashboardOutputSpec(
                new DashboardOutputSpec(),
                new CategoryOutputSpec(),
                new LinkOutputSpec(new TagOutputSpec()),
            );

            expect($spec)->toBeInstanceOf(FullDashboardOutputSpec::class);
        });

        test("requires LinkOutputSpec dependency", function () {
            $spec = new FullDashboardOutputSpec(
                new DashboardOutputSpec(),
                new CategoryOutputSpec(),
                new LinkOutputSpec(new TagOutputSpec()),
            );

            expect($spec)->toBeInstanceOf(FullDashboardOutputSpec::class);
        });
    });

    describe("supports method", function () use ($createSpec) {
        test(
            "supports DashboardWithCategoriesAndFavorites objects",
            function () use ($createSpec) {
                $spec = $createSpec();
                $dashboard = TestEntityFactory::createDashboard();
                $dashboardView = new DashboardWithCategoriesAndFavorites(
                    $dashboard,
                    new CategoryWithLinksCollection(),
                    new LinkCollection(),
                );

                expect($spec->supports($dashboardView))->toBeTrue();
            },
        );

        test("does not support Dashboard objects", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();

            expect($spec->supports($dashboard))->toBeFalse();
        });

        test("does not support Link objects", function () use ($createSpec) {
            $spec = $createSpec();
            $link = TestEntityFactory::createLink();

            expect($spec->supports($link))->toBeFalse();
        });

        test("does not support stdClass objects", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();

            expect($spec->supports(new stdClass()))->toBeFalse();
        });
    });

    describe("transform method", function () use ($createSpec) {
        test(
            "transforms DashboardWithCategoriesAndFavorites to array with all fields",
            function () use ($createSpec) {
                $spec = $createSpec();
                $dashboard = TestEntityFactory::createDashboard();
                $dashboardView = new DashboardWithCategoriesAndFavorites(
                    $dashboard,
                    new CategoryWithLinksCollection(),
                    new LinkCollection(),
                );

                $result = $spec->transform($dashboardView);

                expect($result)->toBeArray();
                expect($result)->toHaveKeys([
                    "id",
                    "title",
                    "description",
                    "icon",
                    "created_at",
                    "updated_at",
                    "categories",
                    "favorites",
                ]);
            },
        );

        test("includes dashboard fields in result", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard(
                title: new Title("My Dashboard"),
            );
            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection(),
                new LinkCollection(),
            );

            $result = $spec->transform($dashboardView);

            expect($result["id"])->toBe($dashboard->dashboardId->toString());
            expect($result["title"])->toBe("My Dashboard");
            expect($result["description"])->toBe($dashboard->description);
        });

        test("transforms empty categories and favorites", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();
            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection(),
                new LinkCollection(),
            );

            $result = $spec->transform($dashboardView);

            expect($result["categories"])->toBeArray();
            expect($result["categories"])->toHaveCount(0);
            expect($result["favorites"])->toBeArray();
            expect($result["favorites"])->toHaveCount(0);
        });

        test("transforms single category with no links", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                title: new Title("Category 1"),
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

            $result = $spec->transform($dashboardView);

            expect($result["categories"])->toHaveCount(1);
            expect($result["categories"][0]["title"])->toBe("Category 1");
            expect($result["categories"][0]["links"])->toBeArray();
            expect($result["categories"][0]["links"])->toHaveCount(0);
        });

        test("transforms single category with multiple links", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link1 = TestEntityFactory::createLink(title: new Title("Link 1"));
            $link2 = TestEntityFactory::createLink(title: new Title("Link 2"));
            $link3 = TestEntityFactory::createLink(title: new Title("Link 3"));

            $categoryWithLinks = new CategoryWithLinks(
                $category,
                new LinkCollection($link1, $link2, $link3),
            );

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection($categoryWithLinks),
                new LinkCollection(),
            );

            $result = $spec->transform($dashboardView);

            expect($result["categories"])->toHaveCount(1);
            expect($result["categories"][0]["links"])->toHaveCount(3);
            expect($result["categories"][0]["links"][0]["title"])->toBe(
                "Link 1",
            );
            expect($result["categories"][0]["links"][1]["title"])->toBe(
                "Link 2",
            );
            expect($result["categories"][0]["links"][2]["title"])->toBe(
                "Link 3",
            );
        });

        test("transforms multiple categories with links", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();

            $category1 = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                title: new Title("Category 1"),
            );
            $link1 = TestEntityFactory::createLink(title: new Title("Link 1"));
            $link2 = TestEntityFactory::createLink(title: new Title("Link 2"));

            $category2 = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                title: new Title("Category 2"),
            );
            $link3 = TestEntityFactory::createLink(title: new Title("Link 3"));

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
                new LinkCollection(),
            );

            $result = $spec->transform($dashboardView);

            expect($result["categories"])->toHaveCount(2);
            expect($result["categories"][0]["title"])->toBe("Category 1");
            expect($result["categories"][0]["links"])->toHaveCount(2);
            expect($result["categories"][1]["title"])->toBe("Category 2");
            expect($result["categories"][1]["links"])->toHaveCount(1);
        });

        test("transforms favorites without categories", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();
            $favorite1 = TestEntityFactory::createLink(
                title: new Title("Favorite 1"),
            );
            $favorite2 = TestEntityFactory::createLink(
                title: new Title("Favorite 2"),
            );

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection(),
                new LinkCollection($favorite1, $favorite2),
            );

            $result = $spec->transform($dashboardView);

            expect($result["categories"])->toHaveCount(0);
            expect($result["favorites"])->toHaveCount(2);
            expect($result["favorites"][0]["title"])->toBe("Favorite 1");
            expect($result["favorites"][1]["title"])->toBe("Favorite 2");
        });

        test(
            "transforms complete dashboard with categories and favorites",
            function () use ($createSpec) {
                $spec = $createSpec();
                $dashboard = TestEntityFactory::createDashboard(
                    title: new Title("Complete Dashboard"),
                );

                $category1 = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                    title: new Title("Work Links"),
                    color: new HexColor("#FF5733"),
                );
                $categoryLink1 = TestEntityFactory::createLink(
                    title: new Title("GitHub"),
                );
                $categoryLink2 = TestEntityFactory::createLink(
                    title: new Title("GitLab"),
                );

                $category2 = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                    title: new Title("News"),
                    color: new HexColor("#33FF57"),
                );
                $categoryLink3 = TestEntityFactory::createLink(
                    title: new Title("HN"),
                );

                $favorite1 = TestEntityFactory::createLink(
                    title: new Title("Gmail"),
                );
                $favorite2 = TestEntityFactory::createLink(
                    title: new Title("Calendar"),
                );

                $categoryWithLinks1 = new CategoryWithLinks(
                    $category1,
                    new LinkCollection($categoryLink1, $categoryLink2),
                );
                $categoryWithLinks2 = new CategoryWithLinks(
                    $category2,
                    new LinkCollection($categoryLink3),
                );

                $dashboardView = new DashboardWithCategoriesAndFavorites(
                    $dashboard,
                    new CategoryWithLinksCollection(
                        $categoryWithLinks1,
                        $categoryWithLinks2,
                    ),
                    new LinkCollection($favorite1, $favorite2),
                );

                $result = $spec->transform($dashboardView);

                // Verify dashboard fields
                expect($result["title"])->toBe("Complete Dashboard");

                // Verify categories structure
                expect($result["categories"])->toHaveCount(2);
                expect($result["categories"][0]["title"])->toBe("Work Links");
                expect($result["categories"][0]["color"])->toBe("#FF5733");
                expect($result["categories"][0]["links"])->toHaveCount(2);
                expect($result["categories"][0]["links"][0]["title"])->toBe(
                    "GitHub",
                );
                expect($result["categories"][0]["links"][1]["title"])->toBe(
                    "GitLab",
                );

                expect($result["categories"][1]["title"])->toBe("News");
                expect($result["categories"][1]["color"])->toBe("#33FF57");
                expect($result["categories"][1]["links"])->toHaveCount(1);
                expect($result["categories"][1]["links"][0]["title"])->toBe(
                    "HN",
                );

                // Verify favorites
                expect($result["favorites"])->toHaveCount(2);
                expect($result["favorites"][0]["title"])->toBe("Gmail");
                expect($result["favorites"][1]["title"])->toBe("Calendar");
            },
        );

        test("preserves link properties in categories", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $link = TestEntityFactory::createLink(
                url: new \jschreuder\BookmarkBureau\Entity\Value\Url(
                    "https://example.com",
                ),
                title: new Title("Example"),
                description: "Example description",
            );

            $categoryWithLinks = new CategoryWithLinks(
                $category,
                new LinkCollection($link),
            );

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection($categoryWithLinks),
                new LinkCollection(),
            );

            $result = $spec->transform($dashboardView);

            expect($result["categories"][0]["links"][0]["id"])->toBe(
                $link->linkId->toString(),
            );
            expect($result["categories"][0]["links"][0]["url"])->toBe(
                "https://example.com",
            );
            expect($result["categories"][0]["links"][0]["title"])->toBe(
                "Example",
            );
            expect($result["categories"][0]["links"][0]["description"])->toBe(
                "Example description",
            );
            expect($result["categories"][0]["links"][0])->toHaveKeys([
                "id",
                "url",
                "title",
                "description",
                "icon",
                "created_at",
                "updated_at",
            ]);
        });

        test("preserves link properties in favorites", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();
            $favorite = TestEntityFactory::createLink(
                url: new \jschreuder\BookmarkBureau\Entity\Value\Url(
                    "https://favorite.com",
                ),
                title: new Title("Favorite Link"),
                description: "Favorite description",
            );

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection(),
                new LinkCollection($favorite),
            );

            $result = $spec->transform($dashboardView);

            expect($result["favorites"][0]["id"])->toBe(
                $favorite->linkId->toString(),
            );
            expect($result["favorites"][0]["url"])->toBe(
                "https://favorite.com",
            );
            expect($result["favorites"][0]["title"])->toBe("Favorite Link");
            expect($result["favorites"][0]["description"])->toBe(
                "Favorite description",
            );
            expect($result["favorites"][0])->toHaveKeys([
                "id",
                "url",
                "title",
                "description",
                "icon",
                "created_at",
                "updated_at",
            ]);
        });

        test("preserves category properties", function () use ($createSpec) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
                title: new Title("Test Category"),
                color: new HexColor("#123456"),
                sortOrder: 42,
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

            $result = $spec->transform($dashboardView);

            expect($result["categories"][0]["id"])->toBe(
                $category->categoryId->toString(),
            );
            expect($result["categories"][0]["dashboard_id"])->toBe(
                $dashboard->dashboardId->toString(),
            );
            expect($result["categories"][0]["title"])->toBe("Test Category");
            expect($result["categories"][0]["color"])->toBe("#123456");
            expect($result["categories"][0]["sort_order"])->toBe(42);
            expect($result["categories"][0])->toHaveKeys([
                "id",
                "dashboard_id",
                "title",
                "color",
                "sort_order",
                "created_at",
                "updated_at",
                "links",
            ]);
        });

        test(
            "throws InvalidArgumentException when transforming unsupported object",
            function () use ($createSpec) {
                $spec = $createSpec();
                $dashboard = TestEntityFactory::createDashboard();

                expect(fn() => $spec->transform($dashboard))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );

        test(
            "exception message contains class name and unsupported type",
            function () use ($createSpec) {
                $spec = $createSpec();
                $dashboard = TestEntityFactory::createDashboard();

                expect(fn() => $spec->transform($dashboard))
                    ->toThrow(InvalidArgumentException::class)
                    ->and(fn() => $spec->transform($dashboard))
                    ->toThrow(function (InvalidArgumentException $e) {
                        return str_contains(
                            $e->getMessage(),
                            FullDashboardOutputSpec::class,
                        ) && str_contains($e->getMessage(), Dashboard::class);
                    });
            },
        );
    });

    describe("edge cases", function () use ($createSpec) {
        test("handles large number of categories", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();

            $categoriesWithLinks = [];
            for ($i = 0; $i < 50; $i++) {
                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                    title: new Title("Category $i"),
                );
                $categoriesWithLinks[] = new CategoryWithLinks(
                    $category,
                    new LinkCollection(),
                );
            }

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection(...$categoriesWithLinks),
                new LinkCollection(),
            );

            $result = $spec->transform($dashboardView);

            expect($result["categories"])->toHaveCount(50);
            expect($result["categories"][0]["title"])->toBe("Category 0");
            expect($result["categories"][49]["title"])->toBe("Category 49");
        });

        test("handles large number of links in a category", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();
            $category = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );

            $links = [];
            for ($i = 0; $i < 100; $i++) {
                $links[] = TestEntityFactory::createLink(
                    title: new Title("Link $i"),
                );
            }

            $categoryWithLinks = new CategoryWithLinks(
                $category,
                new LinkCollection(...$links),
            );

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection($categoryWithLinks),
                new LinkCollection(),
            );

            $result = $spec->transform($dashboardView);

            expect($result["categories"][0]["links"])->toHaveCount(100);
            expect($result["categories"][0]["links"][0]["title"])->toBe(
                "Link 0",
            );
            expect($result["categories"][0]["links"][99]["title"])->toBe(
                "Link 99",
            );
        });

        test("handles large number of favorites", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();

            $favorites = [];
            for ($i = 0; $i < 50; $i++) {
                $favorites[] = TestEntityFactory::createLink(
                    title: new Title("Favorite $i"),
                );
            }

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection(),
                new LinkCollection(...$favorites),
            );

            $result = $spec->transform($dashboardView);

            expect($result["favorites"])->toHaveCount(50);
            expect($result["favorites"][0]["title"])->toBe("Favorite 0");
            expect($result["favorites"][49]["title"])->toBe("Favorite 49");
        });

        test("handles categories with mixed link counts", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();

            $category1 = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $category2 = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );
            $category3 = TestEntityFactory::createCategory(
                dashboard: $dashboard,
            );

            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $link3 = TestEntityFactory::createLink();

            $categoryWithLinks1 = new CategoryWithLinks(
                $category1,
                new LinkCollection(),
            );
            $categoryWithLinks2 = new CategoryWithLinks(
                $category2,
                new LinkCollection($link1, $link2, $link3),
            );
            $categoryWithLinks3 = new CategoryWithLinks(
                $category3,
                new LinkCollection($link1),
            );

            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection(
                    $categoryWithLinks1,
                    $categoryWithLinks2,
                    $categoryWithLinks3,
                ),
                new LinkCollection(),
            );

            $result = $spec->transform($dashboardView);

            expect($result["categories"])->toHaveCount(3);
            expect($result["categories"][0]["links"])->toHaveCount(0);
            expect($result["categories"][1]["links"])->toHaveCount(3);
            expect($result["categories"][2]["links"])->toHaveCount(1);
        });

        test(
            "handles complex nested structure with all optional fields",
            function () use ($createSpec) {
                $spec = $createSpec();
                $dashboard = TestEntityFactory::createDashboard(
                    title: new Title("Complex Dashboard"),
                    description: "A dashboard with all optional fields set",
                    icon: new \jschreuder\BookmarkBureau\Entity\Value\Icon(
                        "dashboard-icon",
                    ),
                );

                $category = TestEntityFactory::createCategory(
                    dashboard: $dashboard,
                    title: new Title("Complex Category"),
                    color: new HexColor("#ABCDEF"),
                );

                $link = TestEntityFactory::createLink(
                    url: new \jschreuder\BookmarkBureau\Entity\Value\Url(
                        "https://complex.example.com",
                    ),
                    title: new Title("Complex Link"),
                    description: "A link with all fields",
                    icon: new \jschreuder\BookmarkBureau\Entity\Value\Icon(
                        "link-icon",
                    ),
                );

                $favorite = TestEntityFactory::createLink(
                    url: new \jschreuder\BookmarkBureau\Entity\Value\Url(
                        "https://favorite.example.com",
                    ),
                    title: new Title("Complex Favorite"),
                    description: "A favorite with all fields",
                    icon: new \jschreuder\BookmarkBureau\Entity\Value\Icon(
                        "favorite-icon",
                    ),
                );

                $categoryWithLinks = new CategoryWithLinks(
                    $category,
                    new LinkCollection($link),
                );

                $dashboardView = new DashboardWithCategoriesAndFavorites(
                    $dashboard,
                    new CategoryWithLinksCollection($categoryWithLinks),
                    new LinkCollection($favorite),
                );

                $result = $spec->transform($dashboardView);

                // Verify all optional fields are preserved
                expect($result["icon"])->toBe("dashboard-icon");
                expect($result["categories"][0]["color"])->toBe("#ABCDEF");
                expect($result["categories"][0]["links"][0]["icon"])->toBe(
                    "link-icon",
                );
                expect($result["favorites"][0]["icon"])->toBe("favorite-icon");
            },
        );
    });

    describe("integration with OutputSpecInterface", function () use (
        $createSpec,
    ) {
        test("transform method signature matches interface", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();
            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection(),
                new LinkCollection(),
            );

            $result = $spec->transform($dashboardView);

            expect($result)->toBeArray();
        });

        test("can be used polymorphically through interface", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $interface = $spec;
            $dashboard = TestEntityFactory::createDashboard();
            $dashboardView = new DashboardWithCategoriesAndFavorites(
                $dashboard,
                new CategoryWithLinksCollection(),
                new LinkCollection(),
            );

            expect($interface)->toBeInstanceOf(
                \jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface::class,
            );

            $result = $interface->transform($dashboardView);

            expect($result)->toBeArray();
        });

        test("composes other OutputSpecs correctly", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $dashboard = TestEntityFactory::createDashboard();
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

            $result = $spec->transform($dashboardView);

            // Verify the composite structure matches what individual OutputSpecs would produce
            $dashboardOutputSpec = new DashboardOutputSpec();
            $expectedDashboard = $dashboardOutputSpec->transform($dashboard);

            expect($result["id"])->toBe($expectedDashboard["id"]);
            expect($result["title"])->toBe($expectedDashboard["title"]);
            expect($result["description"])->toBe(
                $expectedDashboard["description"],
            );

            $categoryOutputSpec = new CategoryOutputSpec();
            $expectedCategory = $categoryOutputSpec->transform($category);

            expect($result["categories"][0]["id"])->toBe(
                $expectedCategory["id"],
            );
            expect($result["categories"][0]["title"])->toBe(
                $expectedCategory["title"],
            );

            $linkOutputSpec = new LinkOutputSpec(new TagOutputSpec());
            $expectedLink = $linkOutputSpec->transform($link);

            expect($result["categories"][0]["links"][0]["id"])->toBe(
                $expectedLink["id"],
            );
            expect($result["categories"][0]["links"][0]["url"])->toBe(
                $expectedLink["url"],
            );

            $expectedFavorite = $linkOutputSpec->transform($favorite);
            expect($result["favorites"][0]["id"])->toBe(
                $expectedFavorite["id"],
            );
            expect($result["favorites"][0]["url"])->toBe(
                $expectedFavorite["url"],
            );
        });
    });
});
