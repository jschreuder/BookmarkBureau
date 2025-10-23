<?php

use jschreuder\BookmarkBureau\Exception\CategoryNotFoundException;
use Ramsey\Uuid\Uuid;

describe('CategoryNotFoundException', function () {

    describe('forId factory method', function () {
        test('creates exception with correct message', function () {
            $categoryId = Uuid::uuid4();
            $exception = CategoryNotFoundException::forId($categoryId);

            expect($exception)->toBeInstanceOf(CategoryNotFoundException::class);
            expect($exception->getMessage())->toBe("Category with ID '{$categoryId}' not found");
        });

        test('creates exception with 404 status code', function () {
            $categoryId = Uuid::uuid4();
            $exception = CategoryNotFoundException::forId($categoryId);

            expect($exception->getCode())->toBe(404);
        });

        test('exception is throwable', function () {
            $categoryId = Uuid::uuid4();
            expect(function () use ($categoryId) {
                throw CategoryNotFoundException::forId($categoryId);
            })->toThrow(CategoryNotFoundException::class);
        });

        test('exception can be caught as RuntimeException', function () {
            $categoryId = Uuid::uuid4();
            expect(function () use ($categoryId) {
                throw CategoryNotFoundException::forId($categoryId);
            })->toThrow(RuntimeException::class);
        });

        test('multiple calls with different IDs create independent exceptions', function () {
            $id1 = Uuid::uuid4();
            $id2 = Uuid::uuid4();
            $exception1 = CategoryNotFoundException::forId($id1);
            $exception2 = CategoryNotFoundException::forId($id2);

            expect($exception1->getMessage())->toBe("Category with ID '{$id1}' not found");
            expect($exception2->getMessage())->toBe("Category with ID '{$id2}' not found");
            expect($exception1)->not->toBe($exception2);
        });
    });

});
