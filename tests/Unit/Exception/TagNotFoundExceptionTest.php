<?php

use jschreuder\BookmarkBureau\Exception\TagNotFoundException;

describe('TagNotFoundException', function () {

    describe('forName factory method', function () {
        test('creates exception with correct message', function () {
            $tagName = 'my-tag';
            $exception = TagNotFoundException::forName($tagName);

            expect($exception)->toBeInstanceOf(TagNotFoundException::class);
            expect($exception->getMessage())->toBe("Tag with name '{$tagName}' not found");
        });

        test('creates exception with 404 status code', function () {
            $tagName = 'other-tag';
            $exception = TagNotFoundException::forName($tagName);

            expect($exception->getCode())->toBe(404);
        });

        test('exception is throwable', function () {
            $tagName = 'error-me';
            expect(function () use ($tagName) {
                throw TagNotFoundException::forName($tagName);
            })->toThrow(TagNotFoundException::class);
        });

        test('exception can be caught as RuntimeException', function () {
            $tagName = 'error-me';
            expect(function () use ($tagName) {
                throw TagNotFoundException::forName($tagName);
            })->toThrow(RuntimeException::class);
        });

        test('multiple calls with different names create independent exceptions', function () {
            $name1 = 'not-me';
            $name2 = 'or me';
            $exception1 = TagNotFoundException::forName($name1);
            $exception2 = TagNotFoundException::forName($name2);

            expect($exception1->getMessage())->toBe("Tag with name '{$name1}' not found");
            expect($exception2->getMessage())->toBe("Tag with name '{$name2}' not found");
            expect($exception1)->not->toBe($exception2);
        });
    });

});
