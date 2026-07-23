<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TotpService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TotpServiceTest extends TestCase
{
    private const SHA1_SECRET =
        'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    private const SHA256_SECRET =
        'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'
        .'GEZDGNBVGY3TQOJQGEZA';

    public function test_it_generates_base32_secret_with_expected_length(): void
    {
        $service = new TotpService;

        $secret = $service->generateSecret();

        $this->assertSame(
            32,
            strlen($secret)
        );

        $this->assertMatchesRegularExpression(
            '/^[A-Z2-7]+$/',
            $secret
        );
    }

    public function test_generated_secrets_are_random(): void
    {
        $service = new TotpService;

        $firstSecret = $service->generateSecret();
        $secondSecret = $service->generateSecret();

        $this->assertNotSame(
            $firstSecret,
            $secondSecret
        );
    }

    public function test_it_generates_six_digit_code_by_default(): void
    {
        $service = new TotpService;

        $code = $service->generateCode(
            self::SHA1_SECRET,
            1_700_000_000
        );

        $this->assertMatchesRegularExpression(
            '/^\d{6}$/',
            $code
        );
    }

    public function test_generated_code_can_be_verified(): void
    {
        $service = new TotpService;

        $timestamp = 1_700_000_000;

        $code = $service->generateCode(
            self::SHA1_SECRET,
            $timestamp
        );

        $this->assertTrue(
            $service->verifyCode(
                self::SHA1_SECRET,
                $code,
                $timestamp,
                0
            )
        );
    }

    public function test_previous_period_code_is_accepted_with_window_one(): void
    {
        $service = new TotpService;

        $timestamp = 1_700_000_000;

        $previousCode = $service->generateCode(
            self::SHA1_SECRET,
            $timestamp
        );

        $this->assertTrue(
            $service->verifyCode(
                self::SHA1_SECRET,
                $previousCode,
                $timestamp + 30,
                1
            )
        );
    }

    public function test_previous_period_code_is_rejected_with_window_zero(): void
    {
        $service = new TotpService;

        $timestamp = 1_700_000_000;

        $previousCode = $service->generateCode(
            self::SHA1_SECRET,
            $timestamp
        );

        $this->assertFalse(
            $service->verifyCode(
                self::SHA1_SECRET,
                $previousCode,
                $timestamp + 30,
                0
            )
        );
    }

    public function test_malformed_codes_are_rejected(): void
    {
        $service = new TotpService;

        foreach (
            [
                '',
                '12345',
                '1234567',
                'ABCDEF',
                '12 345',
            ] as $invalidCode
        ) {
            $this->assertFalse(
                $service->verifyCode(
                    self::SHA1_SECRET,
                    $invalidCode,
                    1_700_000_000
                )
            );
        }
    }

    public function test_seconds_remaining_uses_configured_period(): void
    {
        $service = new TotpService;

        $this->assertSame(
            30,
            $service->secondsRemaining(0)
        );

        $this->assertSame(
            1,
            $service->secondsRemaining(29)
        );

        $this->assertSame(
            30,
            $service->secondsRemaining(30)
        );

        $this->assertSame(
            15,
            $service->secondsRemaining(45)
        );
    }

    public function test_configuration_accessors_return_configured_values(): void
    {
        $service = new TotpService(
            period: 60,
            digits: 8,
            algorithm: 'sha1'
        );

        $this->assertSame(
            60,
            $service->period()
        );

        $this->assertSame(
            8,
            $service->digits()
        );

        $this->assertSame(
            'sha1',
            $service->algorithm()
        );
    }

    public function test_sha1_matches_rfc_totp_test_vector(): void
    {
        $service = new TotpService(
            period: 30,
            digits: 8,
            algorithm: 'sha1'
        );

        $this->assertSame(
            '94287082',
            $service->generateCode(
                self::SHA1_SECRET,
                59
            )
        );
    }

    public function test_sha256_matches_rfc_totp_test_vector(): void
    {
        $service = new TotpService(
            period: 30,
            digits: 8,
            algorithm: 'sha256'
        );

        $this->assertSame(
            '46119246',
            $service->generateCode(
                self::SHA256_SECRET,
                59
            )
        );
    }

    public function test_invalid_period_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new TotpService(
            period: 10
        );
    }

    public function test_invalid_digit_count_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new TotpService(
            digits: 5
        );
    }

    public function test_unsupported_algorithm_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new TotpService(
            algorithm: 'unsupported-algorithm'
        );
    }

    public function test_secret_length_below_minimum_is_rejected(): void
    {
        $service = new TotpService;

        $this->expectException(
            InvalidArgumentException::class
        );

        $service->generateSecret(15);
    }

    public function test_invalid_base32_secret_is_rejected(): void
    {
        $service = new TotpService;

        $this->expectException(
            InvalidArgumentException::class
        );

        $service->generateCode(
            'INVALID0SECRET',
            59
        );
    }

    public function test_invalid_verification_window_is_rejected(): void
    {
        $service = new TotpService;

        $this->expectException(
            InvalidArgumentException::class
        );

        $service->verifyCode(
            self::SHA1_SECRET,
            '123456',
            1_700_000_000,
            6
        );
    }
}
