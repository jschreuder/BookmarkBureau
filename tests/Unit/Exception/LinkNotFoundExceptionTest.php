<?php

use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use Ramsey\Uuid\Rfc4122\UuidV4;

describe('LinkNotFoundException', function () {

    describe('forId factory method', function () {
        test('creates exception with correct message', function () {
            $linkId = UuidV4::uuid4();
            $exception = LinkNotFoundException::forId($linkId);

            expect($exception)->toBeInstanceOf(LinkNotFoundException::class);
            expect($exception->getMessage())->toBe("Link with ID '{$linkId}' not found");
        });

        test('creates exception with 404 status code', function () {
            $linkId = UuidV4::uuid4();
            $exception = LinkNotFoundException::forId($linkId);

            expect($exception->getCode())->toBe(404);
        });

        test('exception is throwable', function () {
            $linkId = UuidV4::uuid4();
            expect(function () use ($linkId) {
                throw LinkNotFoundException::forId($linkId);
            })->toThrow(LinkNotFoundException::class);
        });

        test('exception can be caught as RuntimeException', function () {
            $linkId = UuidV4::uuid4();
            expect(function () use ($linkId) {
                throw LinkNotFoundException::forId($linkId);
            })->toThrow(RuntimeException::class);
        });

        test('multiple calls with different IDs create independent exceptions', function () {
            $id1 = UuidV4::uuid4();
            $id2 = UuidV4::uuid4();
            $exception1 = LinkNotFoundException::forId($id1);
            $exception2 = LinkNotFoundException::forId($id2);

            expect($exception1->getMessage())->toBe("Link with ID '{$id1}' not found");
            expect($exception2->getMessage())->toBe("Link with ID '{$id2}' not found");
            expect($exception1)->not->toBe($exception2);
        });
    });

});
