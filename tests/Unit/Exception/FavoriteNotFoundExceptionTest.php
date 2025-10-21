<?php

use jschreuder\BookmarkBureau\Exception\FavoriteNotFoundException;
use Ramsey\Uuid\Rfc4122\UuidV4;

describe('FavoriteNotFoundException', function () {

    describe('forDashboardAndLink factory method', function () {
        test('creates exception with correct message', function () {
            $dashboardId = UuidV4::uuid4();
            $linkId = UuidV4::uuid4();
            $exception = FavoriteNotFoundException::forDashboardAndLink($dashboardId, $linkId);

            expect($exception)->toBeInstanceOf(FavoriteNotFoundException::class);
            expect($exception->getMessage())->toBe("Favorite not found for dashboard '{$dashboardId}' and link '{$linkId}'");
        });

        test('creates exception with 404 status code', function () {
            $dashboardId = UuidV4::uuid4();
            $linkId = UuidV4::uuid4();
            $exception = FavoriteNotFoundException::forDashboardAndLink($dashboardId, $linkId);

            expect($exception->getCode())->toBe(404);
        });

        test('exception is throwable', function () {
            $dashboardId = UuidV4::uuid4();
            $linkId = UuidV4::uuid4();
            expect(function () use ($dashboardId, $linkId) {
                throw FavoriteNotFoundException::forDashboardAndLink($dashboardId, $linkId);
            })->toThrow(FavoriteNotFoundException::class);
        });

        test('exception can be caught as RuntimeException', function () {
            $dashboardId = UuidV4::uuid4();
            $linkId = UuidV4::uuid4();
            expect(function () use ($dashboardId, $linkId) {
                throw FavoriteNotFoundException::forDashboardAndLink($dashboardId, $linkId);
            })->toThrow(RuntimeException::class);
        });

        test('multiple calls with different IDs create independent exceptions', function () {
            $dashboardId1 = UuidV4::uuid4();
            $linkId1 = UuidV4::uuid4();
            $dashboardId2 = UuidV4::uuid4();
            $linkId2 = UuidV4::uuid4();
            $exception1 = FavoriteNotFoundException::forDashboardAndLink($dashboardId1, $linkId1);
            $exception2 = FavoriteNotFoundException::forDashboardAndLink($dashboardId2, $linkId2);

            expect($exception1->getMessage())->toBe("Favorite not found for dashboard '{$dashboardId1}' and link '{$linkId1}'");
            expect($exception2->getMessage())->toBe("Favorite not found for dashboard '{$dashboardId2}' and link '{$linkId2}'");
            expect($exception1)->not->toBe($exception2);
        });
    });

});
