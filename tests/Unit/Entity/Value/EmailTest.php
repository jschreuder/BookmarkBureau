<?php

use jschreuder\BookmarkBureau\Entity\Value\Email;

describe('Email Value Object', function () {
    describe('valid email addresses', function () {
        test('creates a valid email with standard format', function () {
            $email = new Email('user@example.com');

            expect($email->value)->toBe('user@example.com');
        });

        test('creates a valid email with subdomain', function () {
            $email = new Email('user@mail.example.com');

            expect($email->value)->toBe('user@mail.example.com');
        });

        test('creates a valid email with plus addressing', function () {
            $email = new Email('user+tag@example.com');

            expect($email->value)->toBe('user+tag@example.com');
        });

        test('creates a valid email with numbers', function () {
            $email = new Email('user123@example456.com');

            expect($email->value)->toBe('user123@example456.com');
        });

        test('creates a valid email with dots in local part', function () {
            $email = new Email('first.last@example.com');

            expect($email->value)->toBe('first.last@example.com');
        });

        test('creates a valid email with hyphen in domain', function () {
            $email = new Email('user@my-domain.com');

            expect($email->value)->toBe('user@my-domain.com');
        });

        test('creates a valid email with underscore in local part', function () {
            $email = new Email('user_name@example.com');

            expect($email->value)->toBe('user_name@example.com');
        });
    });

    describe('invalid email addresses', function () {
        test('throws exception for empty string', function () {
            expect(fn() => new Email(''))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for missing @', function () {
            expect(fn() => new Email('userexample.com'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for missing local part', function () {
            expect(fn() => new Email('@example.com'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for missing domain', function () {
            expect(fn() => new Email('user@'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for missing TLD', function () {
            expect(fn() => new Email('user@example'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for spaces', function () {
            expect(fn() => new Email('user name@example.com'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for multiple @ symbols', function () {
            expect(fn() => new Email('user@@example.com'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for invalid characters', function () {
            expect(fn() => new Email('user<>@example.com'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception with descriptive error message', function () {
            expect(fn() => new Email('invalid'))
                ->toThrow(InvalidArgumentException::class, 'Email Value object must get a valid e-mail address, was given: invalid');
        });
    });

    describe('immutability', function () {
        test('Email value object is immutable', function () {
            $email = new Email('user@example.com');

            expect($email->value)->toBe('user@example.com');

            // The object should be readonly, attempting to modify should fail
            expect(fn() => $email->value = 'other@example.com')
                ->toThrow(Error::class);
        });
    });

    describe('string representation', function () {
        test('__toString method returns the email value', function () {
            $email = new Email('user@example.com');
            $stringEmail = (string) $email;

            expect($stringEmail)->toBe('user@example.com');
        });

        test('can be used in string context', function () {
            $email = new Email('user@example.com');
            $message = 'Contact: ' . $email;

            expect($message)->toBe('Contact: user@example.com');
        });
    });
});
