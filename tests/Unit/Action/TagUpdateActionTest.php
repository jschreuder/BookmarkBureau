<?php

use jschreuder\BookmarkBureau\Action\TagUpdateAction;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\BookmarkBureau\InputSpec\TagInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\Middle\Exception\ValidationFailedException;

describe('TagUpdateAction', function () {
    describe('filter method', function () {
        test('filters all fields with whitespace trimmed', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagUpdateAction($tagService, new TagInputSpec(), new TagOutputSpec());

            $filtered = $action->filter(['tag_name' => '  important  ', 'color' => '  #FF0000  ']);

            expect($filtered['tag_name'])->toBe('important');
            expect($filtered['color'])->toBe('#FF0000');
        });
    });

    describe('validate method', function () {
        test('passes validation with valid data', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagUpdateAction($tagService, new TagInputSpec(), new TagOutputSpec());

            try {
                $action->validate(['tag_name' => 'important', 'color' => '#FF0000']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for empty tag_name', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagUpdateAction($tagService, new TagInputSpec(), new TagOutputSpec());

            expect(fn() => $action->validate(['tag_name' => '', 'color' => '#FF0000']))
                ->toThrow(ValidationFailedException::class);
        });
    });

    describe('execute method', function () {
        test('calls tagService.updateTag with correct parameters', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagUpdateAction($tagService, new TagInputSpec(), new TagOutputSpec());
            $updatedTag = TestEntityFactory::createTag(tagName: new TagName('important'));

            $tagService->shouldReceive('updateTag')
                ->once()
                ->with('important', '#FF0000')
                ->andReturn($updatedTag);

            $result = $action->execute(['tag_name' => 'important', 'color' => '#FF0000']);

            expect($result)->toBeArray();
            expect($result['tag_name'])->toBe('important');
        });
    });
});
