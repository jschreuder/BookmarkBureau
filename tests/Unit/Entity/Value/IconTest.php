<?php

use jschreuder\BookmarkBureau\Entity\Value\Icon;

describe('Icon Value Object', function () {
    describe('valid icons', function () {
        test('creates a valid icon with simple string', function () {
            $icon = new Icon('github');

            expect($icon->value)->toBe('github');
            expect((string) $icon)->toBe('github');
        });

        test('creates a valid icon with data URI', function () {
            $icon = new Icon('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjwvc3ZnPg==');

            expect($icon->value)->toBe('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjwvc3ZnPg==');
        });

        test('creates a valid icon with URL', function () {
            $icon = new Icon('https://example.com/icon.svg');

            expect($icon->value)->toBe('https://example.com/icon.svg');
        });

        test('creates a valid icon with path', function () {
            $icon = new Icon('/icons/bookmark.svg');

            expect($icon->value)->toBe('/icons/bookmark.svg');
        });

        test('creates a valid icon with relative path', function () {
            $icon = new Icon('../assets/icons/star.png');

            expect($icon->value)->toBe('../assets/icons/star.png');
        });

        test('creates a valid icon with font icon class name', function () {
            $icon = new Icon('fas fa-star');

            expect($icon->value)->toBe('fas fa-star');
        });

        test('preserves whitespace within icon value', function () {
            $icon = new Icon('icon with spaces');

            expect($icon->value)->toBe('icon with spaces');
        });

        test('preserves leading spaces in value', function () {
            $icon = new Icon('  leading-spaces');

            expect($icon->value)->toBe('  leading-spaces');
        });

        test('preserves trailing spaces in value', function () {
            $icon = new Icon('trailing-spaces  ');

            expect($icon->value)->toBe('trailing-spaces  ');
        });

        test('creates a valid icon with special characters', function () {
            $icon = new Icon('icon-name_v1.2');

            expect($icon->value)->toBe('icon-name_v1.2');
        });

        test('creates valid icons with various formats', function () {
            $testIcons = [
                'fa-heart',
                'material-icons',
                'mdi-github',
                'http://example.com/icon.png',
                'icon@2x',
            ];

            foreach ($testIcons as $iconValue) {
                $icon = new Icon($iconValue);
                expect($icon->value)->toBe($iconValue);
            }
        });

        test('creates a valid icon with single character', function () {
            $icon = new Icon('A');

            expect($icon->value)->toBe('A');
        });
    });

    describe('invalid icons', function () {
        test('throws exception for empty string', function () {
            expect(fn() => new Icon(''))
                ->toThrow(InvalidArgumentException::class, 'Icon cannot be empty');
        });

        test('throws exception for whitespace only', function () {
            expect(fn() => new Icon('   '))
                ->toThrow(InvalidArgumentException::class, 'Icon cannot be empty');
        });

        test('throws exception for tabs only', function () {
            expect(fn() => new Icon("\t\t"))
                ->toThrow(InvalidArgumentException::class, 'Icon cannot be empty');
        });

        test('throws exception for newlines only', function () {
            expect(fn() => new Icon("\n\n"))
                ->toThrow(InvalidArgumentException::class, 'Icon cannot be empty');
        });

        test('throws exception for mixed whitespace only', function () {
            expect(fn() => new Icon(" \t\n "))
                ->toThrow(InvalidArgumentException::class, 'Icon cannot be empty');
        });
    });

    describe('immutability', function () {
        test('Icon value object is immutable', function () {
            $icon = new Icon('github');

            expect($icon->value)->toBe('github');

            // The object should be readonly, attempting to modify should fail
            expect(fn() => $icon->value = 'gitlab')
                ->toThrow(Error::class);
        });
    });
});
