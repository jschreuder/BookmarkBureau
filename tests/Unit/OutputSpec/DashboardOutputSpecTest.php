<?php

use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\OutputSpec\DashboardOutputSpec;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;

describe("DashboardOutputSpec", function () {
    describe("initialization", function () {
        test("creates OutputSpec instance", function () {
            $spec = new DashboardOutputSpec();

            expect($spec)->toBeInstanceOf(DashboardOutputSpec::class);
        });

        test("implements OutputSpecInterface", function () {
            $spec = new DashboardOutputSpec();

            expect($spec)->toBeInstanceOf(OutputSpecInterface::class);
        });

        test("is readonly", function () {
            $spec = new DashboardOutputSpec();

            expect($spec)->toBeInstanceOf(DashboardOutputSpec::class);
        });
    });

    describe("supports method", function () {
        test("supports Dashboard objects", function () {
            $spec = new DashboardOutputSpec();
            $dashboard = TestEntityFactory::createDashboard();

            expect($spec->supports($dashboard))->toBeTrue();
        });

        test("does not support Link objects", function () {
            $spec = new DashboardOutputSpec();
            $link = TestEntityFactory::createLink();

            expect($spec->supports($link))->toBeFalse();
        });

        test("does not support string objects", function () {
            $spec = new DashboardOutputSpec();

            expect($spec->supports(new stdClass()))->toBeFalse();
        });
    });

    describe("transform method", function () {
        test("transforms Dashboard to array with all fields", function () {
            $spec = new DashboardOutputSpec();
            $dashboard = TestEntityFactory::createDashboard();

            $result = $spec->transform($dashboard);

            expect($result)->toBeArray();
            expect($result)->toHaveKeys([
                "id",
                "title",
                "description",
                "icon",
                "created_at",
                "updated_at",
            ]);
        });

        test("returns dashboard ID as string", function () {
            $spec = new DashboardOutputSpec();
            $dashboard = TestEntityFactory::createDashboard();

            $result = $spec->transform($dashboard);

            expect($result["id"])->toBeString();
            expect($result["id"])->toBe($dashboard->dashboardId->toString());
        });

        test("returns dashboard title as string", function () {
            $spec = new DashboardOutputSpec();
            $title = new Title("My Dashboard");
            $dashboard = TestEntityFactory::createDashboard(title: $title);

            $result = $spec->transform($dashboard);

            expect($result["title"])->toBeString();
            expect($result["title"])->toBe("My Dashboard");
        });

        test("returns dashboard description as string", function () {
            $spec = new DashboardOutputSpec();
            $dashboard = TestEntityFactory::createDashboard(
                description: "Dashboard Description",
            );

            $result = $spec->transform($dashboard);

            expect($result["description"])->toBeString();
            expect($result["description"])->toBe("Dashboard Description");
        });

        test("returns dashboard icon as string when present", function () {
            $spec = new DashboardOutputSpec();
            $icon = new Icon("dashboard-icon");
            $dashboard = TestEntityFactory::createDashboard(icon: $icon);

            $result = $spec->transform($dashboard);

            expect($result["icon"])->toBeString();
            expect($result["icon"])->toBe("dashboard-icon");
        });

        test("returns dashboard icon as null when not present", function () {
            $spec = new DashboardOutputSpec();
            $dashboard = TestEntityFactory::createDashboard();
            $dashboard->icon = null;

            $result = $spec->transform($dashboard);

            expect($result["icon"])->toBeNull();
        });

        test("returns created_at in ATOM format", function () {
            $spec = new DashboardOutputSpec();
            $createdAt = new DateTimeImmutable(
                "2024-01-15 14:30:00",
                new DateTimeZone("UTC"),
            );
            $dashboard = TestEntityFactory::createDashboard(
                createdAt: $createdAt,
            );

            $result = $spec->transform($dashboard);

            expect($result["created_at"])->toBeString();
            expect($result["created_at"])->toBe(
                $createdAt->format(DateTimeInterface::ATOM),
            );
        });

        test("returns updated_at in ATOM format", function () {
            $spec = new DashboardOutputSpec();
            $updatedAt = new DateTimeImmutable(
                "2024-01-15 15:45:00",
                new DateTimeZone("UTC"),
            );
            $dashboard = TestEntityFactory::createDashboard(
                updatedAt: $updatedAt,
            );

            $result = $spec->transform($dashboard);

            expect($result["updated_at"])->toBeString();
            expect($result["updated_at"])->toBe(
                $updatedAt->format(DateTimeInterface::ATOM),
            );
        });

        test(
            "throws InvalidArgumentException when transforming unsupported object",
            function () {
                $spec = new DashboardOutputSpec();
                $link = TestEntityFactory::createLink();

                expect(fn() => $spec->transform($link))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );

        test(
            "exception message contains class name and unsupported type",
            function () {
                $spec = new DashboardOutputSpec();
                $link = TestEntityFactory::createLink();

                expect(fn() => $spec->transform($link))
                    ->toThrow(InvalidArgumentException::class)
                    ->and(fn() => $spec->transform($link))
                    ->toThrow(function (InvalidArgumentException $e) {
                        return str_contains(
                            $e->getMessage(),
                            DashboardOutputSpec::class,
                        ) && str_contains($e->getMessage(), Link::class);
                    });
            },
        );
    });

    describe("edge cases", function () {
        test("handles dashboard with long description", function () {
            $spec = new DashboardOutputSpec();
            $longDescription = str_repeat(
                "A very long description text. ",
                100,
            );
            $dashboard = TestEntityFactory::createDashboard(
                description: $longDescription,
            );

            $result = $spec->transform($dashboard);

            expect($result["description"])->toBe($longDescription);
        });

        test(
            "handles dashboard with special characters in description",
            function () {
                $spec = new DashboardOutputSpec();
                $description =
                    'Dashboard with "quotes", \'apostrophes\', & ampersand, and unicode: 北京';
                $dashboard = TestEntityFactory::createDashboard(
                    description: $description,
                );

                $result = $spec->transform($dashboard);

                expect($result["description"])->toBe($description);
            },
        );

        test("handles dashboard with empty string description", function () {
            $spec = new DashboardOutputSpec();
            $dashboard = TestEntityFactory::createDashboard(description: "");

            $result = $spec->transform($dashboard);

            expect($result["description"])->toBe("");
        });

        test("handles multiple dashboards independently", function () {
            $spec = new DashboardOutputSpec();
            $dashboard1 = TestEntityFactory::createDashboard();
            $dashboard2 = TestEntityFactory::createDashboard();

            $result1 = $spec->transform($dashboard1);
            $result2 = $spec->transform($dashboard2);

            expect($result1["id"])->not->toBe($result2["id"]);
            expect($result1["id"])->toBe($dashboard1->dashboardId->toString());
            expect($result2["id"])->toBe($dashboard2->dashboardId->toString());
        });
    });

    describe("integration with OutputSpecInterface", function () {
        test("transform method signature matches interface", function () {
            $spec = new DashboardOutputSpec();
            $dashboard = TestEntityFactory::createDashboard();

            $result = $spec->transform($dashboard);

            expect($result)->toBeArray();
        });

        test("can be used polymorphically through interface", function () {
            $spec = new DashboardOutputSpec();
            $interface = $spec;
            $dashboard = TestEntityFactory::createDashboard();

            expect($interface)->toBeInstanceOf(OutputSpecInterface::class);

            $result = $interface->transform($dashboard);

            expect($result)->toBeArray();
        });
    });
});
