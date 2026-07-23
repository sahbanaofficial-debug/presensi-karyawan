<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

final class TotpService
{
    private const BASE32_ALPHABET =
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const DEFAULT_SECRET_BYTES = 20;

    private const MINIMUM_SECRET_BYTES = 16;

    private const MAXIMUM_SECRET_BYTES = 64;

    private string $algorithm;

    public function __construct(
        private readonly int $period = 30,
        private readonly int $digits = 6,
        string $algorithm = 'sha256'
    ) {
        if ($this->period < 15 || $this->period > 300) {
            throw new InvalidArgumentException(
                'Periode TOTP harus berada antara 15 dan 300 detik.'
            );
        }

        if ($this->digits < 6 || $this->digits > 8) {
            throw new InvalidArgumentException(
                'Jumlah digit TOTP harus berada antara 6 dan 8.'
            );
        }

        $normalizedAlgorithm = strtolower(
            trim($algorithm)
        );

        if (
            ! in_array(
                $normalizedAlgorithm,
                hash_hmac_algos(),
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Algoritma HMAC tidak didukung oleh PHP.'
            );
        }

        $this->algorithm = $normalizedAlgorithm;
    }

    /**
     * Membuat secret acak dan mengubahnya ke format Base32.
     */
    public function generateSecret(
        int $byteLength = self::DEFAULT_SECRET_BYTES
    ): string {
        if (
            $byteLength < self::MINIMUM_SECRET_BYTES
            || $byteLength > self::MAXIMUM_SECRET_BYTES
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Panjang secret harus berada antara %d dan %d byte.',
                    self::MINIMUM_SECRET_BYTES,
                    self::MAXIMUM_SECRET_BYTES
                )
            );
        }

        try {
            $randomBytes = random_bytes($byteLength);
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Secret TOTP tidak dapat dibuat.',
                0,
                $exception
            );
        }

        return $this->base32Encode($randomBytes);
    }

    /**
     * Membuat kode TOTP untuk waktu tertentu.
     *
     * Apabila timestamp tidak diberikan, waktu server
     * saat ini akan digunakan.
     */
    public function generateCode(
        string $secret,
        ?int $timestamp = null
    ): string {
        $timestamp ??= time();

        if ($timestamp < 0) {
            throw new InvalidArgumentException(
                'Timestamp TOTP tidak valid.'
            );
        }

        $decodedSecret = $this->base32Decode($secret);

        if ($decodedSecret === '') {
            throw new InvalidArgumentException(
                'Secret TOTP tidak boleh kosong.'
            );
        }

        $counter = intdiv(
            $timestamp,
            $this->period
        );

        $counterBytes = $this->counterToBytes(
            $counter
        );

        $hash = hash_hmac(
            $this->algorithm,
            $counterBytes,
            $decodedSecret,
            true
        );

        if ($hash === false || strlen($hash) < 20) {
            throw new RuntimeException(
                'Kode TOTP tidak dapat dibuat.'
            );
        }

        $offset = ord(
            $hash[strlen($hash) - 1]
        ) & 0x0F;

        if ($offset + 3 >= strlen($hash)) {
            throw new RuntimeException(
                'Hasil HMAC TOTP tidak valid.'
            );
        }

        $binaryCode =
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $modulo = 10 ** $this->digits;

        $code = (string) (
            $binaryCode % $modulo
        );

        return str_pad(
            $code,
            $this->digits,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Memvalidasi kode TOTP.
     *
     * Window 1 berarti kode pada satu periode sebelum dan
     * satu periode setelah waktu server masih diperiksa.
     */
    public function verifyCode(
        string $secret,
        string $code,
        ?int $timestamp = null,
        int $window = 1
    ): bool {
        $normalizedCode = trim($code);

        if (
            preg_match(
                '/^\d{'.$this->digits.'}$/',
                $normalizedCode
            ) !== 1
        ) {
            return false;
        }

        if ($window < 0 || $window > 5) {
            throw new InvalidArgumentException(
                'Window validasi TOTP harus berada antara 0 dan 5.'
            );
        }

        $timestamp ??= time();

        if ($timestamp < 0) {
            return false;
        }

        for (
            $offset = -$window;
            $offset <= $window;
            $offset++
        ) {
            $candidateTimestamp =
                $timestamp
                + ($offset * $this->period);

            if ($candidateTimestamp < 0) {
                continue;
            }

            $expectedCode = $this->generateCode(
                $secret,
                $candidateTimestamp
            );

            if (
                hash_equals(
                    $expectedCode,
                    $normalizedCode
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mengembalikan sisa masa berlaku kode saat ini.
     */
    public function secondsRemaining(
        ?int $timestamp = null
    ): int {
        $timestamp ??= time();

        if ($timestamp < 0) {
            throw new InvalidArgumentException(
                'Timestamp TOTP tidak valid.'
            );
        }

        $elapsedSeconds =
            $timestamp % $this->period;

        return $this->period - $elapsedSeconds;
    }

    /**
     * Mengembalikan periode TOTP dalam detik.
     */
    public function period(): int
    {
        return $this->period;
    }

    /**
     * Mengembalikan jumlah digit kode.
     */
    public function digits(): int
    {
        return $this->digits;
    }

    /**
     * Mengembalikan algoritma HMAC.
     */
    public function algorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * Mengubah counter TOTP menjadi integer 64 bit big endian.
     */
    private function counterToBytes(
        int $counter
    ): string {
        if (PHP_INT_SIZE < 8) {
            throw new RuntimeException(
                'TOTP memerlukan PHP 64 bit.'
            );
        }

        $divisor = 4_294_967_296;

        $high = intdiv(
            $counter,
            $divisor
        );

        $low = $counter % $divisor;

        return pack(
            'N2',
            $high,
            $low
        );
    }

    /**
     * Mengubah data biner menjadi Base32 tanpa padding.
     */
    private function base32Encode(
        string $binary
    ): string {
        if ($binary === '') {
            return '';
        }

        $buffer = 0;
        $bitsInBuffer = 0;
        $encoded = '';

        $length = strlen($binary);

        for ($index = 0; $index < $length; $index++) {
            $buffer =
                ($buffer << 8)
                | ord($binary[$index]);

            $bitsInBuffer += 8;

            while ($bitsInBuffer >= 5) {
                $bitsInBuffer -= 5;

                $alphabetIndex =
                    ($buffer >> $bitsInBuffer)
                    & 0x1F;

                $encoded .=
                    self::BASE32_ALPHABET[
                        $alphabetIndex
                    ];

                $buffer = $this->retainRemainingBits(
                    $buffer,
                    $bitsInBuffer
                );
            }
        }

        if ($bitsInBuffer > 0) {
            $alphabetIndex =
                ($buffer << (5 - $bitsInBuffer))
                & 0x1F;

            $encoded .=
                self::BASE32_ALPHABET[
                    $alphabetIndex
                ];
        }

        return $encoded;
    }

    /**
     * Mengubah Base32 menjadi data biner.
     */
    private function base32Decode(
        string $encoded
    ): string {
        $normalized = strtoupper(
            preg_replace(
                '/[\s=-]+/',
                '',
                trim($encoded)
            ) ?? ''
        );

        if ($normalized === '') {
            throw new InvalidArgumentException(
                'Secret TOTP tidak boleh kosong.'
            );
        }

        if (
            preg_match(
                '/[^A-Z2-7]/',
                $normalized
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'Secret TOTP bukan Base32 yang valid.'
            );
        }

        $buffer = 0;
        $bitsInBuffer = 0;
        $decoded = '';

        $length = strlen($normalized);

        for ($index = 0; $index < $length; $index++) {
            $alphabetIndex = strpos(
                self::BASE32_ALPHABET,
                $normalized[$index]
            );

            if ($alphabetIndex === false) {
                throw new InvalidArgumentException(
                    'Secret TOTP bukan Base32 yang valid.'
                );
            }

            $buffer =
                ($buffer << 5)
                | $alphabetIndex;

            $bitsInBuffer += 5;

            while ($bitsInBuffer >= 8) {
                $bitsInBuffer -= 8;

                $decoded .= chr(
                    ($buffer >> $bitsInBuffer)
                    & 0xFF
                );

                $buffer = $this->retainRemainingBits(
                    $buffer,
                    $bitsInBuffer
                );
            }
        }

        return $decoded;
    }

    /**
     * Mempertahankan bit yang belum diproses.
     */
    private function retainRemainingBits(
        int $buffer,
        int $bitCount
    ): int {
        if ($bitCount === 0) {
            return 0;
        }

        return $buffer
            & ((1 << $bitCount) - 1);
    }
}
