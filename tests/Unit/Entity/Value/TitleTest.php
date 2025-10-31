<?php

use jschreuder\BookmarkBureau\Entity\Value\Title;

describe('Title Value Object', function () {
    describe('valid titles', function () {
        test('creates a valid title with simple text', function () {
            $title = new Title('My Bookmark');

            expect($title->value)->toBe('My Bookmark');
            expect((string) $title)->toBe('My Bookmark');
        });

        test('creates a valid title with special characters', function () {
            $title = new Title('JavaScript: The Good Parts');

            expect($title->value)->toBe('JavaScript: The Good Parts');
        });

        test('creates a valid title with numbers', function () {
            $title = new Title('PHP 8.3 Features');

            expect($title->value)->toBe('PHP 8.3 Features');
        });

        test('trims whitespace from input', function () {
            $title = new Title('  Trimmed Title  ');

            expect($title->value)->toBe('Trimmed Title');
        });

        test('creates a title at maximum length', function () {
            $maxLengthTitle = str_repeat('a', 255);
            $title = new Title($maxLengthTitle);

            expect($title->value)->toBe($maxLengthTitle);
        });

        test('creates valid titles with various characters', function () {
            $testTitles = [
                'Simple Title',
                'Title with (parentheses)',
                'Title with [brackets]',
                'Title with {braces}',
                'Title with quotes "example"',
                'Title with apostrophe\'s',
                'Title with dashes - and underscores_',
                'Title with dots...',
                'Title with commas, and semicolons;',
                'Title with question marks?',
                'Title with exclamation!',
                'Title with & ampersand',
                'Title with % percent',
                'Title with @ sign',
                'Title with # hashtag',
                'Title with $ dollar',
            ];

            foreach ($testTitles as $titleText) {
                $title = new Title($titleText);
                expect($title->value)->toBe($titleText);
            }
        });

        test('creates a title with single character', function () {
            $title = new Title('A');

            expect($title->value)->toBe('A');
        });

        test('creates a title with unicode characters', function () {
            $title = new Title('Café ☕');

            expect($title->value)->toBe('Café ☕');
        });

        test('creates a title with newlines trimmed', function () {
            $title = new Title("\n  Title with newlines  \n");

            expect($title->value)->toBe('Title with newlines');
        });

        test('creates a title with tabs trimmed', function () {
            $title = new Title("\t  Title with tabs  \t");

            expect($title->value)->toBe('Title with tabs');
        });
    });

    describe('invalid titles', function () {
        test('throws exception for empty string', function () {
            expect(fn() => new Title(''))
                ->toThrow(InvalidArgumentException::class, 'Title cannot be empty');
        });

        test('throws exception for whitespace only', function () {
            expect(fn() => new Title('   '))
                ->toThrow(InvalidArgumentException::class, 'Title cannot be empty');
        });

        test('throws exception for tabs only', function () {
            expect(fn() => new Title("\t\t\t"))
                ->toThrow(InvalidArgumentException::class, 'Title cannot be empty');
        });

        test('throws exception for newlines only', function () {
            expect(fn() => new Title("\n\n\n"))
                ->toThrow(InvalidArgumentException::class, 'Title cannot be empty');
        });

        test('throws exception for exceeding maximum length', function () {
            $tooLongTitle = str_repeat('a', 256);

            expect(fn() => new Title($tooLongTitle))
                ->toThrow(InvalidArgumentException::class, 'Title cannot exceed 255 characters');
        });

        test('throws exception for significantly exceeding maximum length', function () {
            $tooLongTitle = str_repeat('a', 1000);

            expect(fn() => new Title($tooLongTitle))
                ->toThrow(InvalidArgumentException::class, 'Title cannot exceed 255 characters');
        });

        test('throws exception for text exceeding 255 characters after trimming', function () {
            $tooLongTitle = '  ' . str_repeat('a', 256) . '  ';

            expect(fn() => new Title($tooLongTitle))
                ->toThrow(InvalidArgumentException::class, 'Title cannot exceed 255 characters');
        });
    });

    describe('immutability', function () {
        test('Title value object is immutable', function () {
            $title = new Title('Original Title');

            expect($title->value)->toBe('Original Title');

            // The object should be readonly, attempting to modify should fail
            expect(fn() => $title->value = 'Modified Title')
                ->toThrow(Error::class);
        });
    });

    describe('string representation', function () {
        test('__toString method returns the title value', function () {
            $title = new Title('Test Title');
            $stringTitle = (string) $title;

            expect($stringTitle)->toBe('Test Title');
        });

        test('can be used in string context', function () {
            $title = new Title('Bookmark Title');
            $message = 'The title is: ' . $title;

            expect($message)->toBe('The title is: Bookmark Title');
        });
    });
});
