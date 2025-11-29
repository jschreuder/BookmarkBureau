<?php

use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Exception\DuplicateTagException;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Exception\TagNotFoundException;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Repository\TagRepositoryInterface;
use jschreuder\BookmarkBureau\Service\TagService;
use jschreuder\BookmarkBureau\Service\TagServicePipelines;
use Ramsey\Uuid\Uuid;

describe("TagService", function () {
    describe("getTag method", function () {
        test("returns a tag by name", function () {
            $tag = TestEntityFactory::createTag(tagName: new TagName("my-tag"));

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("findByName")
                ->with("my-tag")
                ->once()
                ->andReturn($tag);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->getTag("my-tag");

            expect($result)->toBeInstanceOf(Tag::class);
            expect($result->tagName->value)->toBe("my-tag");
        });

        test(
            "throws TagNotFoundException when tag does not exist",
            function () {
                $tagRepository = Mockery::mock(TagRepositoryInterface::class);
                $tagRepository
                    ->shouldReceive("findByName")
                    ->with("nonexistent-tag")
                    ->once()
                    ->andThrow(TagNotFoundException::class);

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

                $service = new TagService(
                    $tagRepository,
                    $linkRepository,
                    new TagServicePipelines(),
                );

                expect(fn() => $service->getTag("nonexistent-tag"))->toThrow(
                    TagNotFoundException::class,
                );
            },
        );
    });

    describe("getAllTags method", function () {
        test("returns all tags from repository", function () {
            $tag1 = TestEntityFactory::createTag(tagName: new TagName("tag1"));
            $tag2 = TestEntityFactory::createTag(tagName: new TagName("tag2"));
            $tagCollection = new TagCollection($tag1, $tag2);

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("listAll")
                ->once()
                ->andReturn($tagCollection);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->getAllTags();

            expect($result)->toBeInstanceOf(TagCollection::class);
            expect(iterator_count($result))->toBe(2);
        });

        test("returns empty collection when no tags exist", function () {
            $tagCollection = new TagCollection();

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("listAll")
                ->once()
                ->andReturn($tagCollection);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->getAllTags();

            expect(iterator_count($result))->toBe(0);
        });
    });

    describe("getTagsForLink method", function () {
        test("returns tags for a specific link", function () {
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);
            $tag1 = TestEntityFactory::createTag(tagName: new TagName("tag1"));
            $tag2 = TestEntityFactory::createTag(tagName: new TagName("tag2"));
            $tagCollection = new TagCollection($tag1, $tag2);

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("listTagsForLinkId")
                ->with($linkId)
                ->once()
                ->andReturn($tagCollection);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->with($linkId)
                ->once()
                ->andReturn($link);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->getTagsForLink($linkId);

            expect($result)->toBeInstanceOf(TagCollection::class);
            expect(iterator_count($result))->toBe(2);
        });

        test("returns empty collection when link has no tags", function () {
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);
            $tagCollection = new TagCollection();

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("listTagsForLinkId")
                ->with($linkId)
                ->once()
                ->andReturn($tagCollection);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->with($linkId)
                ->once()
                ->andReturn($link);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->getTagsForLink($linkId);

            expect(iterator_count($result))->toBe(0);
        });

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $linkId = Uuid::uuid4();

                $tagRepository = Mockery::mock(TagRepositoryInterface::class);
                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $linkRepository
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->once()
                    ->andThrow(LinkNotFoundException::class);

                $service = new TagService(
                    $tagRepository,
                    $linkRepository,
                    new TagServicePipelines(),
                );

                expect(fn() => $service->getTagsForLink($linkId))->toThrow(
                    LinkNotFoundException::class,
                );
            },
        );
    });

    describe("createTag method", function () {
        test("creates a new tag with name and color", function () {
            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository->shouldReceive("insert")->once();

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->createTag("new-tag", "#FF5733");

            expect($result)->toBeInstanceOf(Tag::class);
            expect($result->tagName->value)->toBe("new-tag");
            expect($result->color?->value)->toBe("#FF5733");
        });

        test("creates a new tag without color", function () {
            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository->shouldReceive("insert")->once();

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->createTag("new-tag");

            expect($result->color)->toBeNull();
        });

        test(
            "throws DuplicateTagException when tag already exists",
            function () {
                $tagRepository = Mockery::mock(TagRepositoryInterface::class);
                $tagRepository
                    ->shouldReceive("insert")
                    ->once()
                    ->andThrow(DuplicateTagException::class);

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

                $service = new TagService(
                    $tagRepository,
                    $linkRepository,
                    new TagServicePipelines(),
                );

                expect(
                    fn() => $service->createTag("existing-tag", "#FF5733"),
                )->toThrow(DuplicateTagException::class);
            },
        );

        test(
            "throws InvalidArgumentException on invalid color format",
            function () {
                $tagRepository = Mockery::mock(TagRepositoryInterface::class);
                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

                $service = new TagService(
                    $tagRepository,
                    $linkRepository,
                    new TagServicePipelines(),
                );

                expect(
                    fn() => $service->createTag("new-tag", "invalid-color"),
                )->toThrow(InvalidArgumentException::class);
            },
        );
    });

    describe("updateTag method", function () {
        test("updates an existing tag with new color", function () {
            $tag = TestEntityFactory::createTag(tagName: new TagName("my-tag"));

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("findByName")
                ->with("my-tag")
                ->once()
                ->andReturn($tag);
            $tagRepository->shouldReceive("update")->once();

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->updateTag("my-tag", "#33FF57");

            expect($result->color?->value)->toBe("#33FF57");
        });

        test("updates tag by removing color", function () {
            $tag = TestEntityFactory::createTag(
                tagName: new TagName("my-tag"),
                color: new HexColor("#FF5733"),
            );

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("findByName")
                ->with("my-tag")
                ->once()
                ->andReturn($tag);
            $tagRepository->shouldReceive("update")->once();

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->updateTag("my-tag", null);

            expect($result->color)->toBeNull();
        });

        test(
            "throws TagNotFoundException when tag does not exist",
            function () {
                $tagRepository = Mockery::mock(TagRepositoryInterface::class);
                $tagRepository
                    ->shouldReceive("findByName")
                    ->with("nonexistent-tag")
                    ->once()
                    ->andThrow(TagNotFoundException::class);

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

                $service = new TagService(
                    $tagRepository,
                    $linkRepository,
                    new TagServicePipelines(),
                );

                expect(
                    fn() => $service->updateTag("nonexistent-tag", "#FF5733"),
                )->toThrow(TagNotFoundException::class);
            },
        );
    });

    describe("deleteTag method", function () {
        test("deletes an existing tag", function () {
            $tag = TestEntityFactory::createTag(tagName: new TagName("my-tag"));

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("findByName")
                ->with("my-tag")
                ->once()
                ->andReturn($tag);
            $tagRepository->shouldReceive("delete")->with($tag)->once();

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $service->deleteTag("my-tag");

            expect(true)->toBeTrue();
        });

        test(
            "throws TagNotFoundException when tag does not exist",
            function () {
                $tagRepository = Mockery::mock(TagRepositoryInterface::class);
                $tagRepository
                    ->shouldReceive("findByName")
                    ->with("nonexistent-tag")
                    ->once()
                    ->andThrow(TagNotFoundException::class);

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

                $service = new TagService(
                    $tagRepository,
                    $linkRepository,
                    new TagServicePipelines(),
                );

                expect(fn() => $service->deleteTag("nonexistent-tag"))->toThrow(
                    TagNotFoundException::class,
                );
            },
        );
    });

    describe("addTagToLink method", function () {
        test("assigns an existing tag to a link", function () {
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);
            $tag = TestEntityFactory::createTag(tagName: new TagName("my-tag"));

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("findByName")
                ->with("my-tag")
                ->once()
                ->andReturn($tag);
            $tagRepository
                ->shouldReceive("hasTagForLinkId")
                ->with($linkId, "my-tag")
                ->once()
                ->andReturn(false);
            $tagRepository
                ->shouldReceive("addTagToLinkId")
                ->with($linkId, "my-tag")
                ->once();

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->with($linkId)
                ->once()
                ->andReturn($link);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $service->addTagToLink($linkId, "my-tag");

            expect(true)->toBeTrue();
        });

        test("creates and assigns a new tag to a link", function () {
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("findByName")
                ->with("new-tag")
                ->once()
                ->andThrow(TagNotFoundException::class);
            $tagRepository->shouldReceive("insert")->once();
            $tagRepository
                ->shouldReceive("hasTagForLinkId")
                ->with($linkId, "new-tag")
                ->once()
                ->andReturn(false);
            $tagRepository
                ->shouldReceive("addTagToLinkId")
                ->with($linkId, "new-tag")
                ->once();

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->with($linkId)
                ->once()
                ->andReturn($link);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $service->addTagToLink($linkId, "new-tag");

            expect(true)->toBeTrue();
        });

        test("does not re-assign an already assigned tag", function () {
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);
            $tag = TestEntityFactory::createTag(tagName: new TagName("my-tag"));

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("findByName")
                ->with("my-tag")
                ->once()
                ->andReturn($tag);
            $tagRepository
                ->shouldReceive("hasTagForLinkId")
                ->with($linkId, "my-tag")
                ->once()
                ->andReturn(true);
            // addTagToLinkId should not be called
            $tagRepository->shouldNotReceive("addTagToLinkId");

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->with($linkId)
                ->once()
                ->andReturn($link);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $service->addTagToLink($linkId, "my-tag");

            expect(true)->toBeTrue();
        });

        test(
            "throws LinkNotFoundException when link does not exist",
            function () {
                $linkId = Uuid::uuid4();

                $tagRepository = Mockery::mock(TagRepositoryInterface::class);
                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $linkRepository
                    ->shouldReceive("findById")
                    ->with($linkId)
                    ->once()
                    ->andThrow(LinkNotFoundException::class);

                $service = new TagService(
                    $tagRepository,
                    $linkRepository,
                    new TagServicePipelines(),
                );

                expect(
                    fn() => $service->addTagToLink($linkId, "my-tag"),
                )->toThrow(LinkNotFoundException::class);
            },
        );
    });

    describe("removeTagFromLink method", function () {
        test("removes a tag from a link", function () {
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("removeTagFromLinkId")
                ->with($linkId, "my-tag")
                ->once();

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $linkRepository
                ->shouldReceive("findById")
                ->with($linkId)
                ->once()
                ->andReturn($link);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $service->removeTagFromLink($linkId, "my-tag");

            expect(true)->toBeTrue();
        });
    });

    describe("searchTags method", function () {
        test("searches tags by query with default limit", function () {
            $tag1 = TestEntityFactory::createTag(tagName: new TagName("api"));
            $tag2 = TestEntityFactory::createTag(tagName: new TagName("async"));
            $tagCollection = new TagCollection($tag1, $tag2);

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("listForNamePrefix")
                ->with("api", 20)
                ->once()
                ->andReturn($tagCollection);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->searchTags("api");

            expect($result)->toBeInstanceOf(TagCollection::class);
            expect(iterator_count($result))->toBe(2);
        });

        test("searches tags by query with custom limit", function () {
            $tag1 = TestEntityFactory::createTag(tagName: new TagName("api"));
            $tagCollection = new TagCollection($tag1);

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("listForNamePrefix")
                ->with("api", 5)
                ->once()
                ->andReturn($tagCollection);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->searchTags("api", 5);

            expect($result)->toBeInstanceOf(TagCollection::class);
            expect(iterator_count($result))->toBe(1);
        });

        test("returns empty collection when no tags match", function () {
            $tagCollection = new TagCollection();

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository
                ->shouldReceive("listForNamePrefix")
                ->with("xyz", 20)
                ->once()
                ->andReturn($tagCollection);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            $result = $service->searchTags("xyz");

            expect(iterator_count($result))->toBe(0);
        });
    });

    describe("integration scenarios", function () {
        test(
            "full workflow: create, assign, list, and delete tags",
            function () {
                $linkId = Uuid::uuid4();
                $link = TestEntityFactory::createLink(id: $linkId);
                $tag = TestEntityFactory::createTag(
                    tagName: new TagName("important"),
                );
                $tagCollection = new TagCollection($tag);

                $tagRepository = Mockery::mock(TagRepositoryInterface::class);
                // For create tag
                $tagRepository->shouldReceive("insert")->once();
                // For getTagsForLink
                $tagRepository
                    ->shouldReceive("listTagsForLinkId")
                    ->andReturn($tagCollection);
                // For addTagToLink
                $tagRepository->shouldReceive("findByName")->andReturn($tag);
                $tagRepository
                    ->shouldReceive("hasTagForLinkId")
                    ->andReturn(false);
                $tagRepository->shouldReceive("addTagToLinkId");
                // For getAllTags
                $tagRepository
                    ->shouldReceive("listAll")
                    ->andReturn($tagCollection);
                // For deleteTag
                $tagRepository->shouldReceive("delete");

                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $linkRepository->shouldReceive("findById")->andReturn($link);

                $service = new TagService(
                    $tagRepository,
                    $linkRepository,
                    new TagServicePipelines(),
                );

                // Create a tag
                $created = $service->createTag("important", "#FF5733");
                expect($created->tagName->value)->toBe("important");

                // Assign tag to link
                $service->addTagToLink($linkId, "important");

                // Get tags for link
                $linkTags = $service->getTagsForLink($linkId);
                expect(iterator_count($linkTags))->toBe(1);

                // List all tags (not transactional)
                $allTags = $service->getAllTags();
                expect(iterator_count($allTags))->toBe(1);

                // Delete tag
                $service->deleteTag("important");

                expect(true)->toBeTrue();
            },
        );

        test("multiple tags management workflow", function () {
            $tag1 = TestEntityFactory::createTag(tagName: new TagName("php"));
            $tag2 = TestEntityFactory::createTag(
                tagName: new TagName("laravel"),
            );
            $tagsCollection = new TagCollection($tag1, $tag2);
            $searchResult = new TagCollection($tag1);

            $tagRepository = Mockery::mock(TagRepositoryInterface::class);
            $tagRepository->shouldReceive("insert")->times(2);
            $tagRepository
                ->shouldReceive("listAll")
                ->andReturn($tagsCollection);
            $tagRepository
                ->shouldReceive("listForNamePrefix")
                ->with("php", 20)
                ->andReturn($searchResult);
            $tagRepository
                ->shouldReceive("findByName")
                ->with("php")
                ->andReturn($tag1);

            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);

            $service = new TagService(
                $tagRepository,
                $linkRepository,
                new TagServicePipelines(),
            );

            // Create multiple tags
            $created1 = $service->createTag("php", "#4F46E5");
            expect($created1->tagName->value)->toBe("php");

            $created2 = $service->createTag("fuelphp", "#FF2D20");
            expect($created2->tagName->value)->toBe("fuelphp");

            // List all tags (not transactional)
            $allTags = $service->getAllTags();
            expect(iterator_count($allTags))->toBe(2);

            // Search tags (not transactional)
            $searchResults = $service->searchTags("php");
            expect(iterator_count($searchResults))->toBe(1);

            expect(true)->toBeTrue();
        });
    });
});
