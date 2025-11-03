<?php

use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Service\PhpPasswordHasher;

describe('PhpPasswordHasher', function () {
    describe('hash method', function () {
        test('hashes a plaintext password with default Argon2id', function () {
            $hasher = new PhpPasswordHasher();
            $plaintext = 'my-secure-password';

            $result = $hasher->hash($plaintext);

            expect($result)->toBeInstanceOf(HashedPassword::class);
            expect($result->getHash())->toStartWith('$argon2id$');
        });

        test('hashes a plaintext password with Argon2id', function () {
            $hasher = new PhpPasswordHasher(PASSWORD_ARGON2ID);
            $plaintext = 'my-secure-password';

            $result = $hasher->hash($plaintext);

            expect($result)->toBeInstanceOf(HashedPassword::class);
            expect($result->getHash())->toStartWith('$argon2id$');
        });

        test('hashes a plaintext password with bcrypt', function () {
            $hasher = new PhpPasswordHasher(PASSWORD_BCRYPT);
            $plaintext = 'my-secure-password';

            $result = $hasher->hash($plaintext);

            expect($result)->toBeInstanceOf(HashedPassword::class);
            expect($result->getHash())->toStartWith('$2y$');
        });

        test('produces different hashes for same password', function () {
            $hasher = new PhpPasswordHasher();
            $plaintext = 'my-secure-password';

            $hash1 = $hasher->hash($plaintext);
            $hash2 = $hasher->hash($plaintext);

            // Due to salt, hashes should be different
            expect($hash1->getHash())->not->toBe($hash2->getHash());
        });

        test('produces different hashes for different passwords', function () {
            $hasher = new PhpPasswordHasher();

            $hash1 = $hasher->hash('password1');
            $hash2 = $hasher->hash('password2');

            expect($hash1->getHash())->not->toBe($hash2->getHash());
        });

        test('handles empty password', function () {
            $hasher = new PhpPasswordHasher();

            $result = $hasher->hash('');

            expect($result)->toBeInstanceOf(HashedPassword::class);
            expect($result->getHash())->not->toBeEmpty();
        });

        test('handles long passwords', function () {
            $hasher = new PhpPasswordHasher();
            $longPassword = str_repeat('a', 1000);

            $result = $hasher->hash($longPassword);

            expect($result)->toBeInstanceOf(HashedPassword::class);
            expect($result->getHash())->not->toBeEmpty();
        });

        test('handles special characters in password', function () {
            $hasher = new PhpPasswordHasher();
            $plaintext = 'p@$$w0rd!#%&*()[]{}';

            $result = $hasher->hash($plaintext);

            expect($result)->toBeInstanceOf(HashedPassword::class);
            expect($result->getHash())->not->toBeEmpty();
        });

        test('handles unicode characters in password', function () {
            $hasher = new PhpPasswordHasher();
            $plaintext = 'пароль密码🔐';

            $result = $hasher->hash($plaintext);

            expect($result)->toBeInstanceOf(HashedPassword::class);
            expect($result->getHash())->not->toBeEmpty();
        });
    });

    describe('verify method', function () {
        test('verifies correct password against hash', function () {
            $hasher = new PhpPasswordHasher();
            $plaintext = 'my-secure-password';
            $hashedPassword = $hasher->hash($plaintext);

            $result = $hasher->verify($plaintext, $hashedPassword);

            expect($result)->toBeTrue();
        });

        test('rejects incorrect password against hash', function () {
            $hasher = new PhpPasswordHasher();
            $plaintext = 'my-secure-password';
            $hashedPassword = $hasher->hash($plaintext);

            $result = $hasher->verify('wrong-password', $hashedPassword);

            expect($result)->toBeFalse();
        });

        test('verifies password with Argon2id algorithm', function () {
            $hasher = new PhpPasswordHasher(PASSWORD_ARGON2ID);
            $plaintext = 'test-password';
            $hashedPassword = $hasher->hash($plaintext);

            $result = $hasher->verify($plaintext, $hashedPassword);

            expect($result)->toBeTrue();
        });

        test('verifies password with bcrypt algorithm', function () {
            $hasher = new PhpPasswordHasher(PASSWORD_BCRYPT);
            $plaintext = 'test-password';
            $hashedPassword = $hasher->hash($plaintext);

            $result = $hasher->verify($plaintext, $hashedPassword);

            expect($result)->toBeTrue();
        });

        test('is case sensitive', function () {
            $hasher = new PhpPasswordHasher();
            $plaintext = 'MyPassword';
            $hashedPassword = $hasher->hash($plaintext);

            $correctCase = $hasher->verify('MyPassword', $hashedPassword);
            $wrongCase = $hasher->verify('mypassword', $hashedPassword);

            expect($correctCase)->toBeTrue();
            expect($wrongCase)->toBeFalse();
        });

        test('verifies empty password', function () {
            $hasher = new PhpPasswordHasher();
            $hashedPassword = $hasher->hash('');

            $result = $hasher->verify('', $hashedPassword);

            expect($result)->toBeTrue();
        });

        test('rejects empty password against non-empty hash', function () {
            $hasher = new PhpPasswordHasher();
            $hashedPassword = $hasher->hash('non-empty');

            $result = $hasher->verify('', $hashedPassword);

            expect($result)->toBeFalse();
        });

        test('handles special characters correctly', function () {
            $hasher = new PhpPasswordHasher();
            $plaintext = 'p@$$w0rd!#%&*()[]{}';
            $hashedPassword = $hasher->hash($plaintext);

            $result = $hasher->verify($plaintext, $hashedPassword);

            expect($result)->toBeTrue();
        });

        test('handles unicode characters correctly', function () {
            $hasher = new PhpPasswordHasher();
            $plaintext = 'пароль密码🔐';
            $hashedPassword = $hasher->hash($plaintext);

            $result = $hasher->verify($plaintext, $hashedPassword);

            expect($result)->toBeTrue();
        });
    });

    describe('algorithm configuration', function () {
        test('can be configured with different algorithms', function () {
            $hasherArgon = new PhpPasswordHasher(PASSWORD_ARGON2ID);
            $hasherBcrypt = new PhpPasswordHasher(PASSWORD_BCRYPT);

            $plaintext = 'test-password';

            $hashArgon = $hasherArgon->hash($plaintext);
            $hashBcrypt = $hasherBcrypt->hash($plaintext);

            expect($hashArgon->getHash())->toStartWith('$argon2id$');
            expect($hashBcrypt->getHash())->toStartWith('$2y$');
        });

        test('verify works across different hasher instances', function () {
            $hasher1 = new PhpPasswordHasher();
            $hasher2 = new PhpPasswordHasher();

            $plaintext = 'test-password';
            $hashedPassword = $hasher1->hash($plaintext);

            // Different instance should still verify correctly
            $result = $hasher2->verify($plaintext, $hashedPassword);

            expect($result)->toBeTrue();
        });

        test('verify detects algorithm automatically from hash', function () {
            // Hash with Argon2id
            $hasherArgon = new PhpPasswordHasher(PASSWORD_ARGON2ID);
            $plaintext = 'test-password';
            $hashedPassword = $hasherArgon->hash($plaintext);

            // Verify with bcrypt-configured hasher (should still work)
            $hasherBcrypt = new PhpPasswordHasher(PASSWORD_BCRYPT);
            $result = $hasherBcrypt->verify($plaintext, $hashedPassword);

            expect($result)->toBeTrue();
        });
    });

    describe('integration with HashedPassword value object', function () {
        test('returns HashedPassword from hash method', function () {
            $hasher = new PhpPasswordHasher();

            $result = $hasher->hash('password');

            expect($result)->toBeInstanceOf(HashedPassword::class);
        });

        test('accepts HashedPassword in verify method', function () {
            $hasher = new PhpPasswordHasher();
            $plaintext = 'password';

            $hashedPassword = $hasher->hash($plaintext);

            expect($hashedPassword)->toBeInstanceOf(HashedPassword::class);
            expect($hasher->verify($plaintext, $hashedPassword))->toBeTrue();
        });
    });
});
