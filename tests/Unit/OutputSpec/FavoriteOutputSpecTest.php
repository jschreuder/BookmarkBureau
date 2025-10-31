<?php

use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\OutputSpec\FavoriteOutputSpec;

describe('FavoriteOutputSpec', function () {
    describe('supports method', function () {
        test('returns true for Favorite instances', function () {
            $spec = new FavoriteOutputSpec();
            $favorite = TestEntityFactory::createFavorite();

            expect($spec->supports($favorite))->toBeTrue();
        });

        test('returns false for non-Favorite instances', function () {
            $spec = new FavoriteOutputSpec();

            expect($spec->supports(new stdClass()))->toBeFalse();
            expect($spec->supports(TestEntityFactory::createDashboard()))->toBeFalse();
            expect($spec->supports(TestEntityFactory::createLink()))->toBeFalse();
            expect($spec->supports(TestEntityFactory::createCategory()))->toBeFalse();
        });
    });

    describe('transform method', function () {
        test('transforms Favorite to array with correct keys', function () {
            $spec = new FavoriteOutputSpec();
            $favorite = TestEntityFactory::createFavorite();

            $result = $spec->transform($favorite);

            expect($result)->toHaveKey('dashboard_id');
            expect($result)->toHaveKey('link_id');
            expect($result)->toHaveKey('sort_order');
            expect($result)->toHaveKey('created_at');
        });

        test('includes correct dashboard_id in output', function () {
            $spec = new FavoriteOutputSpec();
            $favorite = TestEntityFactory::createFavorite();

            $result = $spec->transform($favorite);

            expect($result['dashboard_id'])->toBe($favorite->dashboard->dashboardId->toString());
        });

        test('includes correct link_id in output', function () {
            $spec = new FavoriteOutputSpec();
            $favorite = TestEntityFactory::createFavorite();

            $result = $spec->transform($favorite);

            expect($result['link_id'])->toBe($favorite->link->linkId->toString());
        });

        test('includes correct sort_order in output', function () {
            $spec = new FavoriteOutputSpec();
            $favorite = TestEntityFactory::createFavorite(sortOrder: 5);

            $result = $spec->transform($favorite);

            expect($result['sort_order'])->toBe(5);
        });

        test('formats created_at as ISO 8601', function () {
            $spec = new FavoriteOutputSpec();
            $favorite = TestEntityFactory::createFavorite();

            $result = $spec->transform($favorite);

            expect($result['created_at'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
        });

        test('does not include dashboard object in output', function () {
            $spec = new FavoriteOutputSpec();
            $favorite = TestEntityFactory::createFavorite();

            $result = $spec->transform($favorite);

            expect($result)->not->toHaveKey('dashboard');
            expect($result)->not->toHaveKey('link');
        });

        test('transforms multiple favorites correctly', function () {
            $spec = new FavoriteOutputSpec();
            $favorite1 = TestEntityFactory::createFavorite(sortOrder: 1);
            $favorite2 = TestEntityFactory::createFavorite(sortOrder: 2);

            $result1 = $spec->transform($favorite1);
            $result2 = $spec->transform($favorite2);

            expect($result1['sort_order'])->toBe(1);
            expect($result2['sort_order'])->toBe(2);
            expect($result1['link_id'])->not->toBe($result2['link_id']);
        });
    });
});
