<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Service\OtphpTotpVerifier;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use OTPHP\TOTP;
use Psr\Clock\ClockInterface;

describe("OtphpTotpVerifier", function () {
    describe("verify", function () {
        test("should verify valid TOTP code", function () {
            $clock = Mockery::mock(ClockInterface::class);
            $now = new DateTimeImmutable();
            $clock->shouldReceive("now")->andReturn($now);
            $verifier = new OtphpTotpVerifier($clock);

            // Valid Base32 (A-Z, 2-7), no padding
            $secretString = "JBSWY3DPEBLW64TMMQ";
            $secret = new TotpSecret($secretString);

            // Create a TOTP instance with the same secret and clock to generate a valid code
            $totp = TOTP::create($secretString, clock: $clock);
            $validCode = $totp->now();

            $result = $verifier->verify($validCode, $secret);

            expect($result)->toBe(true);
        });

        test("should reject invalid TOTP code", function () {
            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn(new DateTimeImmutable());
            $verifier = new OtphpTotpVerifier($clock);

            $secretString = "JBSWY3DPEBLW64TMMQ";
            $secret = new TotpSecret($secretString);

            $result = $verifier->verify("000000", $secret);

            expect($result)->toBe(false);
        });

        test("should reject malformed code", function () {
            $clock = Mockery::mock(ClockInterface::class);
            $clock->shouldReceive("now")->andReturn(new DateTimeImmutable());
            $verifier = new OtphpTotpVerifier($clock);

            $secretString = "JBSWY3DPEBLW64TMMQ";
            $secret = new TotpSecret($secretString);

            $result = $verifier->verify("invalid", $secret);

            expect($result)->toBe(false);
        });

        test("should handle different valid Base32 secrets", function () {
            $clock = Mockery::mock(ClockInterface::class);
            $now = new DateTimeImmutable();
            $clock->shouldReceive("now")->andReturn($now);
            $verifier = new OtphpTotpVerifier($clock);

            // Valid Base32 (min 16 chars): ORSXG5A2JQ2XSVBJ
            $secretString = "ORSXG5A2JQ2XSVBJ";
            $secret = new TotpSecret($secretString);

            $totp = TOTP::create($secretString, clock: $clock);
            $validCode = $totp->now();

            $result = $verifier->verify($validCode, $secret);

            expect($result)->toBe(true);
        });

        test("should not verify code with wrong secret", function () {
            $clock = Mockery::mock(ClockInterface::class);
            $now = new DateTimeImmutable();
            $clock->shouldReceive("now")->andReturn($now);
            $verifier = new OtphpTotpVerifier($clock);

            $secretString1 = "JBSWY3DPEBLW64TMMQ";
            $secretString2 = "ORSXG5A2JQ2XSVBJ";
            $secret1 = new TotpSecret($secretString1);

            $totp = TOTP::create($secretString2, clock: $clock);
            $codeFromSecret2 = $totp->now();

            // Try to verify code from secret2 against secret1
            $result = $verifier->verify($codeFromSecret2, $secret1);

            expect($result)->toBe(false);
        });

        test("should accept configurable window parameter", function () {
            $clock = Mockery::mock(ClockInterface::class);
            $now = new DateTimeImmutable();
            $clock->shouldReceive("now")->andReturn($now);

            // Create verifier with custom window of 2
            $verifier = new OtphpTotpVerifier($clock, window: 2);

            $secretString = "JBSWY3DPEBLW64TMMQ";
            $secret = new TotpSecret($secretString);

            $totp = TOTP::create($secretString, clock: $clock);
            $validCode = $totp->now();

            $result = $verifier->verify($validCode, $secret);

            expect($result)->toBe(true);
        });

        test("should use default window of 1 when not specified", function () {
            $clock = Mockery::mock(ClockInterface::class);
            $now = new DateTimeImmutable();
            $clock->shouldReceive("now")->andReturn($now);

            // Create verifier without specifying window (should default to 1)
            $verifier = new OtphpTotpVerifier($clock);

            $secretString = "JBSWY3DPEBLW64TMMQ";
            $secret = new TotpSecret($secretString);

            $totp = TOTP::create($secretString, clock: $clock);
            $validCode = $totp->now();

            $result = $verifier->verify($validCode, $secret);

            expect($result)->toBe(true);
        });
    });
});
