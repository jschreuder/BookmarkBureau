<?php

use jschreuder\BookmarkBureau\Action\CategoryLinkReorderAction;
use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\InputSpec\ReorderCategoryLinksInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe("CategoryLinkReorderAction", function () {
    describe("getAttributeKeysForData method", function () {
        test(
            "returns only category_id for reorder relation action",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $inputSpec = new ReorderCategoryLinksInputSpec();
                $outputSpec = new LinkOutputSpec(new TagOutputSpec());
                $action = new CategoryLinkReorderAction(
                    $categoryService,
                    $linkRepository,
                    $inputSpec,
                    $outputSpec,
                );

                expect($action->getAttributeKeysForData())->toBe([
                    "category_id",
                ]);
            },
        );
    });

    describe("filter method", function () {
        test("filters category_id and links", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $inputSpec = new ReorderCategoryLinksInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new CategoryLinkReorderAction(
                $categoryService,
                $linkRepository,
                $inputSpec,
                $outputSpec,
            );

            $categoryId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            $filtered = $action->filter([
                "category_id" => "  {$categoryId}  ",
                "links" => [["link_id" => "  {$linkId}  ", "sort_order" => 1]],
            ]);

            expect($filtered["category_id"])->toBe($categoryId);
            expect($filtered["links"][0]["link_id"])->toBe($linkId);
        });
    });

    describe("validate method", function () {
        test("passes validation with valid data", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $inputSpec = new ReorderCategoryLinksInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new CategoryLinkReorderAction(
                $categoryService,
                $linkRepository,
                $inputSpec,
                $outputSpec,
            );

            $categoryId = Uuid::uuid4()->toString();
            $linkId = Uuid::uuid4()->toString();

            try {
                $action->validate([
                    "category_id" => $categoryId,
                    "links" => [["link_id" => $linkId, "sort_order" => 1]],
                ]);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for empty links array", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $inputSpec = new ReorderCategoryLinksInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new CategoryLinkReorderAction(
                $categoryService,
                $linkRepository,
                $inputSpec,
                $outputSpec,
            );

            $categoryId = Uuid::uuid4()->toString();

            expect(function () use ($action, $categoryId) {
                $action->validate([
                    "category_id" => $categoryId,
                    "links" => [],
                ]);
            })->toThrow(ValidationFailedException::class);
        });
    });

    describe("execute method", function () {
        test(
            "calls categoryService.reorderLinksInCategory with correct parameters",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $link1 = TestEntityFactory::createLink();
                $link2 = TestEntityFactory::createLink();
                $collection = new LinkCollection($link1, $link2);

                $categoryId = Uuid::uuid4();
                $linkId1 = $link1->linkId->toString();
                $linkId2 = $link2->linkId->toString();

                $linkRepository
                    ->shouldReceive("listForCategoryId")
                    ->withAnyArgs()
                    ->andReturn($collection);

                $categoryService
                    ->shouldReceive("reorderLinksInCategory")
                    ->with(
                        Mockery::on(
                            fn($arg) => $arg->toString() ===
                                $categoryId->toString(),
                        ),
                        \Mockery::type(LinkCollection::class),
                    );

                $inputSpec = new ReorderCategoryLinksInputSpec();
                $outputSpec = new LinkOutputSpec(new TagOutputSpec());
                $action = new CategoryLinkReorderAction(
                    $categoryService,
                    $linkRepository,
                    $inputSpec,
                    $outputSpec,
                );

                $action->execute([
                    "category_id" => $categoryId->toString(),
                    "links" => [
                        ["link_id" => $linkId1, "sort_order" => 1],
                        ["link_id" => $linkId2, "sort_order" => 2],
                    ],
                ]);

                expect(true)->toBeTrue();
            },
        );

        test("returns array of transformed links", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $collection = new LinkCollection($link1, $link2);

            $linkRepository
                ->shouldReceive("listForCategoryId")
                ->andReturn($collection);

            $categoryService->shouldReceive("reorderLinksInCategory");

            $inputSpec = new ReorderCategoryLinksInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new CategoryLinkReorderAction(
                $categoryService,
                $linkRepository,
                $inputSpec,
                $outputSpec,
            );

            $categoryId = Uuid::uuid4()->toString();
            $result = $action->execute([
                "category_id" => $categoryId,
                "links" => [
                    [
                        "link_id" => $link1->linkId->toString(),
                        "sort_order" => 1,
                    ],
                    [
                        "link_id" => $link2->linkId->toString(),
                        "sort_order" => 2,
                    ],
                ],
            ]);

            expect($result["links"])->toHaveCount(2);
            expect($result["links"][0])->toHaveKey("link_id");
            expect($result["links"][0])->toHaveKey("url");
            expect($result["links"][0])->toHaveKey("title");
            expect($result["links"][1])->toHaveKey("link_id");
            expect($result["links"][1])->toHaveKey("url");
            expect($result["links"][1])->toHaveKey("title");
        });

        test("returns empty array when reordering empty list", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $collection = new LinkCollection();

            $linkRepository
                ->shouldReceive("listForCategoryId")
                ->andReturn($collection);

            $categoryService
                ->shouldReceive("reorderLinksInCategory")
                ->with(Mockery::any(), Mockery::type(LinkCollection::class));

            $inputSpec = new ReorderCategoryLinksInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new CategoryLinkReorderAction(
                $categoryService,
                $linkRepository,
                $inputSpec,
                $outputSpec,
            );

            $categoryId = Uuid::uuid4()->toString();

            $result = $action->execute([
                "category_id" => $categoryId,
                "links" => [],
            ]);

            expect($result)->toBe(["links" => []]);
        });

        test("transforms each link with correct order", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $link1 = TestEntityFactory::createLink(
                title: new Title("First Link"),
            );
            $link2 = TestEntityFactory::createLink(
                title: new Title("Second Link"),
            );
            $collection = new LinkCollection($link1, $link2);

            $linkRepository
                ->shouldReceive("listForCategoryId")
                ->andReturn($collection);

            $categoryService->shouldReceive("reorderLinksInCategory");

            $inputSpec = new ReorderCategoryLinksInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new CategoryLinkReorderAction(
                $categoryService,
                $linkRepository,
                $inputSpec,
                $outputSpec,
            );

            $categoryId = Uuid::uuid4()->toString();
            $result = $action->execute([
                "category_id" => $categoryId,
                "links" => [
                    [
                        "link_id" => $link1->linkId->toString(),
                        "sort_order" => 1,
                    ],
                    [
                        "link_id" => $link2->linkId->toString(),
                        "sort_order" => 2,
                    ],
                ],
            ]);

            expect($result["links"][0]["title"])->toBe("First Link");
            expect($result["links"][1]["title"])->toBe("Second Link");
        });

        test(
            "sorts links by sort_order regardless of input order",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $link1 = TestEntityFactory::createLink(
                    title: new Title("First Link"),
                );
                $link2 = TestEntityFactory::createLink(
                    title: new Title("Second Link"),
                );
                $link3 = TestEntityFactory::createLink(
                    title: new Title("Third Link"),
                );
                $collection = new LinkCollection($link1, $link2, $link3);

                $linkRepository
                    ->shouldReceive("listForCategoryId")
                    ->andReturn($collection);

                // Verify that the LinkCollection passed to reorderLinksInCategory is in correct order
                $categoryService->shouldReceive("reorderLinksInCategory")->with(
                    Mockery::any(),
                    Mockery::on(function (LinkCollection $links) use (
                        $link1,
                        $link2,
                        $link3,
                    ) {
                        $linksArray = iterator_to_array($links);
                        return count($linksArray) === 3 &&
                            $linksArray[0]->linkId->equals($link1->linkId) &&
                            $linksArray[1]->linkId->equals($link2->linkId) &&
                            $linksArray[2]->linkId->equals($link3->linkId);
                    }),
                );

                $inputSpec = new ReorderCategoryLinksInputSpec();
                $outputSpec = new LinkOutputSpec(new TagOutputSpec());
                $action = new CategoryLinkReorderAction(
                    $categoryService,
                    $linkRepository,
                    $inputSpec,
                    $outputSpec,
                );

                $categoryId = Uuid::uuid4()->toString();
                // Send links in REVERSE order but with correct sort_order values
                $result = $action->execute([
                    "category_id" => $categoryId,
                    "links" => [
                        [
                            "link_id" => $link3->linkId->toString(),
                            "sort_order" => 3,
                        ],
                        [
                            "link_id" => $link1->linkId->toString(),
                            "sort_order" => 1,
                        ],
                        [
                            "link_id" => $link2->linkId->toString(),
                            "sort_order" => 2,
                        ],
                    ],
                ]);

                // Verify the output is in the correct order (by sort_order, not input order)
                expect($result["links"][0]["title"])->toBe("First Link");
                expect($result["links"][1]["title"])->toBe("Second Link");
                expect($result["links"][2]["title"])->toBe("Third Link");
            },
        );

        test(
            "throws LinkNotFoundException for link not in category",
            function () {
                $categoryService = Mockery::mock(
                    CategoryServiceInterface::class,
                );
                $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
                $link1 = TestEntityFactory::createLink();
                $collection = new LinkCollection($link1);

                $linkRepository
                    ->shouldReceive("listForCategoryId")
                    ->andReturn($collection);

                $inputSpec = new ReorderCategoryLinksInputSpec();
                $outputSpec = new LinkOutputSpec(new TagOutputSpec());
                $action = new CategoryLinkReorderAction(
                    $categoryService,
                    $linkRepository,
                    $inputSpec,
                    $outputSpec,
                );

                $categoryId = Uuid::uuid4()->toString();
                $invalidLinkId = Uuid::uuid4()->toString();

                expect(function () use (
                    $action,
                    $categoryId,
                    $link1,
                    $invalidLinkId,
                ) {
                    $action->execute([
                        "category_id" => $categoryId,
                        "links" => [
                            [
                                "link_id" => $link1->linkId->toString(),
                                "sort_order" => 1,
                            ],
                            [
                                "link_id" => $invalidLinkId,
                                "sort_order" => 2,
                            ],
                        ],
                    ]);
                })->toThrow(LinkNotFoundException::class);
            },
        );
    });

    describe("integration scenarios", function () {
        test("full workflow: filter, validate, and execute", function () {
            $categoryService = Mockery::mock(CategoryServiceInterface::class);
            $linkRepository = Mockery::mock(LinkRepositoryInterface::class);
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();
            $collection = new LinkCollection($link1, $link2);

            $linkRepository
                ->shouldReceive("listForCategoryId")
                ->andReturn($collection);

            $categoryService->shouldReceive("reorderLinksInCategory");

            $inputSpec = new ReorderCategoryLinksInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new CategoryLinkReorderAction(
                $categoryService,
                $linkRepository,
                $inputSpec,
                $outputSpec,
            );

            $categoryId = Uuid::uuid4()->toString();
            $linkId1 = $link1->linkId->toString();
            $linkId2 = $link2->linkId->toString();

            $rawData = [
                "category_id" => "  {$categoryId}  ",
                "links" => [
                    ["link_id" => "  {$linkId1}  ", "sort_order" => 1],
                    ["link_id" => "  {$linkId2}  ", "sort_order" => 2],
                ],
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result["links"])->toHaveCount(2);
                expect($result["links"][0])->toHaveKey("link_id");
                expect($result["links"][1])->toHaveKey("link_id");
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });
});
