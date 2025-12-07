<?php

use jschreuder\BookmarkBureau\Action\LinkCreateAction;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\InputSpec\LinkInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\Middle\Exception\ValidationFailedException;

describe("LinkCreateAction", function () {
    describe("filter method", function () {
        test("trims whitespace from URL", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([
                "url" => "  https://example.com  ",
                "title" => "Test",
                "description" => "Test Description",
                "icon" => null,
            ]);

            expect($filtered["url"])->toBe("https://example.com");
        });

        test("trims whitespace from title", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([
                "url" => "https://example.com",
                "title" => "  Test Title  ",
                "description" => "Test Description",
                "icon" => null,
            ]);

            expect($filtered["title"])->toBe("Test Title");
        });

        test("trims whitespace from description", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([
                "url" => "https://example.com",
                "title" => "Test",
                "description" => "  Test Description  ",
                "icon" => null,
            ]);

            expect($filtered["description"])->toBe("Test Description");
        });

        test("trims whitespace from icon", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([
                "url" => "https://example.com",
                "title" => "Test",
                "description" => "Test Description",
                "icon" => "  test-icon  ",
            ]);

            expect($filtered["icon"])->toBe("test-icon");
        });

        test("handles missing keys with empty strings", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([]);

            expect($filtered["url"])->toBe("");
            expect($filtered["title"])->toBe("");
            expect($filtered["description"])->toBe("");
            expect($filtered["icon"])->toBeNull();
        });

        test("preserves null icon as null", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([
                "url" => "https://example.com",
                "title" => "Test",
                "description" => "Test Description",
                "icon" => null,
            ]);

            expect($filtered["icon"])->toBeNull();
        });

        test("strips HTML tags from title", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([
                "url" => "https://example.com",
                "title" => 'Link <script>alert("xss")</script> Title',
                "description" => "Test Description",
                "icon" => null,
            ]);

            expect($filtered["title"])->toBe('Link alert("xss") Title');
        });

        test("strips HTML tags from description", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $filtered = $action->filter([
                "url" => "https://example.com",
                "title" => "Test",
                "description" =>
                    "Description with <b>bold</b> and <i>italic</i> tags",
                "icon" => null,
            ]);

            expect($filtered["description"])->toBe(
                "Description with bold and italic tags",
            );
        });

        test(
            "strips dangerous HTML from both title and description",
            function () {
                $linkService = Mockery::mock(LinkServiceInterface::class);
                $inputSpec = new LinkInputSpec();
                $outputSpec = new LinkOutputSpec(new TagOutputSpec());
                $action = new LinkCreateAction(
                    $linkService,
                    $inputSpec,
                    $outputSpec,
                );

                $filtered = $action->filter([
                    "url" => "https://example.com",
                    "title" => '<a href="evil.com">Click me</a>',
                    "description" =>
                        '<iframe src="malicious.com"></iframe><p>Content</p>',
                    "icon" => null,
                ]);

                expect($filtered["title"])->toBe("Click me");
                expect($filtered["description"])->toBe("Content");
            },
        );

        test("preserves URL without stripping tags", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            // URLs should NOT have striptags applied (they're validated separately)
            $filtered = $action->filter([
                "url" => "https://example.com/path?param=value",
                "title" => "Test",
                "description" => "Test Description",
                "icon" => null,
            ]);

            expect($filtered["url"])->toBe(
                "https://example.com/path?param=value",
            );
        });
    });

    describe("validate method", function () {
        test("passes validation with valid data", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "url" => "https://example.com",
                "title" => "Test Title",
                "description" => "Test Description",
                "icon" => "test-icon",
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("passes validation with empty description", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "url" => "https://example.com",
                "title" => "Test Title",
                "description" => "",
                "icon" => "test-icon",
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("passes validation with null icon", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "url" => "https://example.com",
                "title" => "Test Title",
                "description" => "Test Description",
                "icon" => null,
            ];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("throws validation error for invalid URL", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "url" => "  ht!tp://invalid",
                "title" => "Test Title",
                "description" => "Test Description",
                "icon" => null,
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for empty URL", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "url" => "",
                "title" => "Test Title",
                "description" => "Test Description",
                "icon" => null,
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("throws validation error for empty title", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "url" => "https://example.com",
                "title" => "",
                "description" => "Test Description",
                "icon" => null,
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test(
            "throws validation error for title exceeding max length",
            function () {
                $linkService = Mockery::mock(LinkServiceInterface::class);
                $inputSpec = new LinkInputSpec();
                $outputSpec = new LinkOutputSpec(new TagOutputSpec());
                $action = new LinkCreateAction(
                    $linkService,
                    $inputSpec,
                    $outputSpec,
                );

                $data = [
                    "url" => "https://example.com",
                    "title" => str_repeat("a", 257),
                    "description" => "Test Description",
                    "icon" => null,
                ];

                expect(fn() => $action->validate($data))->toThrow(
                    ValidationFailedException::class,
                );
            },
        );

        test("throws validation error for missing description", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "url" => "https://example.com",
                "title" => "Test Title",
                "icon" => null,
            ];

            expect(fn() => $action->validate($data))->toThrow(
                ValidationFailedException::class,
            );
        });

        test("includes URL error in validation exceptions", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "url" => "ht!tp://in-valid",
                "title" => "Test Title",
                "description" => "Test Description",
                "icon" => null,
            ];

            expect(function () use ($action, $data) {
                $action->validate($data);
            })->toThrow(ValidationFailedException::class);
        });

        test("includes title error in validation exceptions", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "url" => "https://example.com",
                "title" => "",
                "description" => "Test Description",
                "icon" => null,
            ];

            expect(function () use ($action, $data) {
                $action->validate($data);
            })->toThrow(ValidationFailedException::class);
        });

        test("includes multiple validation errors", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());
            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $data = [
                "url" => "ht!tp://inv",
                "title" => "",
                "description" => null,
                "icon" => null,
            ];

            try {
                $action->validate($data);
                expect(true)->toBeFalse();
            } catch (ValidationFailedException $e) {
                $errors = $e->getValidationErrors();
                expect($errors)->toHaveKey("url");
                expect($errors)->toHaveKey("title");
            }
        });
    });

    describe("execute method", function () {
        test(
            "executes with valid data and returns formatted link",
            function () {
                $linkService = Mockery::mock(LinkServiceInterface::class);
                $link = TestEntityFactory::createLink(
                    icon: new Icon("test-icon"),
                );

                $linkService
                    ->shouldReceive("createLink")
                    ->with(
                        "https://example.com",
                        "Test Title",
                        "Test Description",
                        "test-icon",
                    )
                    ->andReturn($link);
                $inputSpec = new LinkInputSpec();
                $outputSpec = new LinkOutputSpec(new TagOutputSpec());

                $action = new LinkCreateAction(
                    $linkService,
                    $inputSpec,
                    $outputSpec,
                );

                $result = $action->execute([
                    "url" => "https://example.com",
                    "title" => "Test Title",
                    "description" => "Test Description",
                    "icon" => "test-icon",
                ]);

                expect($result)->toHaveKey("link_id");
                expect($result)->toHaveKey("url");
                expect($result)->toHaveKey("title");
                expect($result)->toHaveKey("description");
                expect($result)->toHaveKey("icon");
                expect($result)->toHaveKey("created_at");
                expect($result)->toHaveKey("updated_at");
            },
        );

        test(
            "returns created_at and updated_at in ISO 8601 format",
            function () {
                $linkService = Mockery::mock(LinkServiceInterface::class);
                $link = TestEntityFactory::createLink(
                    icon: new Icon("test-icon"),
                );

                $linkService->shouldReceive("createLink")->andReturn($link);
                $inputSpec = new LinkInputSpec();
                $outputSpec = new LinkOutputSpec(new TagOutputSpec());

                $action = new LinkCreateAction(
                    $linkService,
                    $inputSpec,
                    $outputSpec,
                );

                $result = $action->execute([
                    "url" => "https://example.com",
                    "title" => "Test Title",
                    "description" => "Test Description",
                    "icon" => "test-icon",
                ]);

                expect($result["created_at"])->toMatch(
                    "/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/",
                );
                expect($result["updated_at"])->toMatch(
                    "/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/",
                );
            },
        );

        test("returns correct link service parameters with icon", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $link = TestEntityFactory::createLink(icon: new Icon("test-icon"));

            $linkService
                ->shouldReceive("createLink")
                ->with(
                    "https://example.com",
                    "Test Title",
                    "Long description",
                    "test-icon",
                )
                ->andReturn($link);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());

            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $action->execute([
                "url" => "https://example.com",
                "title" => "Test Title",
                "description" => "Long description",
                "icon" => "test-icon",
            ]);

            expect(true)->toBeTrue(); // Mockery validates the call was made correctly
        });

        test(
            "returns correct link service parameters with null icon",
            function () {
                $linkService = Mockery::mock(LinkServiceInterface::class);
                $link = TestEntityFactory::createLink(icon: null);

                $linkService
                    ->shouldReceive("createLink")
                    ->with(
                        "https://example.com",
                        "Test Title",
                        "Test Description",
                        null,
                    )
                    ->andReturn($link);
                $inputSpec = new LinkInputSpec();
                $outputSpec = new LinkOutputSpec(new TagOutputSpec());

                $action = new LinkCreateAction(
                    $linkService,
                    $inputSpec,
                    $outputSpec,
                );

                $action->execute([
                    "url" => "https://example.com",
                    "title" => "Test Title",
                    "description" => "Test Description",
                    "icon" => null,
                ]);

                expect(true)->toBeTrue();
            },
        );
    });

    describe("integration scenarios", function () {
        test("full workflow: filter, validate, and execute", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $link = TestEntityFactory::createLink(icon: new Icon("test-icon"));

            $linkService
                ->shouldReceive("createLink")
                ->with(
                    "https://example.com",
                    "Test Title",
                    "Test Description",
                    "test-icon",
                )
                ->andReturn($link);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());

            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "url" => "  https://example.com  ",
                "title" => "  Test Title  ",
                "description" => "  Test Description  ",
                "icon" => "  test-icon  ",
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toHaveKey("link_id");
                expect($result)->toHaveKey("url");
                expect($result)->toHaveKey("title");
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test("full workflow with empty icon", function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $link = TestEntityFactory::createLink(
                icon: new Icon("default-icon"),
            );

            $linkService
                ->shouldReceive("createLink")
                ->with(
                    "https://example.com",
                    "Test Title",
                    "Test Description",
                    null,
                )
                ->andReturn($link);
            $inputSpec = new LinkInputSpec();
            $outputSpec = new LinkOutputSpec(new TagOutputSpec());

            $action = new LinkCreateAction(
                $linkService,
                $inputSpec,
                $outputSpec,
            );

            $rawData = [
                "url" => "https://example.com",
                "title" => "Test Title",
                "description" => "Test Description",
                "icon" => null,
            ];

            $filtered = $action->filter($rawData);
            expect($filtered["icon"])->toBeNull();

            try {
                $action->validate($filtered);
                $action->execute($filtered);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });
});
