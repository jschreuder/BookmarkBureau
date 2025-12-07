<?php

use jschreuder\BookmarkBureau\Action\LinkDeleteAction;
use jschreuder\BookmarkBureau\InputSpec\IdInputSpec;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

describe("LinkDeleteAction", function () {
    describe("getAttributeKeysForData method", function () {
        test("returns link_id attribute key", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            expect($action->getAttributeKeysForData())->toBe(["link_id"]);
        });
    });

    describe("filter method", function () {
        test("trims whitespace from id", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);
            $linkId = Uuid::uuid4();

            $filtered = $action->filter([
                "link_id" => "  {$linkId->toString()}  ",
            ]);

            expect($filtered["link_id"])->toBe($linkId->toString());
        });

        test("handles missing id key with empty string", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $filtered = $action->filter([]);

            expect($filtered["link_id"])->toBe("");
        });

        test("preserves valid id without modification", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);
            $linkId = Uuid::uuid4();

            $filtered = $action->filter([
                "link_id" => $linkId->toString(),
            ]);

            expect($filtered["link_id"])->toBe($linkId->toString());
        });

        test("ignores additional fields in input", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);
            $linkId = Uuid::uuid4();

            $filtered = $action->filter([
                "link_id" => $linkId->toString(),
                "url" => "https://example.com",
                "title" => "Should be ignored",
                "extra_field" => "ignored",
            ]);

            expect($filtered)->toHaveKey("link_id");
            expect($filtered)->not->toHaveKey("url");
            expect($filtered)->not->toHaveKey("title");
            expect($filtered)->not->toHaveKey("extra_field");
        });
    });

    describe("validate method", function () {
        test("passes validation with valid UUID", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);
            $linkId = Uuid::uuid4();

            $data = ["link_id" => $linkId->toString()];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for invalid UUID", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $data = ["link_id" => "not-a-uuid"];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for empty id", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $data = ["link_id" => ""];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for missing id key", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $data = [];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for whitespace-only id", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $data = ["link_id" => "   "];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for null id", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $data = ["link_id" => null];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("validates UUID in different formats", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);
            $linkId = Uuid::uuid4();

            $data = ["link_id" => $linkId->toString()];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });

    describe("execute method", function () {
        test("calls deleteLink on service with correct UUID", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();

            $linkService
                ->shouldReceive("deleteLink")
                ->with(Mockery::type(UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $result = $action->execute([
                "link_id" => $linkId->toString(),
            ]);

            expect($result)->toBe([]);
        });

        test("returns empty array after successful deletion", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();

            $linkService
                ->shouldReceive("deleteLink")
                ->with(Mockery::type(UuidInterface::class));

            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $result = $action->execute([
                "link_id" => $linkId->toString(),
            ]);

            expect($result)->toEqual([]);
            expect($result)->toBeArray();
        });

        test(
            "converts string id to UUID before passing to service",
            function () {
                $linkService = Mockery::mock(LinkServiceInterface::class);
                $linkId = Uuid::uuid4();

                $linkService
                    ->shouldReceive("deleteLink")
                    ->with(Mockery::type(UuidInterface::class))
                    ->once();

                $inputSpec = new IdInputSpec("link_id");
                $action = new LinkDeleteAction($linkService, $inputSpec);

                $action->execute([
                    "link_id" => $linkId->toString(),
                ]);

                expect(true)->toBeTrue();
            },
        );

        test("passes exact UUID to service", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();

            $uuidCapture = null;
            $linkService
                ->shouldReceive("deleteLink")
                ->andReturnUsing(function ($uuid) use (&$uuidCapture) {
                    $uuidCapture = $uuid;
                });

            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $action->execute([
                "link_id" => $linkId->toString(),
            ]);

            expect($uuidCapture->toString())->toBe($linkId->toString());
        });
    });

    describe("integration scenarios", function () {
        test("full workflow: filter, validate, and execute", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();

            $linkService
                ->shouldReceive("deleteLink")
                ->with(Mockery::type(UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $rawData = [
                "link_id" => "  {$linkId->toString()}  ",
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBe([]);
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("full workflow with extra fields in input", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();

            $linkService
                ->shouldReceive("deleteLink")
                ->with(Mockery::type(UuidInterface::class))
                ->once();

            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $rawData = [
                "link_id" => $linkId->toString(),
                "url" => "https://example.com",
                "title" => "Should be ignored",
                "extra" => "data",
            ];

            $filtered = $action->filter($rawData);
            expect($filtered)->not->toHaveKey("url");
            expect($filtered)->not->toHaveKey("title");

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBe([]);
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("full workflow filters and validates id correctly", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();

            $linkService
                ->shouldReceive("deleteLink")
                ->with(Mockery::type(UuidInterface::class));

            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $rawData = [
                "link_id" => "  {$linkId->toString()}  ",
            ];

            $filtered = $action->filter($rawData);
            expect($filtered["link_id"])->toBe($linkId->toString());

            $action->validate($filtered);
            $result = $action->execute($filtered);

            expect($result)->toBe([]);
        });

        test("validation failure prevents service call", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);

            $linkService->shouldNotReceive("deleteLink");

            $inputSpec = new IdInputSpec("link_id");
            $action = new LinkDeleteAction($linkService, $inputSpec);

            $rawData = [
                "link_id" => "invalid-uuid",
            ];

            $filtered = $action->filter($rawData);

            expect(function () use ($action, $filtered) {
                $action->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });
    });
});
