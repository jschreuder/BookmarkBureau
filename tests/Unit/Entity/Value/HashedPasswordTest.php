<?php

use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;

describe('HashedPassword Value Object', function () {
    describe('construction', function () {
        test('creates a HashedPassword with valid hash', function () {
            $hash = password_hash('password123', PASSWORD_ARGON2ID);
            $hashedPassword = new HashedPassword($hash);

            expect($hashedPassword->getHash())->toBe($hash);
        });

        test('creates a HashedPassword with bcrypt hash', function () {
            $hash = password_hash('password123', PASSWORD_BCRYPT);
            $hashedPassword = new HashedPassword($hash);

            expect($hashedPassword->getHash())->toBe($hash);
        });

        test('accepts any non-empty string as hash', function () {
            // HashedPassword doesn't validate format - that's the hasher's job
            $customHash = '$argon2id$v=19$m=65536,t=4,p=1$custom';
            $hashedPassword = new HashedPassword($customHash);

            expect($hashedPassword->getHash())->toBe($customHash);
        });
    });

    describe('immutability', function () {
        test('HashedPassword value object is immutable', function () {
            $hash = password_hash('password123', PASSWORD_ARGON2ID);
            $hashedPassword = new HashedPassword($hash);

            expect($hashedPassword->getHash())->toBe($hash);

            // The object should be readonly, attempting to modify should fail
            expect(fn() => $hashedPassword->hash = 'different-hash')
                ->toThrow(Error::class);
        });
    });

    describe('encapsulation', function () {
        test('encapsulates password hash details', function () {
            $plaintext = 'my-secret-password';
            $hash = password_hash($plaintext, PASSWORD_ARGON2ID);
            $hashedPassword = new HashedPassword($hash);

            // The value object stores the hash, not the plaintext
            expect($hashedPassword->getHash())->not->toContain($plaintext);
            expect($hashedPassword->getHash())->toStartWith('$argon2id$');
        });

        test('different plaintexts produce different hashes', function () {
            $hash1 = password_hash('password1', PASSWORD_ARGON2ID);
            $hash2 = password_hash('password2', PASSWORD_ARGON2ID);

            $hashedPassword1 = new HashedPassword($hash1);
            $hashedPassword2 = new HashedPassword($hash2);

            expect($hashedPassword1->getHash())->not->toBe($hashedPassword2->getHash());
        });
    });
});
