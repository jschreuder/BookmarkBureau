<?php

use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;

describe('Tag Entity', function () {
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

            expect($tag->tagName)->toBe($tagName);
            expect($tag->color)->toBe($color);
        });
    });

    describe('tagName getter', function () {
        test('getTagName returns the tag name', function () {
            $tagName = 'important';
            $tag = TestEntityFactory::createTag(tagName: $tagName);

            expect($tag->tagName)->toBe($tagName);
            expect($tag->tagName)->toBeString();
        });

        test('getTagName returns the exact tag name provided', function () {
            $tagNames = [
                'todo',
                'bug-fix',
                'documentation',
                'feature-request',
            ];

            foreach ($tagNames as $tagName) {
                $tag = TestEntityFactory::createTag(tagName: $tagName);
                expect($tag->tagName)->toBe($tagName);
            }
        });

        test('tagName is readonly and cannot be modified', function () {
            $tag = TestEntityFactory::createTag();

            expect(fn() => $tag->tagName = 'modified-tag')
                ->toThrow(Error::class);
        });
    });

    describe('color getter and setter', function () {
        test('getColor returns the color', function () {
            $color = new HexColor('#00FF00');
            $tag = TestEntityFactory::createTag(color: $color);

            expect($tag->color)->toBe($color);
            expect($tag->color)->toBeInstanceOf(HexColor::class);
        });

        test('setColor updates the color', function () {
            $tag = TestEntityFactory::createTag();
            $newColor = new HexColor('#0000FF');

            $tag->color = $newColor;

            expect($tag->color)->toBe($newColor);
        });

        test('setColor works with various hex colors', function () {
            $tag = TestEntityFactory::createTag();
            $colors = [
                new HexColor('#FFFFFF'),
                new HexColor('#000000'),
                new HexColor('#123456'),
                new HexColor('#abcdef'),
            ];

            foreach ($colors as $color) {
                $tag->color = $color;
                expect($tag->color)->toBe($color);
            }
        });
    });

    describe('multiple setters', function () {
        test('can update color multiple times in sequence', function () {
            $tag = TestEntityFactory::createTag();
            $color1 = new HexColor('#FF0000');
            $color2 = new HexColor('#00FF00');
            $color3 = new HexColor('#0000FF');

            $tag->color = $color1;
            expect($tag->color)->toBe($color1);

            $tag->color = $color2;
            expect($tag->color)->toBe($color2);

            $tag->color = $color3;
            expect($tag->color)->toBe($color3);
        });
    });

    describe('immutability constraints', function () {
        test('tagName cannot be modified directly', function () {
            $tag = TestEntityFactory::createTag();

            expect(fn() => $tag->tagName = 'different-tag')
                ->toThrow(Error::class);
        });
    });

    describe('edge cases', function () {
        test('can create tag with single character tag name', function () {
            $tag = TestEntityFactory::createTag(tagName: 'a');

            expect($tag->tagName)->toBe('a');
        });

        test('can create tag with long tag name', function () {
            $longTagName = str_repeat('tag', 30);
            $tag = TestEntityFactory::createTag(tagName: $longTagName);

            expect($tag->tagName)->toBe($longTagName);
        });

        test('can create tag with tag name containing special characters', function () {
            $tag = TestEntityFactory::createTag(tagName: 'tag-with-dashes_and_underscores');

            expect($tag->tagName)->toBe('tag-with-dashes_and_underscores');
        });

        test('can create tag with tag name containing numbers', function () {
            $tag = TestEntityFactory::createTag(tagName: 'tag123');

            expect($tag->tagName)->toBe('tag123');
        });
    });
});
