<?php

use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Composite\TagNameCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Service\LinkService;
use jschreuder\BookmarkBureau\Service\LinkServicePipelines;
use Ramsey\Uuid\Uuid;

describe("LinkService", function () {
    describe("getLink method", function () {
        test("retrieves a link by ID", function () {
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->with($linkId)
                ->andReturn($link);
            $pipelines = new LinkServicePipelines();

            $service = new LinkService($linkRepository, $pipelines);

            $result = $service->getLink($linkId);

            expect($result)->toBe($link);
            expect($result->linkId)->toEqual($linkId);
        });

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $linkId = Uuid::uuid4();

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $linkRepository
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->andThrow(LinkNotFoundException::forId($linkId));

                $pipelines = new LinkServicePipelines();

                $service = new LinkService($linkRepository, $pipelines);

                expect(fn() => $service->getLink($linkId))->toThrow(
                    LinkNotFoundException::class,
                );
            },
        );
    });

    describe("createLink method", function () {
        test("creates a new link with all parameters", function () {
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository->shouldReceive("insert")->once();

            $pipelines = new LinkServicePipelines();

            $service = new LinkService($linkRepository, $pipelines);

            $result = $service->createLink(
                "https://example.com",
                "Test Title",
                "Test Description",
                "test-icon",
            );

            expect($result)->toBeInstanceOf(Link::class);
            expect($result->url->value)->toBe("https://example.com");
            expect($result->title->value)->toBe("Test Title");
            expect($result->description)->toBe("Test Description");
            expect($result->icon?->value)->toBe("test-icon");
        });

        test("creates a new link without icon", function () {
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository->shouldReceive("insert")->once();

            $pipelines = new LinkServicePipelines();

            $service = new LinkService($linkRepository, $pipelines);

            $result = $service->createLink(
                "https://example.com",
                "Test Title",
                "Test Description",
                null,
            );

            expect($result)->toBeInstanceOf(Link::class);
            expect($result->icon)->toBeNull();
        });

        test("creates a new link with empty description", function () {
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository->shouldReceive("insert")->once();

            $pipelines = new LinkServicePipelines();

            $service = new LinkService($linkRepository, $pipelines);

            $result = $service->createLink("https://example.com", "Test Title");

            expect($result->description)->toBe("");
        });

        test("rolls back transaction on invalid title", function () {
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

            $pipelines = new LinkServicePipelines();

            $service = new LinkService($linkRepository, $pipelines);

            expect(
                fn() => $service->createLink("https://example.com", "", "Test"),
            )->toThrow(InvalidArgumentException::class);
        });
    });

    describe("updateLink method", function () {
        test("updates an existing link with all parameters", function () {
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->with($linkId)
                ->andReturn($link);
            $linkRepository->shouldReceive("update")->once();

            $pipelines = new LinkServicePipelines();

            $service = new LinkService($linkRepository, $pipelines);

            $result = $service->updateLink(
                $linkId,
                "https://updated.com",
                "Updated Title",
                "Updated Description",
                "updated-icon",
            );

            expect($result->url->value)->toBe("https://updated.com");
            expect($result->title->value)->toBe("Updated Title");
            expect($result->description)->toBe("Updated Description");
            expect($result->icon?->value)->toBe("updated-icon");
        });

        test("updates link without icon", function () {
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(
                id: $linkId,
                icon: new Icon("original-icon"),
            );

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->with($linkId)
                ->andReturn($link);
            $linkRepository->shouldReceive("update")->once();

            $pipelines = new LinkServicePipelines();

            $service = new LinkService($linkRepository, $pipelines);

            $result = $service->updateLink(
                $linkId,
                "https://updated.com",
                "Updated Title",
                "Updated Description",
                null,
            );

            expect($result->icon)->toBeNull();
        });

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $linkId = Uuid::uuid4();

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $linkRepository
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->andThrow(LinkNotFoundException::forId($linkId));

                $pipelines = new LinkServicePipelines();

                $service = new LinkService($linkRepository, $pipelines);

                expect(
                    fn() => $service->updateLink(
                        $linkId,
                        "https://example.com",
                        "Title",
                        "Desc",
                    ),
                )->toThrow(LinkNotFoundException::class);
            },
        );
    });

    describe("deleteLink method", function () {
        test("deletes an existing link", function () {
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->with($linkId)
                ->andReturn($link);
            $linkRepository->shouldReceive("delete")->with($link)->once();

            $pipelines = new LinkServicePipelines();

            $service = new LinkService($linkRepository, $pipelines);

            $service->deleteLink($linkId);

            expect(true)->toBeTrue(); // Mockery validates the delete was called
        });

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $linkId = Uuid::uuid4();

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $linkRepository
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->andThrow(LinkNotFoundException::forId($linkId));

                $pipelines = new LinkServicePipelines();

                $service = new LinkService($linkRepository, $pipelines);

                expect(fn() => $service->deleteLink($linkId))->toThrow(
                    LinkNotFoundException::class,
                );
            },
        );
    });

    describe("integration scenarios", function () {
        test(
            "full workflow: create, retrieve, update, and delete",
            function () {
                $linkId = Uuid::uuid4();
                $updatedLink = TestEntityFactory::createLink(
                    id: $linkId,
                    title: new Title("Updated Title"),
                );

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $linkRepository->shouldReceive("insert")->once();
                $linkRepository->shouldReceive("update")->once();
                $linkRepository
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->andReturn($updatedLink);
                $linkRepository->shouldReceive("delete")->once();

                $pipelines = new LinkServicePipelines();

                $service = new LinkService($linkRepository, $pipelines);

                // Create
                $created = $service->createLink(
                    "https://example.com",
                    "Test Title",
                    "Description",
                );
                expect($created)->toBeInstanceOf(Link::class);

                // Retrieve
                $retrieved = $service->getLink($linkId);
                expect($retrieved->title->value)->toBe("Updated Title");

                // Update
                $updated = $service->updateLink(
                    $linkId,
                    "https://updated.com",
                    "Updated Title",
                    "Updated Desc",
                );
                expect($updated)->toBeInstanceOf(Link::class);

                // Delete
                $service->deleteLink($linkId);

                expect(true)->toBeTrue();
            },
        );
    });
});
