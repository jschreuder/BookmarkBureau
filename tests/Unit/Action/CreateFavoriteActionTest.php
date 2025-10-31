<?php

use jschreuder\BookmarkBureau\Action\FavoriteCreateAction;
use jschreuder\BookmarkBureau\InputSpec\FavoriteInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\FavoriteOutputSpec;
use jschreuder\BookmarkBureau\Service\FavoriteServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe('CreateFavoriteAction', function () {
    describe('filter method', function () {
        test('excludes sort_order from required fields', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $action->filter([
                'dashboard_id' => "  {$dashboardId}  ",
                'link_id' => "  {$linkId}  ",
                'sort_order' => 5,
            ]);

            expect($filtered)->toHaveKey('dashboard_id');
            expect($filtered)->toHaveKey('link_id');
            expect($filtered)->not->toHaveKey('sort_order');
        });

        test('trims whitespace from dashboard_id and link_id', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $action->filter([
                'dashboard_id' => "  {$dashboardId}  ",
                'link_id' => "  {$linkId}  ",
            ]);

            expect($filtered['dashboard_id'])->toBe($dashboardId);
            expect($filtered['link_id'])->toBe($linkId);
        });
    });

    describe('validate method', function () {
        test('passes validation with valid dashboard_id and link_id', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $dashboardId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            try {
                $action->validate([
                    'dashboard_id' => $dashboardId,
                    'link_id' => $linkId,
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for invalid dashboard_id UUID', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($action, $linkId) {
                $action->validate([
                    'dashboard_id' => 'not-a-uuid',
                    'link_id' => $linkId,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for invalid link_id UUID', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($action, $dashboardId) {
                $action->validate([
                    'dashboard_id' => $dashboardId,
                    'link_id' => 'not-a-uuid',
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty dashboard_id', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $linkId = Uuid::uuid4()->toString();

            expect(function() use ($action, $linkId) {
                $action->validate([
                    'dashboard_id' => '',
                    'link_id' => $linkId,
                ]);
            })->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty link_id', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $dashboardId = Uuid::uuid4()->toString();

            expect(function() use ($action, $dashboardId) {
                $action->validate([
                    'dashboard_id' => $dashboardId,
                    'link_id' => '',
                ]);
            })->toThrow(ValidationFailedException::class);
        });
    });

    describe('execute method', function () {
        test('executes with valid data and returns formatted favorite', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $favorite = TestEntityFactory::createFavorite();

            $dashboardId = $favorite->dashboard->dashboardId;
            $linkId = $favorite->link->linkId;

            $favoriteService->shouldReceive('addFavorite')
                ->with(
                    Mockery::on(fn($arg) => $arg->toString() === $dashboardId->toString()),
                    Mockery::on(fn($arg) => $arg->toString() === $linkId->toString())
                )
                ->andReturn($favorite);

            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $result = $action->execute([
                'dashboard_id' => $dashboardId->toString(),
                'link_id' => $linkId->toString(),
            ]);

            expect($result)->toHaveKey('dashboard_id');
            expect($result)->toHaveKey('link_id');
            expect($result)->toHaveKey('sort_order');
            expect($result)->toHaveKey('created_at');
        });

        test('calls favoriteService.addFavorite with correct UUID objects', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $favorite = TestEntityFactory::createFavorite();

            $dashboardId = $favorite->dashboard->dashboardId;
            $linkId = $favorite->link->linkId;

            $favoriteService->shouldReceive('addFavorite')
                ->with(
                    Mockery::on(fn($arg) => $arg->toString() === $dashboardId->toString()),
                    Mockery::on(fn($arg) => $arg->toString() === $linkId->toString())
                )
                ->andReturn($favorite);

            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $action->execute([
                'dashboard_id' => $dashboardId->toString(),
                'link_id' => $linkId->toString(),
            ]);

            expect(true)->toBeTrue();
        });

        test('returns created_at in ISO 8601 format', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $favorite = TestEntityFactory::createFavorite();

            $favoriteService->shouldReceive('addFavorite')
                ->andReturn($favorite);

            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $result = $action->execute([
                'dashboard_id' => $favorite->dashboard->dashboardId->toString(),
                'link_id' => $favorite->link->linkId->toString(),
            ]);

            expect($result['created_at'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter, validate, and execute', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $favorite = TestEntityFactory::createFavorite();

            $favoriteService->shouldReceive('addFavorite')
                ->andReturn($favorite);

            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $dashboardId = $favorite->dashboard->dashboardId->toString();
            $linkId = $favorite->link->linkId->toString();

            $rawData = [
                'dashboard_id' => "  {$dashboardId}  ",
                'link_id' => "  {$linkId}  ",
                'sort_order' => 5,
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toHaveKey('dashboard_id');
                expect($result)->toHaveKey('link_id');
                expect($result)->toHaveKey('sort_order');
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('sort_order from input is ignored in execution', function () {
            $favoriteService = Mockery::mock(FavoriteServiceInterface::class);
            $favorite = TestEntityFactory::createFavorite(sortOrder: 999);

            $favoriteService->shouldReceive('addFavorite')
                ->andReturn($favorite);

            $inputSpec = new FavoriteInputSpec();
            $outputSpec = new FavoriteOutputSpec();
            $action = new FavoriteCreateAction($favoriteService, $inputSpec, $outputSpec);

            $dashboardId = $favorite->dashboard->dashboardId->toString();
            $linkId = $favorite->link->linkId->toString();

            $rawData = [
                'dashboard_id' => $dashboardId,
                'link_id' => $linkId,
                'sort_order' => 5,
            ];

            $filtered = $action->filter($rawData);
            expect($filtered)->not->toHaveKey('sort_order');

            $action->validate($filtered);
            $result = $action->execute($filtered);

            expect($result['sort_order'])->toBe(999);
        });
    });
});
