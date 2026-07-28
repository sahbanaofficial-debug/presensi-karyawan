<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BranchTerminalLifecycleException;
use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class BranchTerminalLifecycleService
{
    private const ACTIVATION_CODE_DIGITS = 8;

    private const ACTIVATION_LIFETIME_MINUTES = 15;

    private const DEVICE_TOKEN_BYTES = 32;

    /**
     * @return array{
     *     terminal: BranchTerminal,
     *     activation_code: string
     * }
     */
    public function register(
        Branch $branch,
        User $creator,
        string $name
    ): array {
        $normalizedName = $this->normalizeName(
            $name
        );

        return DB::transaction(
            function () use (
                $branch,
                $creator,
                $normalizedName
            ): array {
                $lockedBranch = Branch::query()
                    ->lockForUpdate()
                    ->find($branch->getKey());

                if ($lockedBranch === null) {
                    throw new BranchTerminalLifecycleException(
                        'branch_not_found',
                        'Cabang terminal tidak ditemukan.'
                    );
                }

                if (
                    ! $lockedBranch->isActive()
                    || ! $lockedBranch
                        ->hasGeofenceConfiguration()
                ) {
                    throw new BranchTerminalLifecycleException(
                        'branch_unavailable',
                        'Cabang harus aktif dan memiliki konfigurasi geofence lengkap.'
                    );
                }

                $activationCode =
                    $this->generateActivationCode();

                $now = $this->now();

                $terminal =
                    BranchTerminal::query()->create([
                        'branch_id' => $lockedBranch->getKey(),

                        'name' => $normalizedName,

                        'device_token_hash' => null,

                        'activation_code_hash' => $this->hashCredential(
                            $activationCode
                        ),

                        'activation_expires_at' => $now->addMinutes(
                            self::ACTIVATION_LIFETIME_MINUTES
                        ),

                        'activated_at' => null,
                        'last_seen_at' => null,

                        'status' => BranchTerminal::STATUS_PENDING,

                        'created_by' => $creator->getKey(),

                        'revoked_by' => null,
                        'revoked_at' => null,
                    ]);

                return [
                    'terminal' => $terminal,
                    'activation_code' => $activationCode,
                ];
            },
            3
        );
    }

    /**
     * @return array{
     *     terminal: BranchTerminal,
     *     activation_code: string
     * }
     */
    public function renewActivation(
        BranchTerminal $branchTerminal
    ): array {
        return DB::transaction(
            function () use (
                $branchTerminal
            ): array {
                $terminal =
                    $this->lockTerminalById(
                        (int) $branchTerminal
                            ->getKey()
                    );

                if (! $terminal->isPending()) {
                    throw new BranchTerminalLifecycleException(
                        'terminal_not_pending',
                        'Kode aktivasi hanya dapat diperbarui untuk terminal pending.'
                    );
                }

                $previousActivationHash =
                    $terminal
                        ->activation_code_hash;

                do {
                    $activationCode =
                        $this->generateActivationCode();

                    $activationHash =
                        $this->hashCredential(
                            $activationCode
                        );
                } while (
                    $activationHash
                    === $previousActivationHash
                );

                $terminal->update([
                    'activation_code_hash' => $activationHash,

                    'activation_expires_at' => $this->now()->addMinutes(
                        self::ACTIVATION_LIFETIME_MINUTES
                    ),
                ]);

                return [
                    'terminal' => $terminal->fresh(),
                    'activation_code' => $activationCode,
                ];
            },
            3
        );
    }

    /**
     * @return array{
     *     terminal: BranchTerminal,
     *     device_token: string
     * }
     */
    public function activate(
        string $publicId,
        string $activationCode
    ): array {
        $normalizedPublicId = strtolower(
            trim($publicId)
        );

        $normalizedActivationCode = trim(
            $activationCode
        );

        if (
            ! Str::isUuid(
                $normalizedPublicId
            )
        ) {
            throw new BranchTerminalLifecycleException(
                'terminal_not_found',
                'Terminal tidak ditemukan.'
            );
        }

        if (
            preg_match(
                '/^\d{'.self::ACTIVATION_CODE_DIGITS.'}$/',
                $normalizedActivationCode
            ) !== 1
        ) {
            throw new BranchTerminalLifecycleException(
                'activation_code_invalid',
                'Kode aktivasi terminal tidak valid.'
            );
        }

        return DB::transaction(
            function () use (
                $normalizedPublicId,
                $normalizedActivationCode
            ): array {
                $terminal = BranchTerminal::query()
                    ->where(
                        'public_id',
                        $normalizedPublicId
                    )
                    ->lockForUpdate()
                    ->first();

                if ($terminal === null) {
                    throw new BranchTerminalLifecycleException(
                        'terminal_not_found',
                        'Terminal tidak ditemukan.'
                    );
                }

                if (! $terminal->isPending()) {
                    throw new BranchTerminalLifecycleException(
                        'terminal_not_pending',
                        'Terminal sudah tidak berada dalam status pending.'
                    );
                }

                if (
                    $terminal->activation_code_hash
                    === null
                    || $terminal
                        ->activation_expires_at
                    === null
                ) {
                    throw new BranchTerminalLifecycleException(
                        'activation_unavailable',
                        'Kode aktivasi terminal tidak tersedia.'
                    );
                }

                $now = $this->now();

                if (
                    $terminal
                        ->activation_expires_at
                        ->lessThanOrEqualTo($now)
                ) {
                    throw new BranchTerminalLifecycleException(
                        'activation_expired',
                        'Kode aktivasi terminal telah kedaluwarsa.'
                    );
                }

                if (
                    ! hash_equals(
                        $terminal
                            ->activation_code_hash,
                        $this->hashCredential(
                            $normalizedActivationCode
                        )
                    )
                ) {
                    throw new BranchTerminalLifecycleException(
                        'activation_code_invalid',
                        'Kode aktivasi terminal tidak valid.'
                    );
                }

                $deviceToken =
                    $this->generateDeviceToken();

                $terminal->update([
                    'device_token_hash' => $this->hashCredential(
                        $deviceToken
                    ),

                    'activation_code_hash' => null,

                    'activation_expires_at' => null,

                    'activated_at' => $now,
                    'last_seen_at' => $now,

                    'status' => BranchTerminal::STATUS_ACTIVE,

                    'revoked_by' => null,
                    'revoked_at' => null,
                ]);

                return [
                    'terminal' => $terminal->fresh(),
                    'device_token' => $deviceToken,
                ];
            },
            3
        );
    }

    public function authenticate(
        string $publicId,
        string $deviceToken
    ): BranchTerminal {
        $normalizedPublicId = strtolower(
            trim($publicId)
        );

        $normalizedDeviceToken = trim(
            $deviceToken
        );

        if (
            ! Str::isUuid(
                $normalizedPublicId
            )
        ) {
            throw new BranchTerminalLifecycleException(
                'terminal_not_found',
                'Terminal tidak ditemukan.'
            );
        }

        if (
            preg_match(
                '/^[a-f0-9]{64}$/',
                $normalizedDeviceToken
            ) !== 1
        ) {
            throw new BranchTerminalLifecycleException(
                'device_token_invalid',
                'Token perangkat terminal tidak valid.'
            );
        }

        return DB::transaction(
            function () use (
                $normalizedPublicId,
                $normalizedDeviceToken
            ): BranchTerminal {
                $terminal = BranchTerminal::query()
                    ->where(
                        'public_id',
                        $normalizedPublicId
                    )
                    ->lockForUpdate()
                    ->first();

                if ($terminal === null) {
                    throw new BranchTerminalLifecycleException(
                        'terminal_not_found',
                        'Terminal tidak ditemukan.'
                    );
                }

                if (! $terminal->isActive()) {
                    throw new BranchTerminalLifecycleException(
                        'terminal_inactive',
                        'Terminal tidak aktif.'
                    );
                }

                if (
                    $terminal->device_token_hash
                    === null
                    || ! hash_equals(
                        $terminal
                            ->device_token_hash,
                        $this->hashCredential(
                            $normalizedDeviceToken
                        )
                    )
                ) {
                    throw new BranchTerminalLifecycleException(
                        'device_token_invalid',
                        'Token perangkat terminal tidak valid.'
                    );
                }

                $terminal->update([
                    'last_seen_at' => $this->now(),
                ]);

                return $terminal->fresh();
            },
            3
        );
    }

    public function revoke(
        BranchTerminal $branchTerminal,
        User $revoker
    ): BranchTerminal {
        return DB::transaction(
            function () use (
                $branchTerminal,
                $revoker
            ): BranchTerminal {
                $terminal =
                    $this->lockTerminalById(
                        (int) $branchTerminal
                            ->getKey()
                    );

                if ($terminal->isRevoked()) {
                    throw new BranchTerminalLifecycleException(
                        'terminal_already_revoked',
                        'Terminal sudah dicabut.'
                    );
                }

                $terminal->update([
                    'device_token_hash' => null,
                    'activation_code_hash' => null,
                    'activation_expires_at' => null,

                    'status' => BranchTerminal::STATUS_REVOKED,

                    'revoked_by' => $revoker->getKey(),

                    'revoked_at' => $this->now(),
                ]);

                return $terminal->fresh();
            },
            3
        );
    }

    private function lockTerminalById(
        int $terminalId
    ): BranchTerminal {
        $terminal = BranchTerminal::query()
            ->lockForUpdate()
            ->find($terminalId);

        if ($terminal === null) {
            throw new BranchTerminalLifecycleException(
                'terminal_not_found',
                'Terminal tidak ditemukan.'
            );
        }

        return $terminal;
    }

    private function normalizeName(
        string $name
    ): string {
        $normalizedName = trim(
            preg_replace(
                '/\s+/',
                ' ',
                $name
            ) ?? ''
        );

        $nameLength = Str::length(
            $normalizedName
        );

        if (
            $nameLength < 3
            || $nameLength > 100
        ) {
            throw new BranchTerminalLifecycleException(
                'terminal_name_invalid',
                'Nama terminal harus terdiri dari 3 sampai 100 karakter.'
            );
        }

        return $normalizedName;
    }

    private function generateActivationCode(): string
    {
        $minimum = 10 ** (
            self::ACTIVATION_CODE_DIGITS - 1
        );

        $maximum = (10 **
            self::ACTIVATION_CODE_DIGITS) - 1;

        return (string) random_int(
            $minimum,
            $maximum
        );
    }

    private function generateDeviceToken(): string
    {
        try {
            return bin2hex(
                random_bytes(
                    self::DEVICE_TOKEN_BYTES
                )
            );
        } catch (Throwable $exception) {
            throw new BranchTerminalLifecycleException(
                'device_token_generation_failed',
                'Token perangkat terminal tidak dapat dibuat: '
                .$exception->getMessage()
            );
        }
    }

    private function hashCredential(
        string $credential
    ): string {
        return hash(
            'sha256',
            $credential
        );
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now(
            (string) config(
                'app.timezone',
                'Asia/Jakarta'
            )
        );
    }
}
