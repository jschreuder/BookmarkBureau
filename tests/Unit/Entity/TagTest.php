<?php

use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;

describe('Tag Entity', function () {
    function createTestTag(
        ?string $tagName = null,
        ?HexColor $color = null
    ): Tag {
        return new Tag(
            tagName: $tagName ?? 'example-tag',
            color: $color ?? new HexColor('#FF5733')
        );
    }

    describe('construction', function () {
        test('creates a tag with all properties', function () {
            $tagName = 'important';
            $color = new HexColor('#FF0000');

            $tag = new Tag($tagName, $color);

            expect($tag)->toBeInstanceOf(Tag::class);
        });

        test('stores all properties correctly during construction', function () {
            $tagName = 'important';
            $color = new HexColor('#FF0000');

            $tag = new Tag($tagName, $color);

            expect($tag->getTagName())->toBe($tagName);
            expect($tag->getColor())->toBe($color);
        });
    });

    describe('tagName getter', function () {
        test('getTagName returns the tag name', function () {
            $tagName = 'important';
            $tag = createTestTag(tagName: $tagName);

            expect($tag->getTagName())->toBe($tagName);
            expect($tag->getTagName())->toBeString();
        });

        test('getTagName returns the exact tag name provided', function () {
            $tagNames = [
                'todo',
                'bug-fix',
                'documentation',
                'feature-request',
            ];

            foreach ($tagNames as $tagName) {
                $tag = createTestTag(tagName: $tagName);
                expect($tag->getTagName())->toBe($tagName);
            }
        });

        test('tagName is readonly and cannot be modified', function () {
            $tag = createTestTag();

            expect(fn() => $tag->tagName = 'modified-tag')
                ->toThrow(Error::class);
        });
    });

    describe('color getter and setter', function () {
        test('getColor returns the color', function () {
            $color = new HexColor('#00FF00');
            $tag = createTestTag(color: $color);

            expect($tag->getColor())->toBe($color);
            expect($tag->getColor())->toBeInstanceOf(HexColor::class);
        });

        test('setColor updates the color', function () {
            $tag = createTestTag();
            $newColor = new HexColor('#0000FF');

            $tag->setColor($newColor);

            expect($tag->getColor())->toBe($newColor);
        });

        test('setColor works with various hex colors', function () {
            $tag = createTestTag();
            $colors = [
                new HexColor('#FFFFFF'),
                new HexColor('#000000'),
                new HexColor('#123456'),
                new HexColor('#abcdef'),
            ];

            foreach ($colors as $color) {
                $tag->setColor($color);
                expect($tag->getColor())->toBe($color);
            }
        });
    });

    describe('multiple setters', function () {
        test('can update color multiple times in sequence', function () {
            $tag = createTestTag();
            $color1 = new HexColor('#FF0000');
            $color2 = new HexColor('#00FF00');
            $color3 = new HexColor('#0000FF');

            $tag->setColor($color1);
            expect($tag->getColor())->toBe($color1);

            $tag->setColor($color2);
            expect($tag->getColor())->toBe($color2);

            $tag->setColor($color3);
            expect($tag->getColor())->toBe($color3);
        });
    });

    describe('immutability constraints', function () {
        test('tagName cannot be modified directly', function () {
            $tag = createTestTag();

            expect(fn() => $tag->tagName = 'different-tag')
                ->toThrow(Error::class);
        });

        test('color property cannot be modified directly', function () {
            $tag = createTestTag();

            expect(fn() => $tag->color = new HexColor('#000000'))
                ->toThrow(Error::class);
        });
    });

    describe('edge cases', function () {
        test('can create tag with single character tag name', function () {
            $tag = createTestTag(tagName: 'a');

            expect($tag->getTagName())->toBe('a');
        });

        test('can create tag with long tag name', function () {
            $longTagName = str_repeat('tag', 30);
            $tag = createTestTag(tagName: $longTagName);

            expect($tag->getTagName())->toBe($longTagName);
        });

        test('can create tag with tag name containing special characters', function () {
            $tag = createTestTag(tagName: 'tag-with-dashes_and_underscores');

            expect($tag->getTagName())->toBe('tag-with-dashes_and_underscores');
        });

        test('can create tag with tag name containing numbers', function () {
            $tag = createTestTag(tagName: 'tag123');

            expect($tag->getTagName())->toBe('tag123');
        });
    });
});
