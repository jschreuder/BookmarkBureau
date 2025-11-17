<?php

use jschreuder\BookmarkBureau\Entity\Value\HexColor;

describe("HexColor Value Object", function () {
    describe("valid hex colors", function () {
        test("creates a valid hex color with lowercase", function () {
            $color = new HexColor("#ff5733");

            expect($color->value)->toBe("#ff5733");
            expect((string) $color)->toBe("#ff5733");
        });

        test("creates a valid hex color with uppercase", function () {
            $color = new HexColor("#FF5733");

            expect($color->value)->toBe("#FF5733");
        });

        test("creates a valid hex color with mixed case", function () {
            $color = new HexColor("#FfAaBb");

            expect($color->value)->toBe("#FfAaBb");
        });

        test("creates a valid hex color with all zeros", function () {
            $color = new HexColor("#000000");

            expect($color->value)->toBe("#000000");
        });

        test("creates a valid hex color with all F", function () {
            $color = new HexColor("#FFFFFF");

            expect($color->value)->toBe("#FFFFFF");
        });

        test("creates a valid hex color with various values", function () {
            $testColors = [
                "#123456",
                "#abcdef",
                "#ABCDEF",
                "#00ff00",
                "#ff0000",
                "#0000ff",
            ];

            foreach ($testColors as $colorValue) {
                $color = new HexColor($colorValue);
                expect($color->value)->toBe($colorValue);
            }
        });
    });

    describe("invalid hex colors", function () {
        test("throws exception for empty string", function () {
            expect(fn() => new HexColor(""))->toThrow(
                InvalidArgumentException::class,
            );
        });

        test("throws exception for missing hash", function () {
            expect(fn() => new HexColor("FF5733"))->toThrow(
                InvalidArgumentException::class,
            );
        });

        test("throws exception for too short hex code", function () {
            expect(fn() => new HexColor("#FF57"))->toThrow(
                InvalidArgumentException::class,
            );
        });

        test("throws exception for too long hex code", function () {
            expect(fn() => new HexColor("#FF573399"))->toThrow(
                InvalidArgumentException::class,
            );
        });

        test("throws exception for non-hex characters", function () {
            expect(fn() => new HexColor("#GG5733"))->toThrow(
                InvalidArgumentException::class,
            );
        });

        test("throws exception for invalid characters after hash", function () {
            expect(fn() => new HexColor("#FF@733"))->toThrow(
                InvalidArgumentException::class,
            );
        });

        test("throws exception for spaces in hex code", function () {
            expect(fn() => new HexColor("#FF 733"))->toThrow(
                InvalidArgumentException::class,
            );
        });

        test("throws exception for plain text", function () {
            expect(fn() => new HexColor("red"))->toThrow(
                InvalidArgumentException::class,
            );
        });

        test(
            "throws exception with error message containing provided value",
            function () {
                $invalidColor = "not-a-color";

                expect(fn() => new HexColor($invalidColor))->toThrow(
                    InvalidArgumentException::class,
                    "HexColor Value object must be a valid HTML hex color (#RRGGBB), was given: " .
                        $invalidColor,
                );
            },
        );
    });

    describe("immutability", function () {
        test("HexColor value object is immutable", function () {
            $color = new HexColor("#FF5733");

            expect($color->value)->toBe("#FF5733");

            // The object should be readonly, attempting to modify should fail
            expect(fn() => ($color->value = "#000000"))->toThrow(Error::class);
        });
    });

    describe("string representation", function () {
        test("__toString method returns the hex color value", function () {
            $color = new HexColor("#FF5733");
            $stringColor = (string) $color;

            expect($stringColor)->toBe("#FF5733");
        });

        test("can be used in string context", function () {
            $color = new HexColor("#FF5733");
            $message = "The color is: " . $color;

            expect($message)->toBe("The color is: #FF5733");
        });
    });

    describe("equals method", function () {
        test("equals returns true for same hex color value", function () {
            $color1 = new HexColor("#FF5733");
            $color2 = new HexColor("#FF5733");

            expect($color1->equals($color2))->toBeTrue();
        });

        test(
            "equals returns false for different hex color values",
            function () {
                $color1 = new HexColor("#FF5733");
                $color2 = new HexColor("#FF5734");

                expect($color1->equals($color2))->toBeFalse();
            },
        );

        test("equals is case-sensitive for hex values", function () {
            $color1 = new HexColor("#FF5733");
            $color2 = new HexColor("#ff5733");

            expect($color1->equals($color2))->toBeFalse();
        });

        test(
            "equals returns false when comparing with different type",
            function () {
                $color = new HexColor("#FF5733");
                $stdObject = new stdClass();

                expect($color->equals($stdObject))->toBeFalse();
            },
        );

        test(
            "equals returns false when comparing with non-value object",
            function () {
                $color = new HexColor("#FF5733");
                $title = new \jschreuder\BookmarkBureau\Entity\Value\Title(
                    "My Color",
                );

                expect($color->equals($title))->toBeFalse();
            },
        );

        test("equals returns true for hex colors with all zeros", function () {
            $color1 = new HexColor("#000000");
            $color2 = new HexColor("#000000");

            expect($color1->equals($color2))->toBeTrue();
        });

        test("equals returns true for hex colors with all F", function () {
            $color1 = new HexColor("#FFFFFF");
            $color2 = new HexColor("#FFFFFF");

            expect($color1->equals($color2))->toBeTrue();
        });
    });
});
