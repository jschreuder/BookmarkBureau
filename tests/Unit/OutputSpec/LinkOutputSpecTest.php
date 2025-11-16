<?php

use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TagOutputSpec;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;

describe("LinkOutputSpec", function () {
    // Helper to create a LinkOutputSpec with all dependencies
    $createSpec = fn() => new LinkOutputSpec(new TagOutputSpec());

    describe("initialization", function () use ($createSpec) {
        test("creates OutputSpec instance", function () use ($createSpec) {
            $spec = $createSpec();

            expect($spec)->toBeInstanceOf(LinkOutputSpec::class);
        });

        test("implements OutputSpecInterface", function () use ($createSpec) {
            $spec = $createSpec();

            expect($spec)->toBeInstanceOf(OutputSpecInterface::class);
        });

        test("is readonly", function () use ($createSpec) {
            $spec = $createSpec();

            expect($spec)->toBeInstanceOf(LinkOutputSpec::class);
        });
    });

    describe("supports method", function () use ($createSpec) {
        test("supports Link objects", function () use ($createSpec) {
            $spec = $createSpec();
            $link = TestEntityFactory::createLink();

            expect($spec->supports($link))->toBeTrue();
        });

        test("does not support Category objects", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $category = TestEntityFactory::createCategory();

            expect($spec->supports($category))->toBeFalse();
        });

        test("does not support stdClass objects", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();

            expect($spec->supports(new stdClass()))->toBeFalse();
        });
    });

    describe("transform method", function () use ($createSpec) {
        test("transforms Link to array with all fields", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $link = TestEntityFactory::createLink();

            $result = $spec->transform($link);

            expect($result)->toBeArray();
            expect($result)->toHaveKeys([
                "id",
                "url",
                "title",
                "description",
                "icon",
                "created_at",
                "updated_at",
                "tags",
            ]);
        });

        test("returns link ID as string", function () use ($createSpec) {
            $spec = $createSpec();
            $link = TestEntityFactory::createLink();

            $result = $spec->transform($link);

            expect($result["id"])->toBeString();
            expect($result["id"])->toBe($link->linkId->toString());
        });

        test("returns link URL as string", function () use ($createSpec) {
            $spec = $createSpec();
            $url = new Url("https://example.com/path?query=value");
            $link = TestEntityFactory::createLink(url: $url);

            $result = $spec->transform($link);

            expect($result["url"])->toBeString();
            expect($result["url"])->toBe(
                "https://example.com/path?query=value",
            );
        });

        test("returns link title as string", function () use ($createSpec) {
            $spec = $createSpec();
            $title = new Title("Example Website");
            $link = TestEntityFactory::createLink(title: $title);

            $result = $spec->transform($link);

            expect($result["title"])->toBeString();
            expect($result["title"])->toBe("Example Website");
        });

        test("returns link description as string", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $link = TestEntityFactory::createLink(
                description: "This is a great example website",
            );

            $result = $spec->transform($link);

            expect($result["description"])->toBeString();
            expect($result["description"])->toBe(
                "This is a great example website",
            );
        });

        test("returns link icon as string when present", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $icon = new Icon("link-icon");
            $link = TestEntityFactory::createLink(icon: $icon);

            $result = $spec->transform($link);

            expect($result["icon"])->toBeString();
            expect($result["icon"])->toBe("link-icon");
        });

        test("returns link icon as null when not present", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $link = TestEntityFactory::createLink(icon: null);

            $result = $spec->transform($link);

            expect($result["icon"])->toBeNull();
        });

        test("returns created_at in ATOM format", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $createdAt = new DateTimeImmutable(
                "2024-02-10 10:00:00",
                new DateTimeZone("UTC"),
            );
            $link = TestEntityFactory::createLink(createdAt: $createdAt);

            $result = $spec->transform($link);

            expect($result["created_at"])->toBeString();
            expect($result["created_at"])->toBe(
                $createdAt->format(DateTimeInterface::ATOM),
            );
        });

        test("returns updated_at in ATOM format", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $updatedAt = new DateTimeImmutable(
                "2024-02-10 11:30:00",
                new DateTimeZone("UTC"),
            );
            $link = TestEntityFactory::createLink(updatedAt: $updatedAt);

            $result = $spec->transform($link);

            expect($result["updated_at"])->toBeString();
            expect($result["updated_at"])->toBe(
                $updatedAt->format(DateTimeInterface::ATOM),
            );
        });

        test("returns empty tags array when link has no tags", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $link = TestEntityFactory::createLink();

            $result = $spec->transform($link);

            expect($result["tags"])->toBeArray();
            expect($result["tags"])->toHaveCount(0);
        });

        test(
            "throws InvalidArgumentException when transforming unsupported object",
            function () use ($createSpec) {
                $spec = $createSpec();
                $category = TestEntityFactory::createCategory();

                expect(fn() => $spec->transform($category))->toThrow(
                    InvalidArgumentException::class,
                );
            },
        );

        test(
            "exception message contains class name and unsupported type",
            function () use ($createSpec) {
                $spec = $createSpec();
                $category = TestEntityFactory::createCategory();

                expect(fn() => $spec->transform($category))
                    ->toThrow(InvalidArgumentException::class)
                    ->and(fn() => $spec->transform($category))
                    ->toThrow(function (InvalidArgumentException $e) {
                        return str_contains(
                            $e->getMessage(),
                            LinkOutputSpec::class,
                        ) && str_contains($e->getMessage(), Category::class);
                    });
            },
        );
    });

    describe("edge cases", function () use ($createSpec) {
        test("handles link with long URL", function () use ($createSpec) {
            $spec = $createSpec();
            $longUrl =
                "https://example.com/" . str_repeat("a", 500) . "?param=value";
            $url = new Url($longUrl);
            $link = TestEntityFactory::createLink(url: $url);

            $result = $spec->transform($link);

            expect($result["url"])->toBe($longUrl);
        });

        test("handles link with long description", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $longDescription = str_repeat("This is a long description. ", 100);
            $link = TestEntityFactory::createLink(
                description: $longDescription,
            );

            $result = $spec->transform($link);

            expect($result["description"])->toBe($longDescription);
        });

        test(
            "handles link with special characters in description",
            function () use ($createSpec) {
                $spec = $createSpec();
                $description =
                    'Link with "quotes", \'apostrophes\', & ampersand, newlines\nand unicode: 日本語';
                $link = TestEntityFactory::createLink(
                    description: $description,
                );

                $result = $spec->transform($link);

                expect($result["description"])->toBe($description);
            },
        );

        test("handles link with empty string description", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $link = TestEntityFactory::createLink(description: "");

            $result = $spec->transform($link);

            expect($result["description"])->toBe("");
        });

        test("handles link with URL special characters", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $url = new Url(
                "https://example.com/path?param1=value1&param2=value%20with%20spaces#anchor",
            );
            $link = TestEntityFactory::createLink(url: $url);

            $result = $spec->transform($link);

            expect($result["url"])->toBe(
                "https://example.com/path?param1=value1&param2=value%20with%20spaces#anchor",
            );
        });

        test("handles multiple links independently", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();

            $result1 = $spec->transform($link1);
            $result2 = $spec->transform($link2);

            expect($result1["id"])->not->toBe($result2["id"]);
            expect($result1["id"])->toBe($link1->linkId->toString());
            expect($result2["id"])->toBe($link2->linkId->toString());
        });

        test("handles link with different datetime zones", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $createdAt = new DateTimeImmutable(
                "2024-02-10 10:00:00",
                new DateTimeZone("Europe/Amsterdam"),
            );
            $link = TestEntityFactory::createLink(createdAt: $createdAt);

            $result = $spec->transform($link);

            expect($result["created_at"])->toBe(
                $createdAt->format(DateTimeInterface::ATOM),
            );
        });
    });

    describe("integration with OutputSpecInterface", function () use (
        $createSpec,
    ) {
        test("transform method signature matches interface", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $link = TestEntityFactory::createLink();

            $result = $spec->transform($link);

            expect($result)->toBeArray();
        });

        test("can be used polymorphically through interface", function () use (
            $createSpec,
        ) {
            $spec = $createSpec();
            $interface = $spec;
            $link = TestEntityFactory::createLink();

            expect($interface)->toBeInstanceOf(OutputSpecInterface::class);

            $result = $interface->transform($link);

            expect($result)->toBeArray();
        });
    });
});
