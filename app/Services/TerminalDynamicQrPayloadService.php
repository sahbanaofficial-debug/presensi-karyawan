<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TerminalDynamicQrException;
use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\BranchTerminal;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use JsonException;

final class TerminalDynamicQrPayloadService
{
    public const PAYLOAD_VERSION = 1;

    private const UNAVAILABLE_REFRESH_SECONDS = 15;

    public function __construct(
        private readonly TotpService $totpService
    ) {}

    /**
     * @return array{
     *     available: bool,
     *     terminal: array{
     *         public_id: string,
     *         name: string
     *     },
     *     branch: array{
     *         code: string,
     *         name: string
     *     },
     *     session: null|array{
     *         public_id: string,
     *         attendance_type: string,
     *         session_source: string,
     *         session_date: string,
     *         start_time: string,
     *         end_time: string
     *     },
     *     qr_payload: ?string,
     *     seconds_remaining: ?int,
     *     refresh_after: int,
     *     issued_at: string
     * }
     *
     * @throws JsonException
     */
    public function payloadFor(
        BranchTerminal $branchTerminal,
        DateTimeInterface|string|null $moment = null
    ): array {
        $now = $this->resolveMoment($moment);

        $terminal = BranchTerminal::query()
            ->with('branch')
            ->find($branchTerminal->getKey());

        if ($terminal === null) {
            throw new TerminalDynamicQrException(
                'terminal_not_found',
                'Terminal tidak ditemukan.'
            );
        }

        if (! $terminal->isActive()) {
            throw new TerminalDynamicQrException(
                'terminal_inactive',
                'Terminal tidak aktif.'
            );
        }

        $branch = $terminal->branch;

        if (
            ! $branch instanceof Branch
            || ! $branch->isActive()
            || ! $branch
                ->hasGeofenceConfiguration()
        ) {
            throw new TerminalDynamicQrException(
                'terminal_branch_unavailable',
                'Cabang terminal tidak aktif atau konfigurasi geofence belum lengkap.'
            );
        }

        $attendanceSession =
            AttendanceSession::query()
                ->where(
                    'branch_id',
                    $branch->getKey()
                )
                ->where(
                    'attendance_type',
                    AttendanceSession::TYPE_AUTO
                )
                ->where(
                    'session_source',
                    AttendanceSession::SOURCE_AUTOMATIC
                )
                ->where(
                    'status',
                    'active'
                )
                ->whereDate(
                    'session_date',
                    $now->format('Y-m-d')
                )
                ->where(
                    'start_time',
                    '<=',
                    $now
                )
                ->where(
                    'end_time',
                    '>=',
                    $now
                )
                ->orderByDesc('start_time')
                ->orderByDesc('id')
                ->first();

        if ($attendanceSession === null) {
            return $this->unavailablePayload(
                terminal: $terminal,
                branch: $branch,
                now: $now
            );
        }

        $timestamp = $now->getTimestamp();

        $token = $this->totpService
            ->generateCode(
                $attendanceSession
                    ->encrypted_secret,
                $timestamp
            );

        $signature = $this->signatureFor(
            sessionPublicId: (string) $attendanceSession
                ->public_id,

            token: $token,

            terminalPublicId: (string) $terminal
                ->public_id
        );

        $qrPayload = json_encode(
            [
                'version' => self::PAYLOAD_VERSION,

                'session' => (string) $attendanceSession
                    ->public_id,

                'token' => $token,

                'terminal' => (string) $terminal
                    ->public_id,

                'signature' => $signature,
            ],
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
        );

        $secondsRemaining =
            $this->totpService
                ->secondsRemaining(
                    $timestamp
                );

        return [
            'available' => true,

            'terminal' => $this->terminalData(
                $terminal
            ),

            'branch' => $this->branchData(
                $branch
            ),

            'session' => [
            'public_id' => (string) $attendanceSession
                ->public_id,

            'attendance_type' => (string) $attendanceSession
                ->attendance_type,

            'session_source' => (string) $attendanceSession
                ->session_source,

            'session_date' => $attendanceSession
                ->session_date
                ->format('Y-m-d'),

            'start_time' => $attendanceSession
                ->start_time
                ->toIso8601String(),

            'end_time' => $attendanceSession
                ->end_time
                ->toIso8601String(),
            ],

            'qr_payload' => $qrPayload,

            'seconds_remaining' => $secondsRemaining,

            'refresh_after' => $secondsRemaining,

            'issued_at' => $now->toIso8601String(),
        ];
    }

