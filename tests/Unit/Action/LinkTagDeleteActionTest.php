<?php

use jschreuder\BookmarkBureau\Action\LinkTagDeleteAction;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\BookmarkBureau\InputSpec\LinkTagInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe('LinkTagDeleteAction', function () {
    describe('filter method', function () {
        test('filters all fields with whitespace trimmed', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $uuid = Uuid::uuid4()->toString();
            $action = new LinkTagDeleteAction($tagService, new LinkTagInputSpec());

            $filtered = $action->filter(['id' => "  {$uuid}  ", 'tag_name' => '  important  ']);

            expect($filtered['id'])->toBe($uuid);
            expect($filtered['tag_name'])->toBe('important');
        });
    });

    describe('validate method', function () {
        test('passes validation with valid data', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $uuid = Uuid::uuid4()->toString();
            $action = new LinkTagDeleteAction($tagService, new LinkTagInputSpec());

            try {
                $action->validate(['id' => $uuid, 'tag_name' => 'important']);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for invalid link UUID', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new LinkTagDeleteAction($tagService, new LinkTagInputSpec());

            expect(fn() => $action->validate(['id' => 'not-a-uuid', 'tag_name' => 'important']))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty tag_name', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $uuid = Uuid::uuid4()->toString();
            $action = new LinkTagDeleteAction($tagService, new LinkTagInputSpec());

            expect(fn() => $action->validate(['id' => $uuid, 'tag_name' => '']))
                ->toThrow(ValidationFailedException::class);
        });
    });

    describe('execute method', function () {
        test('calls tagService.removeTagFromLink with correct parameters', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $uuid = Uuid::uuid4();
            $action = new LinkTagDeleteAction($tagService, new LinkTagInputSpec());

            $tagService->shouldReceive('removeTagFromLink')
                ->once()
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class), 'important');

            $result = $action->execute(['id' => $uuid->toString(), 'tag_name' => 'important']);

            expect($result)->toBe([]);
        });
    });

    describe('full workflow', function () {
        test('filter -> validate -> execute workflow', function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $uuid = Uuid::uuid4();
            $action = new LinkTagDeleteAction($tagService, new LinkTagInputSpec());

            $rawData = ['id' => "  {$uuid->toString()}  ", 'tag_name' => '  important  ', 'extra' => 'ignored'];
            $filtered = $action->filter($rawData);
            expect($filtered['id'])->toBe($uuid->toString());
            expect($filtered['tag_name'])->toBe('important');

            try {
                $action->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }

            $tagService->shouldReceive('removeTagFromLink')
                ->once()
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class), 'important');

            $result = $action->execute($filtered);
            expect($result)->toBe([]);
        });
    });
});
