<?php

use jschreuder\BookmarkBureau\Entity\Value\HexColor;

describe('HexColor Value Object', function () {
    describe('valid hex colors', function () {
        test('creates a valid hex color with lowercase', function () {
            $color = new HexColor('#ff5733');

            expect($color->getValue())->toBe('#ff5733');
            expect((string) $color)->toBe('#ff5733');
        });

        test('creates a valid hex color with uppercase', function () {
            $color = new HexColor('#FF5733');

            expect($color->getValue())->toBe('#FF5733');
        });

        test('creates a valid hex color with mixed case', function () {
            $color = new HexColor('#FfAaBb');

            expect($color->getValue())->toBe('#FfAaBb');
        });

        test('creates a valid hex color with all zeros', function () {
            $color = new HexColor('#000000');

            expect($color->getValue())->toBe('#000000');
        });

        test('creates a valid hex color with all F', function () {
            $color = new HexColor('#FFFFFF');

            expect($color->getValue())->toBe('#FFFFFF');
        });

        test('creates a valid hex color with various values', function () {
            $testColors = [
                '#123456',
                '#abcdef',
                '#ABCDEF',
                '#00ff00',
                '#ff0000',
                '#0000ff',
            ];

            foreach ($testColors as $colorValue) {
                $color = new HexColor($colorValue);
                expect($color->getValue())->toBe($colorValue);
            }
        });
    });

    describe('invalid hex colors', function () {
        test('throws exception for empty string', function () {
            expect(fn() => new HexColor(''))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for missing hash', function () {
            expect(fn() => new HexColor('FF5733'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for too short hex code', function () {
            expect(fn() => new HexColor('#FF57'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for too long hex code', function () {
            expect(fn() => new HexColor('#FF573399'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for non-hex characters', function () {
            expect(fn() => new HexColor('#GG5733'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for invalid characters after hash', function () {
            expect(fn() => new HexColor('#FF@733'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for spaces in hex code', function () {
            expect(fn() => new HexColor('#FF 733'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for plain text', function () {
            expect(fn() => new HexColor('red'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception with error message containing provided value', function () {
            $invalidColor = 'not-a-color';

            expect(fn() => new HexColor($invalidColor))
                ->toThrow(InvalidArgumentException::class, 'HexColor Value object must be a valid HTML hex color (#RRGGBB), was given: ' . $invalidColor);
        });
    });

    describe('immutability', function () {
        test('HexColor value object is immutable', function () {
            $color = new HexColor('#FF5733');

            expect($color->getValue())->toBe('#FF5733');

            // The object should be readonly, attempting to modify should fail
            expect(fn() => $color->value = '#000000')
                ->toThrow(Error::class);
        });
    });

    describe('string representation', function () {
        test('__toString method returns the hex color value', function () {
            $color = new HexColor('#FF5733');
            $stringColor = (string) $color;

            expect($stringColor)->toBe('#FF5733');
        });

        test('can be used in string context', function () {
            $color = new HexColor('#FF5733');
            $message = 'The color is: ' . $color;

            expect($message)->toBe('The color is: #FF5733');
        });
    });
});
