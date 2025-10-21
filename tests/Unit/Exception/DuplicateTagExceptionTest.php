<?php

use jschreuder\BookmarkBureau\Exception\DuplicateTagException;

describe('DuplicateTagException', function () {

    describe('forName factory method', function () {
        test('creates exception with correct message', function () {
            $tagName = 'important';
            $exception = DuplicateTagException::forName($tagName);

            expect($exception)->toBeInstanceOf(DuplicateTagException::class);
            expect($exception->getMessage())->toBe("Tag with name '{$tagName}' already exists");
        });

        test('creates exception with 409 status code', function () {
            $tagName = 'important';
            $exception = DuplicateTagException::forName($tagName);

            expect($exception->getCode())->toBe(409);
        });

        test('exception is throwable', function () {
            $tagName = 'important';
            expect(function () use ($tagName) {
                throw DuplicateTagException::forName($tagName);
            })->toThrow(DuplicateTagException::class);
        });

        test('exception can be caught as RuntimeException', function () {
            $tagName = 'important';
            expect(function () use ($tagName) {
                throw DuplicateTagException::forName($tagName);
            })->toThrow(RuntimeException::class);
        });

        test('multiple calls with different names create independent exceptions', function () {
            $name1 = 'important';
            $name2 = 'urgent';
            $exception1 = DuplicateTagException::forName($name1);
            $exception2 = DuplicateTagException::forName($name2);

            expect($exception1->getMessage())->toBe("Tag with name '{$name1}' already exists");
            expect($exception2->getMessage())->toBe("Tag with name '{$name2}' already exists");
            expect($exception1)->not->toBe($exception2);
        });
    });

});
