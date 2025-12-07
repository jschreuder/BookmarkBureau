<?php

use jschreuder\BookmarkBureau\Action\TagDeleteAction;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\BookmarkBureau\InputSpec\TagNameInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;

describe('TagDeleteAction', function () {
    describe('filter method', function () {
        test('filters id and trims whitespace', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            $filtered = $action->filter(['id' => '  important  ']);

            expect($filtered['id'])->toBe('important');
        });
    });

    describe('validate method', function () {
        test('passes validation with valid id', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            try {
                $action->validate(['id' => 'important']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for empty id', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            expect(fn() => $action->validate(['id' => '']))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing id', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            expect(fn() => $action->validate([]))
                ->toThrow(ValidationFailedException::class);
        });
    });

    describe('execute method', function () {
        test('calls tagService.deleteTag and returns empty array', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            $tagService->shouldReceive('deleteTag')
                ->once()
                ->with('important');

            $result = $action->execute(['id' => 'important']);

            expect($result)->toBe([]);
        });
    });

    describe('full workflow', function () {
        test('filter -> validate -> execute workflow', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new TagDeleteAction($tagService, new TagNameInputSpec());

            $rawData = ['id' => '  important  ', 'extra' => 'ignored'];
            $filtered = $action->filter($rawData);
            expect($filtered['id'])->toBe('important');

            try {
                $action->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }

            $tagService->shouldReceive('deleteTag')
                ->once()
                ->with('important');

            $result = $action->execute($filtered);
            expect($result)->toBe([]);
        });
    });
});
