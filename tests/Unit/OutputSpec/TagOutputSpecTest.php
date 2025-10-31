<?php

use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;

describe('TagOutputSpec', function () {
    describe('initialization', function () {
        test('creates OutputSpec instance', function () {
            $spec = new TagOutputSpec();

            expect($spec)->toBeInstanceOf(TagOutputSpec::class);
        });

        test('implements OutputSpecInterface', function () {
            $spec = new TagOutputSpec();

            expect($spec)->toBeInstanceOf(\jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface::class);
        });

        test('is readonly', function () {
            $spec = new TagOutputSpec();

            expect($spec)->toBeInstanceOf(TagOutputSpec::class);
        });
    });

    describe('supports method', function () {
        test('supports Tag objects', function () {
            $spec = new TagOutputSpec();
            $tag = TestEntityFactory::createTag();

            expect($spec->supports($tag))->toBeTrue();
        });

        test('does not support Link objects', function () {
            $spec = new TagOutputSpec();
            $link = TestEntityFactory::createLink();

            expect($spec->supports($link))->toBeFalse();
        });

        test('does not support stdClass objects', function () {
            $spec = new TagOutputSpec();

            expect($spec->supports(new stdClass()))->toBeFalse();
        });
    });

    describe('transform method', function () {
        test('transforms Tag to array with all fields', function () {
            $spec = new TagOutputSpec();
            $tag = TestEntityFactory::createTag();

            $result = $spec->transform($tag);

            expect($result)->toBeArray();
            expect($result)->toHaveKeys(['tag_name', 'color']);
        });

        test('returns tag_name as string', function () {
            $spec = new TagOutputSpec();
            $tagName = new TagName('important');
            $tag = TestEntityFactory::createTag(tagName: $tagName);

            $result = $spec->transform($tag);

            expect($result['tag_name'])->toBeString();
            expect($result['tag_name'])->toBe('important');
        });

        test('returns color as string when present', function () {
            $spec = new TagOutputSpec();
            $color = new HexColor('#FF0000');
            $tag = TestEntityFactory::createTag(color: $color);

            $result = $spec->transform($tag);

            expect($result['color'])->toBeString();
            expect($result['color'])->toBe('#FF0000');
        });

        test('returns color as null when not present', function () {
            $spec = new TagOutputSpec();
            $tag = TestEntityFactory::createTag(color: null);

            $result = $spec->transform($tag);

            expect($result['color'])->toBeNull();
        });

        test('throws InvalidArgumentException when transforming unsupported object', function () {
            $spec = new TagOutputSpec();
            $link = TestEntityFactory::createLink();

            expect(fn() => $spec->transform($link))
                ->toThrow(InvalidArgumentException::class);
        });

        test('exception message contains class name and unsupported type', function () {
            $spec = new TagOutputSpec();
            $link = TestEntityFactory::createLink();

            expect(fn() => $spec->transform($link))
                ->toThrow(InvalidArgumentException::class)
                ->and(fn() => $spec->transform($link))
                ->toThrow(function (InvalidArgumentException $e) {
                    return str_contains($e->getMessage(), TagOutputSpec::class)
                        && str_contains($e->getMessage(), Link::class);
                });
        });
    });

    describe('edge cases', function () {
        test('handles tag with long name', function () {
            $spec = new TagOutputSpec();
            $longName = str_repeat('a', 100);
            $tagName = new TagName($longName);
            $tag = TestEntityFactory::createTag(tagName: $tagName);

            $result = $spec->transform($tag);

            expect($result['tag_name'])->toBe($longName);
        });

        test('handles tag with hyphens in name', function () {
            $spec = new TagOutputSpec();
            $nameWithHyphens = 'tag-with-hyphens-123';
            $tagName = new TagName($nameWithHyphens);
            $tag = TestEntityFactory::createTag(tagName: $tagName);

            $result = $spec->transform($tag);

            expect($result['tag_name'])->toBe($nameWithHyphens);
        });

        test('handles tag with various hex color formats', function () {
            $spec = new TagOutputSpec();
            $colors = ['#000000', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF', '#FFA500'];

            foreach ($colors as $colorValue) {
                $color = new HexColor($colorValue);
                $tag = TestEntityFactory::createTag(color: $color);

                $result = $spec->transform($tag);

                expect($result['color'])->toBe($colorValue);
            }
        });

        test('handles multiple tags independently', function () {
            $spec = new TagOutputSpec();
            $tag1 = TestEntityFactory::createTag(tagName: new TagName('first'));
            $tag2 = TestEntityFactory::createTag(tagName: new TagName('second'));

            $result1 = $spec->transform($tag1);
            $result2 = $spec->transform($tag2);

            expect($result1['tag_name'])->toBe('first');
            expect($result2['tag_name'])->toBe('second');
            expect($result1['tag_name'])->not->toBe($result2['tag_name']);
        });

        test('handles tag with numbers in name', function () {
            $spec = new TagOutputSpec();
            $nameWithNumbers = 'tag-123-numbers';
            $tagName = new TagName($nameWithNumbers);
            $tag = TestEntityFactory::createTag(tagName: $tagName);

            $result = $spec->transform($tag);

            expect($result['tag_name'])->toBe($nameWithNumbers);
        });
    });

    describe('integration with OutputSpecInterface', function () {
        test('transform method signature matches interface', function () {
            $spec = new TagOutputSpec();
            $tag = TestEntityFactory::createTag();

            $result = $spec->transform($tag);

            expect($result)->toBeArray();
        });

        test('can be used polymorphically through interface', function () {
            $spec = new TagOutputSpec();
            $interface = $spec;
            $tag = TestEntityFactory::createTag();

            expect($interface)->toBeInstanceOf(\jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface::class);

            $result = $interface->transform($tag);

            expect($result)->toBeArray();
        });
    });
});
