<?php

use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;

describe('TotpSecret Value Object', function () {
    describe('valid TOTP secrets', function () {
        test('creates a valid TOTP secret with 16 characters', function () {
            $secret = new TotpSecret('JBSWY3DPEHPK3PXP');

            expect($secret->getSecret())->toBe('JBSWY3DPEHPK3PXP');
        });

        test('creates a valid TOTP secret with 32 characters', function () {
            $secret = new TotpSecret('JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP');

            expect($secret->getSecret())->toBe('JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP');
        });

        test('creates a valid TOTP secret with mixed case', function () {
            $secret = new TotpSecret('JbSwY3DpEhPk3PxP');

            // Should normalize to uppercase
            expect($secret->getSecret())->toBe('JBSWY3DPEHPK3PXP');
        });

        test('creates a valid TOTP secret with lowercase', function () {
            $secret = new TotpSecret('jbswy3dpehpk3pxp');

            // Should normalize to uppercase
            expect($secret->getSecret())->toBe('JBSWY3DPEHPK3PXP');
        });

        test('accepts all valid Base32 characters', function () {
            // Valid Base32 alphabet: A-Z (26) and 2-7 (6) = 32 characters
            $secret = new TotpSecret('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567');

            expect($secret->getSecret())->toBe('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567');
        });

        test('accepts secrets longer than 32 characters', function () {
            $longSecret = str_repeat('JBSWY3DPEHPK3PXP', 4); // 64 characters
            $secret = new TotpSecret($longSecret);

            expect($secret->getSecret())->toBe($longSecret);
        });
    });

    describe('invalid TOTP secrets', function () {
        test('throws exception for empty string', function () {
            expect(fn() => new TotpSecret(''))
                ->toThrow(InvalidArgumentException::class, 'TOTP secret must be Base32 encoded (min 16 chars, alphabet A-Z2-7)');
        });

        test('throws exception for too short secret', function () {
            expect(fn() => new TotpSecret('JBSWY3DPEHPK3PX')) // 15 characters
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for invalid character 0', function () {
            expect(fn() => new TotpSecret('JBSWY3DPEHPK3PX0'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for invalid character 1', function () {
            expect(fn() => new TotpSecret('JBSWY3DPEHPK3PX1'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for invalid character 8', function () {
            expect(fn() => new TotpSecret('JBSWY3DPEHPK3PX8'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for invalid character 9', function () {
            expect(fn() => new TotpSecret('JBSWY3DPEHPK3PX9'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for special characters', function () {
            expect(fn() => new TotpSecret('JBSWY3DPEHPK3PX!'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for spaces', function () {
            expect(fn() => new TotpSecret('JBSWY3DP EHPK3PXP'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for padding characters', function () {
            expect(fn() => new TotpSecret('JBSWY3DPEHPK3PXP===='))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for hex characters not in Base32', function () {
            expect(fn() => new TotpSecret('ABCDEF0123456789')) // 0, 1, 8, 9 not valid
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('immutability', function () {
        test('TotpSecret value object is immutable', function () {
            $secret = new TotpSecret('JBSWY3DPEHPK3PXP');

            expect($secret->getSecret())->toBe('JBSWY3DPEHPK3PXP');

            // The object should be readonly, attempting to modify should fail
            expect(fn() => $secret->secret = 'DIFFERENTVALUE23')
                ->toThrow(Error::class);
        });
    });

    describe('normalization', function () {
        test('normalizes lowercase to uppercase', function () {
            $secret = new TotpSecret('jbswy3dpehpk3pxp');

            expect($secret->getSecret())->toBe('JBSWY3DPEHPK3PXP');
        });

        test('normalizes mixed case to uppercase', function () {
            $secret = new TotpSecret('JbSwY3DpEhPk3PxP');

            expect($secret->getSecret())->toBe('JBSWY3DPEHPK3PXP');
        });

        test('preserves already uppercase secrets', function () {
            $secret = new TotpSecret('JBSWY3DPEHPK3PXP');

            expect($secret->getSecret())->toBe('JBSWY3DPEHPK3PXP');
        });
    });

    describe('Base32 RFC 4648 compliance', function () {
        test('validates against RFC 4648 Base32 alphabet', function () {
            // RFC 4648 Base32 uses A-Z (uppercase) and 2-7
            // Excludes: 0, 1, 8, 9 (to avoid confusion with O, I, B, g)
            $validSecret = 'ABCDEFGHIJKLMNOP'; // 16 chars, all valid
            $secret = new TotpSecret($validSecret);

            expect($secret->getSecret())->toBe($validSecret);
        });

        test('minimum entropy requirement of 80 bits', function () {
            // 16 Base32 characters = 80 bits (16 * 5 bits per char)
            $minSecret = 'A234567B234567CD'; // Exactly 16 characters
            $secret = new TotpSecret($minSecret);

            expect(strlen($secret->getSecret()))->toBe(16);
        });
    });
});
