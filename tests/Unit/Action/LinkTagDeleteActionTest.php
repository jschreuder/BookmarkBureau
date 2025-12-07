<?php

use jschreuder\BookmarkBureau\Action\LinkTagDeleteAction;
use jschreuder\BookmarkBureau\Service\TagServiceInterface;
use jschreuder\BookmarkBureau\InputSpec\LinkTagInputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

describe("LinkTagDeleteAction", function () {
    describe("getAttributeKeysForData method", function () {
        test(
            "returns both link_id and tag_name for delete relation action",
            function () {
                $tagService = Mockery::mock(TagServiceInterface::class);
                $action = new LinkTagDeleteAction(
                    $tagService,
                    new LinkTagInputSpec(),
                );

                expect($action->getAttributeKeysForData())->toBe([
                    "link_id",
                    "tag_name",
                ]);
            },
        );
    });

    describe("filter method", function () {
        test("filters all fields with whitespace trimmed", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $uuid = Uuid::uuid4()->toString();
            $action = new LinkTagDeleteAction(
                $tagService,
                new LinkTagInputSpec(),
            );

            $filtered = $action->filter([
                "link_id" => "  {$uuid}  ",
                "tag_name" => "  important  ",
            ]);

            expect($filtered["link_id"])->toBe($uuid);
            expect($filtered["tag_name"])->toBe("important");
        });
    });

    describe("validate method", function () {
        test("passes validation with valid data", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $uuid = Uuid::uuid4()->toString();
            $action = new LinkTagDeleteAction(
                $tagService,
                new LinkTagInputSpec(),
            );

            try {
                $action->validate([
                    "link_id" => $uuid,
                    "tag_name" => "important",
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for invalid link UUID", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $action = new LinkTagDeleteAction(
                $tagService,
                new LinkTagInputSpec(),
            );

            expect(
                fn() => $action->validate([
                    "link_id" => "not-a-uuid",
                    "tag_name" => "important",
                ]),
            )->toThrow(ValidationFailedException::class);
        });

        test("throws validation error for empty tag_name", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $uuid = Uuid::uuid4()->toString();
            $action = new LinkTagDeleteAction(
                $tagService,
                new LinkTagInputSpec(),
            );

            expect(
                fn() => $action->validate([
                    "link_id" => $uuid,
                    "tag_name" => "",
                ]),
            )->toThrow(ValidationFailedException::class);
        });
    });

    describe("execute method", function () {
        test(
            "calls tagService.removeTagFromLink with correct parameters",
            function () {
                $tagService = Mockery::mock(TagServiceInterface::class);
                $uuid = Uuid::uuid4();
                $action = new LinkTagDeleteAction(
                    $tagService,
                    new LinkTagInputSpec(),
                );

                $tagService
                    ->shouldReceive("removeTagFromLink")
                    ->once()
                    ->with(Mockery::type(UuidInterface::class), "important");

                $result = $action->execute([
                    "link_id" => $uuid->toString(),
                    "tag_name" => "important",
                ]);

                expect($result)->toBe([]);
            },
        );
    });

    describe("full workflow", function () {
        test("filter -> validate -> execute workflow", function () {
            $tagService = Mockery::mock(TagServiceInterface::class);
            $uuid = Uuid::uuid4();
            $action = new LinkTagDeleteAction(
                $tagService,
                new LinkTagInputSpec(),
            );

            $rawData = [
                "link_id" => "  {$uuid->toString()}  ",
                "tag_name" => "  important  ",
                "extra" => "ignored",
            ];
            $filtered = $action->filter($rawData);
            expect($filtered["link_id"])->toBe($uuid->toString());
            expect($filtered["tag_name"])->toBe("important");

            try {
                $action->validate($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }

            $tagService
                ->shouldReceive("removeTagFromLink")
                ->once()
                ->with(Mockery::type(UuidInterface::class), "important");

            $result = $action->execute($filtered);
            expect($result)->toBe([]);
        });
    });
});
