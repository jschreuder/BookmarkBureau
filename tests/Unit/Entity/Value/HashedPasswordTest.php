<?php

use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;

describe("HashedPassword Value Object", function () {
    describe("construction", function () {
        test("creates a HashedPassword with valid hash", function () {
            $hash = password_hash("password123", PASSWORD_ARGON2ID);
            $hashedPassword = new HashedPassword($hash);

            expect($hashedPassword->value)->toBe($hash);
        });

        test("creates a HashedPassword with bcrypt hash", function () {
            $hash = password_hash("password123", PASSWORD_BCRYPT);
            $hashedPassword = new HashedPassword($hash);

            expect($hashedPassword->value)->toBe($hash);
        });

        test("accepts any non-empty string as hash", function () {
            // HashedPassword doesn't validate format - that's the hasher's job
            $customHash = '$argon2id$v=19$m=65536,t=4,p=1$custom';
            $hashedPassword = new HashedPassword($customHash);

            expect($hashedPassword->value)->toBe($customHash);
        });
    });

    describe("immutability", function () {
        test("HashedPassword value object is immutable", function () {
            $hash = password_hash("password123", PASSWORD_ARGON2ID);
            $hashedPassword = new HashedPassword($hash);

            expect($hashedPassword->value)->toBe($hash);

            // The object should be readonly, attempting to modify should fail
            expect(
                fn() => ($hashedPassword->value = "different-hash"),
            )->toThrow(Error::class);
        });
    });

    describe("string conversion", function () {
        test("converts to string via __toString", function () {
            $hash = password_hash("password123", PASSWORD_ARGON2ID);
            $hashedPassword = new HashedPassword($hash);

            expect((string) $hashedPassword)->toBe($hash);
        });
    });

    describe("encapsulation", function () {
        test("encapsulates password hash details", function () {
            $plaintext = "my-secret-password";
            $hash = password_hash($plaintext, PASSWORD_ARGON2ID);
            $hashedPassword = new HashedPassword($hash);

            // The value object stores the hash, not the plaintext
            expect($hashedPassword->value)->not->toContain($plaintext);
            expect($hashedPassword->value)->toStartWith('$argon2id$');
        });

        test("different plaintexts produce different hashes", function () {
            $hash1 = password_hash("password1", PASSWORD_ARGON2ID);
            $hash2 = password_hash("password2", PASSWORD_ARGON2ID);

            $hashedPassword1 = new HashedPassword($hash1);
            $hashedPassword2 = new HashedPassword($hash2);

            expect($hashedPassword1->value)->not->toBe($hashedPassword2->value);
        });
    });

    describe("equals method", function () {
        test("equals returns true for same hash value", function () {
            $hash = password_hash("password123", PASSWORD_ARGON2ID);
            $hashedPassword1 = new HashedPassword($hash);
            $hashedPassword2 = new HashedPassword($hash);

            expect($hashedPassword1->equals($hashedPassword2))->toBeTrue();
        });

        test("equals returns false for different hash values", function () {
            $hash1 = password_hash("password1", PASSWORD_ARGON2ID);
            $hash2 = password_hash("password2", PASSWORD_ARGON2ID);
            $hashedPassword1 = new HashedPassword($hash1);
            $hashedPassword2 = new HashedPassword($hash2);

            expect($hashedPassword1->equals($hashedPassword2))->toBeFalse();
        });

        test(
            "equals returns false when comparing with different type",
            function () {
                $hash = password_hash("password123", PASSWORD_ARGON2ID);
                $hashedPassword = new HashedPassword($hash);
                $stdObject = new stdClass();

                expect($hashedPassword->equals($stdObject))->toBeFalse();
            },
        );

        test(
            "equals returns false when comparing with non-value object",
            function () {
                $hash = password_hash("password123", PASSWORD_ARGON2ID);
                $hashedPassword = new HashedPassword($hash);
                $jwtToken = new \jschreuder\BookmarkBureau\Entity\Value\JwtToken(
                    "test.token",
                );

                expect($hashedPassword->equals($jwtToken))->toBeFalse();
            },
        );

        test(
            "equals comparison respects exact hash string matching",
            function () {
                // Different hashes from different passwords should not be equal
                $hash1 = password_hash("password1", PASSWORD_ARGON2ID);
                $hash2 = password_hash("password1", PASSWORD_ARGON2ID);
                // Note: Even though both hash "password1", they are different salt/hash combinations
                // So they will not be equal unless they are the exact same hash string
                $hashedPassword1 = new HashedPassword($hash1);
                $hashedPassword2 = new HashedPassword($hash2);

                // Since we created two separate hashes from the same password, they differ in salt
                expect($hashedPassword1->equals($hashedPassword2))->toBeFalse();
            },
        );
    });
});
