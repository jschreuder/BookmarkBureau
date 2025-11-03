<?php

use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use Ramsey\Uuid\Uuid;

describe('User Entity', function () {
    describe('construction', function () {
        test('creates a user with all properties', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));
            $totpSecret = new TotpSecret('JBSWY3DPEHPK3PXP');

            $user = new User($userId, $email, $passwordHash, $totpSecret);

            expect($user->userId)->toBe($userId);
            expect($user->email)->toBe($email);
            expect($user->passwordHash->getHash())->toBe($passwordHash->getHash());
            expect($user->totpSecret)->toBe($totpSecret);
        });

        test('creates a user without TOTP', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);

            expect($user->userId)->toBe($userId);
            expect($user->email)->toBe($email);
            expect($user->passwordHash->getHash())->toBe($passwordHash->getHash());
            expect($user->totpSecret)->toBeNull();
        });
    });

    describe('changePassword method', function () {
        test('changes the password hash', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $oldPasswordHash = new HashedPassword(password_hash('old-password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $oldPasswordHash, null);

            $newPasswordHash = new HashedPassword(password_hash('new-password', PASSWORD_ARGON2ID));
            $user->changePassword($newPasswordHash);

            expect($user->passwordHash)->toBe($newPasswordHash);
            expect($user->passwordHash->getHash())->not->toBe($oldPasswordHash->getHash());
        });

        test('password change is intentional via method call', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);

            // Cannot change password directly due to private(set)
            expect(fn() => $user->passwordHash = new HashedPassword('direct-access'))
                ->toThrow(Error::class);
        });
    });

    describe('changeTotpSecret method', function () {
        test('enables TOTP when previously disabled', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);
            expect($user->totpSecret)->toBeNull();

            $totpSecret = new TotpSecret('JBSWY3DPEHPK3PXP');
            $user->changeTotpSecret($totpSecret);

            expect($user->totpSecret)->toBe($totpSecret);
            expect($user->requiresTotp())->toBeTrue();
        });

        test('changes TOTP secret when already enabled', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));
            $oldSecret = new TotpSecret('JBSWY3DPEHPK3PXP');

            $user = new User($userId, $email, $passwordHash, $oldSecret);

            $newSecret = new TotpSecret('ABCDEFGHIJKLMNOP');
            $user->changeTotpSecret($newSecret);

            expect($user->totpSecret)->toBe($newSecret);
            expect($user->totpSecret->getSecret())->not->toBe($oldSecret->getSecret());
        });

        test('TOTP secret change is intentional via method call', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);

            // Cannot change TOTP secret directly due to private(set)
            expect(fn() => $user->totpSecret = new TotpSecret('JBSWY3DPEHPK3PXP'))
                ->toThrow(Error::class);
        });
    });

    describe('disableTotp method', function () {
        test('disables TOTP authentication', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));
            $totpSecret = new TotpSecret('JBSWY3DPEHPK3PXP');

            $user = new User($userId, $email, $passwordHash, $totpSecret);
            expect($user->requiresTotp())->toBeTrue();

            $user->disableTotp();

            expect($user->totpSecret)->toBeNull();
            expect($user->requiresTotp())->toBeFalse();
        });

        test('disabling TOTP is safe when already disabled', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);
            expect($user->requiresTotp())->toBeFalse();

            $user->disableTotp();

            expect($user->totpSecret)->toBeNull();
            expect($user->requiresTotp())->toBeFalse();
        });

        test('TOTP disable is explicit and intentional', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));
            $totpSecret = new TotpSecret('JBSWY3DPEHPK3PXP');

            $user = new User($userId, $email, $passwordHash, $totpSecret);

            // Cannot set to null directly - must use disableTotp()
            expect(fn() => $user->totpSecret = null)
                ->toThrow(Error::class);
        });
    });

    describe('requiresTotp method', function () {
        test('returns true when TOTP is enabled', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));
            $totpSecret = new TotpSecret('JBSWY3DPEHPK3PXP');

            $user = new User($userId, $email, $passwordHash, $totpSecret);

            expect($user->requiresTotp())->toBeTrue();
        });

        test('returns false when TOTP is disabled', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);

            expect($user->requiresTotp())->toBeFalse();
        });

        test('returns false after disabling TOTP', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));
            $totpSecret = new TotpSecret('JBSWY3DPEHPK3PXP');

            $user = new User($userId, $email, $passwordHash, $totpSecret);
            $user->disableTotp();

            expect($user->requiresTotp())->toBeFalse();
        });
    });

    describe('email property', function () {
        test('email is publicly readable', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);

            expect($user->email)->toBe($email);
            expect($user->email->value)->toBe('user@example.com');
        });

        test('email cannot be changed directly', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);

            // Cannot change email directly due to private(set)
            expect(fn() => $user->email = new Email('other@example.com'))
                ->toThrow(Error::class);
        });
    });

    describe('userId property', function () {
        test('userId is publicly readable', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);

            expect($user->userId)->toBe($userId);
        });

        test('userId is immutable', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);

            // Cannot change userId due to readonly
            expect(fn() => $user->userId = Uuid::uuid4())
                ->toThrow(Error::class);
        });
    });

    describe('security properties', function () {
        test('passwordHash is publicly readable', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);

            expect($user->passwordHash)->toBe($passwordHash);
        });

        test('passwordHash can only be changed via changePassword', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));

            $user = new User($userId, $email, $passwordHash, null);

            // Direct assignment should fail
            expect(fn() => $user->passwordHash = new HashedPassword('new-hash'))
                ->toThrow(Error::class);
        });

        test('totpSecret is publicly readable', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));
            $totpSecret = new TotpSecret('JBSWY3DPEHPK3PXP');

            $user = new User($userId, $email, $passwordHash, $totpSecret);

            expect($user->totpSecret)->toBe($totpSecret);
        });

        test('totpSecret can only be changed via changeTotpSecret or disableTotp', function () {
            $userId = Uuid::uuid4();
            $email = new Email('user@example.com');
            $passwordHash = new HashedPassword(password_hash('password', PASSWORD_ARGON2ID));
            $totpSecret = new TotpSecret('JBSWY3DPEHPK3PXP');

            $user = new User($userId, $email, $passwordHash, $totpSecret);

            // Direct assignment should fail
            expect(fn() => $user->totpSecret = new TotpSecret('ABCDEFGHIJKLMNOP'))
                ->toThrow(Error::class);
        });
    });
});
