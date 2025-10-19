<?php

use jschreuder\BookmarkBureau\Entity\Value\Url;

describe('Url Value Object', function () {
    describe('valid URLs', function () {
        test('creates a valid URL with http protocol', function () {
            $url = new Url('http://example.com');

            expect($url->getValue())->toBe('http://example.com');
            expect((string) $url)->toBe('http://example.com');
        });

        test('creates a valid URL with https protocol', function () {
            $url = new Url('https://example.com');

            expect($url->getValue())->toBe('https://example.com');
        });

        test('creates a valid URL with path', function () {
            $url = new Url('https://example.com/path/to/page');

            expect($url->getValue())->toBe('https://example.com/path/to/page');
        });

        test('creates a valid URL with query string', function () {
            $url = new Url('https://example.com/search?q=test&sort=date');

            expect($url->getValue())->toBe('https://example.com/search?q=test&sort=date');
        });

        test('creates a valid URL with fragment', function () {
            $url = new Url('https://example.com/page#section');

            expect($url->getValue())->toBe('https://example.com/page#section');
        });

        test('creates a valid URL with port', function () {
            $url = new Url('https://example.com:8080/path');

            expect($url->getValue())->toBe('https://example.com:8080/path');
        });

        test('creates a valid URL with authentication', function () {
            $url = new Url('https://user:pass@example.com');

            expect($url->getValue())->toBe('https://user:pass@example.com');
        });

        test('creates a valid URL with subdomain', function () {
            $url = new Url('https://api.example.com/v1/users');

            expect($url->getValue())->toBe('https://api.example.com/v1/users');
        });

        test('creates a valid URL with www', function () {
            $url = new Url('https://www.example.com');

            expect($url->getValue())->toBe('https://www.example.com');
        });

        test('creates a valid URL with file protocol', function () {
            $url = new Url('ftp://files.example.com/downloads');

            expect($url->getValue())->toBe('ftp://files.example.com/downloads');
        });

        test('creates a valid URL with complex query string', function () {
            $url = new Url('https://example.com/search?q=test%20value&filter=type:article&sort=-date');

            expect($url->getValue())->toBe('https://example.com/search?q=test%20value&filter=type:article&sort=-date');
        });
    });

    describe('invalid URLs', function () {
        test('throws exception for empty string', function () {
            expect(fn() => new Url(''))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for string without protocol', function () {
            expect(fn() => new Url('example.com'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for only protocol', function () {
            expect(fn() => new Url('https://'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for invalid protocol', function () {
            expect(fn() => new Url('a(b)://example.com'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for invalid domain', function () {
            expect(fn() => new Url('https://'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for plain text', function () {
            expect(fn() => new Url('not a url'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception with error message containing provided value', function () {
            $invalidUrl = 'invalid-url-value';

            expect(fn() => new Url($invalidUrl))
                ->toThrow(InvalidArgumentException::class, 'Url Value object must get a valid URL, was given: ' . $invalidUrl);
        });
    });

    describe('immutability', function () {
        test('URL value object is immutable', function () {
            $url = new Url('https://example.com');

            expect($url->getValue())->toBe('https://example.com');

            // The object should be readonly, attempting to modify should fail
            expect(fn() => $url->value = 'https://different.com')
                ->toThrow(Error::class);
        });
    });

    describe('string representation', function () {
        test('__toString method returns the URL value', function () {
            $url = new Url('https://example.com/path?query=value');
            $stringUrl = (string) $url;

            expect($stringUrl)->toBe('https://example.com/path?query=value');
        });

        test('can be used in string context', function () {
            $url = new Url('https://example.com');
            $message = 'The URL is: ' . $url;

            expect($message)->toBe('The URL is: https://example.com');
        });
    });
});
