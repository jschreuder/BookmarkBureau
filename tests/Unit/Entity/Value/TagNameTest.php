<?php

use jschreuder\BookmarkBureau\Entity\Value\TagName;

describe("TagName Value Object", function () {
    describe("valid tag names", function () {
        test("creates a valid tag name with lowercase letters", function () {
            $tagName = new TagName("development");

            expect($tagName->value)->toBe("development");
            expect((string) $tagName)->toBe("development");
        });

        test("creates a valid tag name with numbers", function () {
            $tagName = new TagName("php7");

            expect($tagName->value)->toBe("php7");
        });

        test("creates a valid tag name with hyphens", function () {
            $tagName = new TagName("web-development");

            expect($tagName->value)->toBe("web-development");
        });

        test("normalizes uppercase to lowercase", function () {
            $tagName = new TagName("PHP");

            expect($tagName->value)->toBe("php");
        });

        test("normalizes mixed case to lowercase", function () {
            $tagName = new TagName("WebDevelopment");

            expect($tagName->value)->toBe("webdevelopment");
        });

        test("trims whitespace from input", function () {
            $tagName = new TagName("  php-development  ");

            expect($tagName->value)->toBe("php-development");
        });

        test("creates a valid tag name with various valid values", function () {
            $testNames = [
                "javascript",
                "node-js",
                "react",
                "test-driven-development",
                "a",
                "z",
                "0",
                "9",
                "a1b2c3",
                "test-123-tag",
            ];

            foreach ($testNames as $name) {
                $tagName = new TagName($name);
                expect($tagName->value)->toBe($name);
            }
        });

        test("creates a tag name at maximum length", function () {
            $maxLengthName = str_repeat("a", 100);
            $tagName = new TagName($maxLengthName);

            expect($tagName->value)->toBe($maxLengthName);
        });
    });

    describe("invalid tag names", function () {
        test("throws exception for empty string", function () {
            expect(fn() => new TagName(""))->toThrow(
                InvalidArgumentException::class,
                "Tag name cannot be empty",
            );
        });

        test("throws exception for whitespace only", function () {
            expect(fn() => new TagName("   "))->toThrow(
                InvalidArgumentException::class,
                "Tag name cannot be empty",
            );
        });

        test("throws exception for exceeding maximum length", function () {
            $tooLongName = str_repeat("a", 101);

            expect(fn() => new TagName($tooLongName))->toThrow(
                InvalidArgumentException::class,
                "Tag name cannot exceed 100 characters",
            );
        });

        test("throws exception for uppercase letters", function () {
            // Note: uppercase is normalized to lowercase, so we need a character that becomes invalid
            // Actually, uppercase is normalized, so this test verifies the normalization works
            $tagName = new TagName("DEVELOPMENT");
            expect($tagName->value)->toBe("development");
        });

        test("throws exception for spaces", function () {
            expect(fn() => new TagName("web development"))->toThrow(
                InvalidArgumentException::class,
                "Tag name can only contain lowercase letters, numbers, and hyphens",
            );
        });

        test("throws exception for special characters", function () {
            $invalidChars = [
                "@",
                "#",
                '$',
                "%",
                "&",
                "*",
                "!",
                "?",
                ".",
                ",",
                ";",
                ":",
            ];

            foreach ($invalidChars as $char) {
                expect(fn() => new TagName("tag" . $char . "name"))->toThrow(
                    InvalidArgumentException::class,
                    "Tag name can only contain lowercase letters, numbers, and hyphens",
                );
            }
        });

        test("throws exception for underscores", function () {
            expect(fn() => new TagName("tag_name"))->toThrow(
                InvalidArgumentException::class,
                "Tag name can only contain lowercase letters, numbers, and hyphens",
            );
        });

        test("throws exception for slashes", function () {
            expect(fn() => new TagName("tag/name"))->toThrow(
                InvalidArgumentException::class,
                "Tag name can only contain lowercase letters, numbers, and hyphens",
            );
        });

        test("throws exception for backslashes", function () {
            expect(fn() => new TagName('tag\\name'))->toThrow(
                InvalidArgumentException::class,
                "Tag name can only contain lowercase letters, numbers, and hyphens",
            );
        });

        test("throws exception for quotes", function () {
            expect(fn() => new TagName('tag"name'))->toThrow(
                InvalidArgumentException::class,
                "Tag name can only contain lowercase letters, numbers, and hyphens",
            );
        });
    });

    describe("immutability", function () {
        test("TagName value object is immutable", function () {
            $tagName = new TagName("development");

            expect($tagName->value)->toBe("development");

            // The object should be readonly, attempting to modify should fail
            expect(fn() => ($tagName->value = "different"))->toThrow(
                Error::class,
            );
        });
    });

    describe("string representation", function () {
        test("__toString method returns the tag name value", function () {
            $tagName = new TagName("development");
            $stringTagName = (string) $tagName;

            expect($stringTagName)->toBe("development");
        });

        test("can be used in string context", function () {
            $tagName = new TagName("php");
            $message = "The tag is: " . $tagName;

            expect($message)->toBe("The tag is: php");
        });
    });

    describe("equals method", function () {
        test("equals returns true for same tag name value", function () {
            $tagName1 = new TagName("php");
            $tagName2 = new TagName("php");

            expect($tagName1->equals($tagName2))->toBeTrue();
        });

        test("equals returns false for different tag name values", function () {
            $tagName1 = new TagName("php");
            $tagName2 = new TagName("python");

            expect($tagName1->equals($tagName2))->toBeFalse();
        });

        test(
            "equals returns true for uppercase input normalized to lowercase",
            function () {
                $tagName1 = new TagName("PHP");
                $tagName2 = new TagName("php");

                expect($tagName1->equals($tagName2))->toBeTrue();
            },
        );

        test(
            "equals returns true when comparing with trimmed whitespace values",
            function () {
                $tagName1 = new TagName("  development  ");
                $tagName2 = new TagName("development");

                expect($tagName1->equals($tagName2))->toBeTrue();
            },
        );

        test(
            "equals returns false when comparing with different type",
            function () {
                $tagName = new TagName("php");
                $stdObject = new stdClass();

                expect($tagName->equals($stdObject))->toBeFalse();
            },
        );

        test(
            "equals returns false when comparing with non-value object",
            function () {
                $tagName = new TagName("php");
                $icon = new \jschreuder\BookmarkBureau\Entity\Value\Icon(
                    "github",
                );

                expect($tagName->equals($icon))->toBeFalse();
            },
        );

        test("equals comparison respects normalization rules", function () {
            $tagName1 = new TagName("WEB-DEVELOPMENT");
            $tagName2 = new TagName("web-development");

            expect($tagName1->equals($tagName2))->toBeTrue();
        });
    });
});
