<?php

use jschreuder\BookmarkBureau\Entity\Value\InvalidTokenException;

describe("InvalidTokenException", function () {
    test("extends Exception", function () {
        $exception = new InvalidTokenException("Test message");
        expect($exception)->toBeInstanceOf(Exception::class);
    });

    test("can be instantiated with message", function () {
        $exception = new InvalidTokenException("Invalid signature");
        expect($exception->getMessage())->toBe("Invalid signature");
    });

    test("can be instantiated with message and code", function () {
        $exception = new InvalidTokenException("Invalid signature", 123);
        expect($exception->getMessage())->toBe("Invalid signature");
        expect($exception->getCode())->toBe(123);
    });

    test("can be thrown and caught", function () {
        expect(function () {
            throw new InvalidTokenException("Token expired");
        })->toThrow(InvalidTokenException::class);
    });

    test("can be caught as Exception", function () {
        $caught = false;
        try {
            throw new InvalidTokenException("Test");
        } catch (Exception $e) {
            $caught = true;
        }
        expect($caught)->toBeTrue();
    });
});
