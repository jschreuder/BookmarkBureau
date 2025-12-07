<?php

use jschreuder\BookmarkBureau\Action\FavoriteDeleteAction;
use jschreuder\BookmarkBureau\InputSpec\FavoriteInputSpec;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe("DeleteFavoriteAction", function () {
    describe("getAttributeKeysForData method", function () {
        test(
            "returns both dashboard_id and link_id for delete relation action",
            function () {
                $favoriteService = Mockery::mock(
                    FavoriteServiceInterface::class,
                );
                $inputSpec = new FavoriteInputSpec();
                $action = new FavoriteDeleteAction(
                    $favoriteService,
                    $inputSpec,
                );

                expect($action->getAttributeKeysForData())->toBe([
                    "dashboard_id",
                    "link_id",
                ]);
            },
        );
    });

    describe("filter method", function () {
        test("filters dashboard_id and link_id fields", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $action = new FavoriteDeleteAction($favoriteService, $inputSpec);

            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $action->filter([
                "dashboard_id" => "  {$dashboardId}  ",
                "link_id" => "  {$linkId}  ",
                "sort_order" => 5,
            ]);

            expect($filtered)->toHaveKey("dashboard_id");
            expect($filtered)->toHaveKey("link_id");
            expect($filtered)->not->toHaveKey("sort_order");
        });

        test("trims whitespace from ids", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $action = new FavoriteDeleteAction($favoriteService, $inputSpec);

            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $action->filter([
                "dashboard_id" => "  {$dashboardId}  ",
                "link_id" => "  {$linkId}  ",
            ]);

            expect($filtered["dashboard_id"])->toBe($dashboardId);
            expect($filtered["link_id"])->toBe($linkId);
        });
    });

    describe("validate method", function () {
        test("passes validation with valid ids", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $action = new FavoriteDeleteAction($favoriteService, $inputSpec);

            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            try {
                $action->validate([
                    "dashboard_id" => $dashboardId,
                    "link_id" => $linkId,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for invalid dashboard_id", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $action = new FavoriteDeleteAction($favoriteService, $inputSpec);

            $linkId = Uuid::uuid4()->toString();

            expect(function () use ($action, $linkId) {
                $action->validate([
                    "dashboard_id" => "invalid",
                    "link_id" => $linkId,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for invalid link_id", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $action = new FavoriteDeleteAction($favoriteService, $inputSpec);

            $dashboardId = Uuid::uuid4()->toString();

            expect(function () use ($action, $dashboardId) {
                $action->validate([
                    "dashboard_id" => $dashboardId,
                    "link_id" => "invalid",
                ]);
            })->toThrow(ValidationFailedException::class);
        });
    });

    describe("execute method", function () {
        test(
            "calls favoriteService.removeFavorite with correct UUID objects",
            function () {
                $favoriteService = Mockery::mock(
                    FavoriteServiceInterface::class,
                );
                $favorite = TestEntityFactory::createFavorite();

                $dashboardId = $favorite->dashboard->dashboardId;
                $linkId = $favorite->link->linkId;

                $favoriteService
                    ->shouldReceive("removeFavorite")
                    ->with(
                        Mockery::on(
                            fn($arg) => $arg->toString() ===
                                $dashboardId->toString(),
                        ),
                        Mockery::on(
                            fn($arg) => $arg->toString() ===
                                $linkId->toString(),
                        ),
                    )
                    ->andReturnNull();

                $inputSpec = new FavoriteInputSpec();
                $action = new FavoriteDeleteAction(
                    $favoriteService,
                    $inputSpec,
                );

                $action->execute([
                    "dashboard_id" => $dashboardId->toString(),
                    "link_id" => $linkId->toString(),
                ]);

                expect(true)->toBeTrue();
            },
        );

        test("returns empty array", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $favorite = TestEntityFactory::createFavorite();

            $favoriteService->shouldReceive("removeFavorite")->andReturnNull();

            $inputSpec = new FavoriteInputSpec();
            $action = new FavoriteDeleteAction($favoriteService, $inputSpec);

            $result = $action->execute([
                "dashboard_id" => $favorite->dashboard->dashboardId->toString(),
                "link_id" => $favorite->link->linkId->toString(),
            ]);

            expect($result)->toBe([]);
        });
    });

    describe("integration scenarios", function () {
        test("full workflow: filter, validate, and execute", function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $favorite = TestEntityFactory::createFavorite();

            $favoriteService->shouldReceive("removeFavorite")->andReturnNull();

            $inputSpec = new FavoriteInputSpec();
            $action = new FavoriteDeleteAction($favoriteService, $inputSpec);

            $dashboardId = $favorite->dashboard->dashboardId->toString();
            $linkId = $favorite->link->linkId->toString();

            $rawData = [
                "dashboard_id" => "  {$dashboardId}  ",
                "link_id" => "  {$linkId}  ",
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBe([]);
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });
});
