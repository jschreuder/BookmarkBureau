<?php

use jschreuder\BookmarkBureau\Exception\ResponseTransformerException;

describe('ResponseTransformerException', function () {

    test('can be instantiated', function () {
        $exception = new ResponseTransformerException();

        expect($exception)->toBeInstanceOf(ResponseTransformerException::class);
    });

    test('extends RuntimeException', function () {
        $exception = new ResponseTransformerException();

        expect($exception)->toBeInstanceOf(RuntimeException::class);
    });

    test('can be thrown', function () {
        expect(function () {
            throw new ResponseTransformerException();
        })->toThrow(ResponseTransformerException::class);
    });

    test('can be caught as RuntimeException', function () {
        expect(function () {
            throw new ResponseTransformerException();
        })->toThrow(RuntimeException::class);
    });

    test('can be instantiated with a message', function () {
        $message = 'Failed to transform response';
        $exception = new ResponseTransformerException($message);

        expect($exception->getMessage())->toBe($message);
    });

    test('can be instantiated with a message and code', function () {
        $message = 'Failed to transform response';
        $code = 500;
        $exception = new ResponseTransformerException($message, $code);

        expect($exception->getMessage())->toBe($message);
        expect($exception->getCode())->toBe($code);
    });

});
