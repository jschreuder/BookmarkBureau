<?php

use jschreuder\BookmarkBureau\Exception\InactiveUnitOfWorkException;

describe('InactiveUnitOfWorkException', function () {

    test('can be instantiated', function () {
        $exception = new InactiveUnitOfWorkException();

        expect($exception)->toBeInstanceOf(InactiveUnitOfWorkException::class);
    });

    test('extends RuntimeException', function () {
        $exception = new InactiveUnitOfWorkException();

        expect($exception)->toBeInstanceOf(RuntimeException::class);
    });

    test('can be thrown', function () {
        expect(function () {
            throw new InactiveUnitOfWorkException();
        })->toThrow(InactiveUnitOfWorkException::class);
    });

    test('can be caught as RuntimeException', function () {
        expect(function () {
            throw new InactiveUnitOfWorkException();
        })->toThrow(RuntimeException::class);
    });

    test('can be instantiated with a message', function () {
        $message = 'Unit of work is not active';
        $exception = new InactiveUnitOfWorkException($message);

        expect($exception->getMessage())->toBe($message);
    });

    test('can be instantiated with a message and code', function () {
        $message = 'Unit of work is not active';
        $code = 500;
        $exception = new InactiveUnitOfWorkException($message, $code);

        expect($exception->getMessage())->toBe($message);
        expect($exception->getCode())->toBe($code);
    });

});
