<?php

use jschreuder\BookmarkBureau\Exception\DashboardNotFoundException;
use Ramsey\Uuid\Rfc4122\UuidV4;

describe('DashboardNotFoundException', function () {

    describe('forId factory method', function () {
        test('creates exception with correct message', function () {
            $dashboardId = UuidV4::uuid4();
            $exception = DashboardNotFoundException::forId($dashboardId);

            expect($exception)->toBeInstanceOf(DashboardNotFoundException::class);
            expect($exception->getMessage())->toBe("Dashboard with ID '{$dashboardId}' not found");
        });

        test('creates exception with 404 status code', function () {
            $dashboardId = UuidV4::uuid4();
            $exception = DashboardNotFoundException::forId($dashboardId);

            expect($exception->getCode())->toBe(404);
        });

        test('exception is throwable', function () {
            $dashboardId = UuidV4::uuid4();
            expect(function () use ($dashboardId) {
                throw DashboardNotFoundException::forId($dashboardId);
            })->toThrow(DashboardNotFoundException::class);
        });

        test('exception can be caught as RuntimeException', function () {
            $dashboardId = UuidV4::uuid4();
            expect(function () use ($dashboardId) {
                throw DashboardNotFoundException::forId($dashboardId);
            })->toThrow(RuntimeException::class);
        });

        test('multiple calls with different IDs create independent exceptions', function () {
            $id1 = UuidV4::uuid4();
            $id2 = UuidV4::uuid4();
            $exception1 = DashboardNotFoundException::forId($id1);
            $exception2 = DashboardNotFoundException::forId($id2);

            expect($exception1->getMessage())->toBe("Dashboard with ID '{$id1}' not found");
            expect($exception2->getMessage())->toBe("Dashboard with ID '{$id2}' not found");
            expect($exception1)->not->toBe($exception2);
        });
    });

});
