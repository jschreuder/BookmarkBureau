<?php

use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use Ramsey\Uuid\Uuid;

describe('LinkNotFoundException', function () {

    describe('forId factory method', function () {
        test('creates exception with correct message', function () {
            $linkId = Uuid::uuid4();
            $exception = LinkNotFoundException::forId($linkId);

            expect($exception)->toBeInstanceOf(LinkNotFoundException::class);
            expect($exception->getMessage())->toBe("Link with ID '{$linkId}' not found");
        });

        test('creates exception with 404 status code', function () {
            $linkId = Uuid::uuid4();
            $exception = LinkNotFoundException::forId($linkId);

            expect($exception->getCode())->toBe(404);
        });

        test('exception is throwable', function () {
            $linkId = Uuid::uuid4();
            expect(function () use ($linkId) {
                throw LinkNotFoundException::forId($linkId);
            })->toThrow(LinkNotFoundException::class);
        });

        test('exception can be caught as RuntimeException', function () {
            $linkId = Uuid::uuid4();
            expect(function () use ($linkId) {
                throw LinkNotFoundException::forId($linkId);
            })->toThrow(RuntimeException::class);
        });

        test('multiple calls with different IDs create independent exceptions', function () {
            $id1 = Uuid::uuid4();
            $id2 = Uuid::uuid4();
            $exception1 = LinkNotFoundException::forId($id1);
            $exception2 = LinkNotFoundException::forId($id2);

            expect($exception1->getMessage())->toBe("Link with ID '{$id1}' not found");
            expect($exception2->getMessage())->toBe("Link with ID '{$id2}' not found");
            expect($exception1)->not->toBe($exception2);
        });
    });

});