    public function verifySignature(
        string $sessionPublicId,
        string $token,
        string $terminalPublicId,
        string $signature
    ): bool {
        $normalizedSignature = strtolower(
            trim($signature)
        );

        if (
            preg_match(
                '/^[a-f0-9]{64}$/',
                $normalizedSignature
            ) !== 1
        ) {
            return false;
        }

        $expectedSignature =
            $this->signatureFor(
                sessionPublicId: $sessionPublicId,

                token: $token,

                terminalPublicId: $terminalPublicId
            );

        return hash_equals(
            $expectedSignature,
            $normalizedSignature
        );
    }

    /**
     * @return array{
     *     available: false,
     *     terminal: array{
     *         public_id: string,
     *         name: string
     *     },
     *     branch: array{
     *         code: string,
     *         name: string
     *     },
     *     session: null,
     *     qr_payload: null,
     *     seconds_remaining: null,
     *     refresh_after: int,
     *     issued_at: string
     * }
     */
    private function unavailablePayload(
        BranchTerminal $terminal,
        Branch $branch,
        CarbonImmutable $now
    ): array {
        return [
            'available' => false,

            'terminal' => $this->terminalData(
                $terminal
            ),

            'branch' => $this->branchData(
                $branch
            ),

            'session' => null,
            'qr_payload' => null,
            'seconds_remaining' => null,

            'refresh_after' => self::UNAVAILABLE_REFRESH_SECONDS,

            'issued_at' => $now->toIso8601String(),
        ];
    }

    /**
     * @return array{
     *     public_id: string,
     *     name: string
     * }
     */
    private function terminalData(
        BranchTerminal $terminal
    ): array {
        return [
            'public_id' => (string) $terminal->public_id,

            'name' => (string) $terminal->name,
        ];
    }

    /**
     * @return array{
     *     code: string,
     *     name: string
     * }
     */
    private function branchData(
        Branch $branch
    ): array {
        return [
            'code' => (string) $branch->code,
            'name' => (string) $branch->name,
        ];
    }

    private function signatureFor(
        string $sessionPublicId,
        string $token,
        string $terminalPublicId
    ): string {
        $canonicalPayload = implode(
            '|',
            [
                'v'.self::PAYLOAD_VERSION,

                strtolower(
                    trim($sessionPublicId)
                ),

                trim($token),

                strtolower(
                    trim($terminalPublicId)
                ),
            ]
        );

        return hash_hmac(
            'sha256',
            $canonicalPayload,
            $this->signingKey()
        );
    }

    private function signingKey(): string
    {
        $applicationKey = trim(
            (string) config(
                'app.key',
                ''
            )
        );

        if ($applicationKey === '') {
            throw new TerminalDynamicQrException(
                'qr_signing_key_unavailable',
                'Kunci penandatanganan QR tidak tersedia.'
            );
        }

        if (
            str_starts_with(
                $applicationKey,
                'base64:'
            )
        ) {
            $decodedKey = base64_decode(
                substr(
                    $applicationKey,
                    7
                ),
                true
            );

            if (
                $decodedKey === false
                || $decodedKey === ''
            ) {
                throw new TerminalDynamicQrException(
                    'qr_signing_key_invalid',
                    'Kunci penandatanganan QR tidak valid.'
                );
            }

            return $decodedKey;
        }

        return $applicationKey;
    }

    private function resolveMoment(
        DateTimeInterface|string|null $moment
    ): CarbonImmutable {
        if ($moment instanceof DateTimeInterface) {
            return CarbonImmutable::instance(
                $moment
            )->setTimezone(
                $this->timezone()
            );
        }

        if (is_string($moment)) {
            return CarbonImmutable::parse(
                $moment,
                $this->timezone()
            );
        }

        return CarbonImmutable::now(
            $this->timezone()
        );
    }

    private function timezone(): string
    {
        return (string) config(
            'app.timezone',
            'Asia/Jakarta'
        );
    }
}
