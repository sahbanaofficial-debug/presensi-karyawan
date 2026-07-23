<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CloseAttendanceSessionRequest;
use App\Http\Requests\StoreAttendanceSessionRequest;
use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Services\TotpService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

final class AttendanceSessionController extends Controller
{
    private const ITEMS_PER_PAGE = 15;

    /**
     * Menampilkan daftar sesi presensi.
     */
    public function index(Request $request): View
    {
        $this->expireElapsedSessions();

        $search = trim(
            (string) $request->query('search', '')
        );

        $attendanceType = strtolower(
            trim(
                (string) $request->query(
                    'attendance_type',
                    ''
                )
            )
        );

        $status = strtolower(
            trim(
                (string) $request->query(
                    'status',
                    ''
                )
            )
        );

        $sessionDate = $this->validDateOrNull(
            (string) $request->query(
                'session_date',
                ''
            )
        );

        if (
            ! in_array(
                $attendanceType,
                [
                    'check_in',
                    'check_out',
                ],
                true
            )
        ) {
            $attendanceType = '';
        }

        if (
            ! in_array(
                $status,
                [
                    'active',
                    'closed',
                    'expired',
                ],
                true
            )
        ) {
            $status = '';
        }

        $attendanceSessions =
            AttendanceSession::query()
                ->with([
                    'branch:id,code,name,status',
                    'creator:id,name,email,role,status',
                ])
                ->withCount('attendances')
                ->when(
                    $search !== '',
                    function (
                        Builder $query
                    ) use ($search): void {
                        $query->where(
                            function (
                                Builder $searchQuery
                            ) use ($search): void {
                                $searchQuery
                                    ->where(
                                        'public_id',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhereHas(
                                        'branch',
                                        function (
                                            Builder $branchQuery
                                        ) use ($search): void {
                                            $branchQuery
                                                ->where(
                                                    'code',
                                                    'like',
                                                    "%{$search}%"
                                                )
                                                ->orWhere(
                                                    'name',
                                                    'like',
                                                    "%{$search}%"
                                                );
                                        }
                                    );
                            }
                        );
                    }
                )
                ->when(
                    $attendanceType !== '',
                    fn (
                        Builder $query
                    ) => $query->where(
                        'attendance_type',
                        $attendanceType
                    )
                )
                ->when(
                    $status !== '',
                    fn (
                        Builder $query
                    ) => $query->where(
                        'status',
                        $status
                    )
                )
                ->when(
                    $sessionDate !== null,
                    fn (
                        Builder $query
                    ) => $query->whereDate(
                        'session_date',
                        $sessionDate
                    )
                )
                ->orderByRaw(
                    "CASE
                        WHEN status = 'active' THEN 0
                        WHEN status = 'closed' THEN 1
                        ELSE 2
                    END"
                )
                ->orderByDesc('session_date')
                ->orderByDesc('start_time')
                ->paginate(self::ITEMS_PER_PAGE)
                ->withQueryString();

        return view(
            'attendance-sessions.index',
            [
                'attendanceSessions' => $attendanceSessions,

                'search' => $search,

                'selectedAttendanceType' => $attendanceType,

                'selectedStatus' => $status,

                'selectedSessionDate' => $sessionDate,
            ]
        );
    }

    /**
     * Menampilkan formulir pembukaan sesi.
     */
    public function create(): View
    {
        $this->expireElapsedSessions();

        $branches = Branch::query()
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('geofence_radius', '>', 0)
            ->where('maximum_accuracy', '>', 0)
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'address',
                'latitude',
                'longitude',
                'geofence_radius',
                'maximum_accuracy',
                'status',
            ]);

        return view(
            'attendance-sessions.create',
            [
                'branches' => $branches,
            ]
        );
    }

    /**
     * Membuka sesi presensi baru.
     */
    public function store(
        StoreAttendanceSessionRequest $request,
        TotpService $totpService
    ): RedirectResponse {
        $validated = $request->validated();

        $startTime = $this->combineDateAndTime(
            (string) $validated['session_date'],
            (string) $validated['start_time']
        );

        $endTime = $this->combineDateAndTime(
            (string) $validated['session_date'],
            (string) $validated['end_time']
        );

        $creatorId = (int) $request
            ->user()
            ->getKey();

        $attendanceSession = DB::transaction(
            function () use (
                $validated,
                $startTime,
                $endTime,
                $creatorId,
                $totpService
            ): AttendanceSession {
                /*
                 * Mengunci cabang agar dua permintaan yang
                 * bersamaan tidak membuka sesi ganda.
                 */
                $branch = Branch::query()
                    ->whereKey(
                        (int) $validated['branch_id']
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! $branch->isActive()
                    || ! $branch
                        ->hasGeofenceConfiguration()
                ) {
                    throw ValidationException::withMessages([
                        'branch_id' => 'Cabang tidak aktif atau konfigurasi geofence belum lengkap.',
                    ]);
                }

                /*
                 * Sesi aktif yang waktu berakhirnya telah lewat
                 * ditandai sebagai expired.
                 */
                AttendanceSession::query()
                    ->where(
                        'branch_id',
                        $branch->getKey()
                    )
                    ->where('status', 'active')
                    ->where(
                        'end_time',
                        '<',
                        CarbonImmutable::now(
                            config('app.timezone')
                        )
                    )
                    ->update([
                        'status' => 'expired',
                    ]);

                $duplicateExists =
                    AttendanceSession::query()
                        ->where(
                            'branch_id',
                            $branch->getKey()
                        )
                        ->where(
                            'attendance_type',
                            $validated[
                                'attendance_type'
                            ]
                        )
                        ->whereDate(
                            'session_date',
                            $validated[
                                'session_date'
                            ]
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->exists();

                if ($duplicateExists) {
                    throw ValidationException::withMessages([
                        'attendance_type' => 'Cabang tersebut sudah mempunyai sesi presensi aktif dengan jenis dan tanggal yang sama.',
                    ]);
                }

                return AttendanceSession::query()->create([
                    'branch_id' => $branch->getKey(),

                    'attendance_type' => $validated[
                            'attendance_type'
                        ],

                    'session_date' => $validated[
                            'session_date'
                        ],

                    'start_time' => $startTime,

                    'end_time' => $endTime,

                    /*
                     * Model cast encrypted akan mengenkripsi
                     * secret sebelum disimpan.
                     */
                    'encrypted_secret' => $totpService
                        ->generateSecret(),

                    'status' => 'active',

                    'created_by' => $creatorId,

                    'closed_at' => null,
                ]);
            },
            3
        );

        return redirect()
            ->route(
                'attendance-sessions.show',
                $attendanceSession
            )
            ->with(
                'success',
                'Sesi presensi berhasil dibuka.'
            );
    }

    /**
     * Menampilkan detail sesi presensi.
     */
    public function show(
        AttendanceSession $attendanceSession,
        TotpService $totpService
    ): View {
        $this->expireSessionIfElapsed(
            $attendanceSession
        );

        $attendanceSession
            ->refresh()
            ->load([
                'branch:id,code,name,address,latitude,longitude,geofence_radius,maximum_accuracy,status',
                'creator:id,name,email,role,status',
            ])
            ->loadCount([
                'attendances',
                'validationLogs',
            ]);

        $now = CarbonImmutable::now(
            config('app.timezone')
        );

        $isWithinTimeWindow =
            $attendanceSession
                ->isWithinTimeWindow($now);

        $isUsable =
            $attendanceSession
                ->isUsableAt($now);

        $currentToken = null;
        $tokenSecondsRemaining = null;

        if ($isUsable) {
            $currentToken =
                $totpService->generateCode(
                    $attendanceSession
                        ->encrypted_secret,
                    $now->getTimestamp()
                );

            $tokenSecondsRemaining =
                $totpService->secondsRemaining(
                    $now->getTimestamp()
                );
        }

        return view(
            'attendance-sessions.show',
            [
                'attendanceSession' => $attendanceSession,

                'isWithinTimeWindow' => $isWithinTimeWindow,

                'isUsable' => $isUsable,

                'currentToken' => $currentToken,

                'tokenSecondsRemaining' => $tokenSecondsRemaining,
            ]
        );
    }

    /**
     * Menghasilkan payload QR dinamis.
     *
     * Secret TOTP tidak pernah dimasukkan ke respons.
     */
    public function payload(
        AttendanceSession $attendanceSession,
        TotpService $totpService
    ): JsonResponse {
        $this->expireSessionIfElapsed(
            $attendanceSession
        );

        $attendanceSession->refresh();

        $now = CarbonImmutable::now(
            config('app.timezone')
        );

        if (! $attendanceSession->isActive()) {
            return response()->json(
                [
                    'message' => 'Sesi presensi sudah tidak aktif.',

                    'status' => $attendanceSession->status,
                ],
                409
            );
        }

        if (
            $now->lessThan(
                $attendanceSession->start_time
            )
        ) {
            return response()->json(
                [
                    'message' => 'Sesi presensi belum dimulai.',

                    'status' => 'not_started',

                    'starts_at' => $attendanceSession
                        ->start_time
                        ->toIso8601String(),
                ],
                422
            );
        }

        if (
            $now->greaterThan(
                $attendanceSession->end_time
            )
        ) {
            $attendanceSession->update([
                'status' => 'expired',
            ]);

            return response()->json(
                [
                    'message' => 'Sesi presensi telah berakhir.',

                    'status' => 'expired',
                ],
                410
            );
        }

        $timestamp = $now->getTimestamp();

        $token = $totpService->generateCode(
            $attendanceSession
                ->encrypted_secret,
            $timestamp
        );

        $sessionSecondsRemaining = max(
            1,
            $attendanceSession
                ->end_time
                ->getTimestamp()
                - $timestamp
        );

        $expiresIn = min(
            $totpService->secondsRemaining(
                $timestamp
            ),
            $sessionSecondsRemaining
        );

        /*
         * Data inilah yang nantinya diubah menjadi QR Code.
         * Tidak ada id internal maupun secret di dalamnya.
         */
        $qrPayload = json_encode(
            [
                'session' => $attendanceSession->public_id,

                'token' => $token,
            ],
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
        );

        return response()->json([
            'data' => [
                'public_id' => $attendanceSession->public_id,

                'attendance_type' => $attendanceSession
                    ->attendance_type,

                'session_date' => $attendanceSession
                    ->session_date
                    ->format('Y-m-d'),

                'token' => $token,

                'qr_payload' => $qrPayload,

                'expires_in' => $expiresIn,

                'valid_until' => $now
                    ->addSeconds($expiresIn)
                    ->toIso8601String(),
            ],
        ]);
    }

    /**
     * Menutup sesi presensi secara manual.
     */
    public function close(
        CloseAttendanceSessionRequest $request,
        AttendanceSession $attendanceSession
    ): RedirectResponse {
        $this->expireSessionIfElapsed(
            $attendanceSession
        );

        $attendanceSession->refresh();

        if (! $attendanceSession->isActive()) {
            return redirect()
                ->route(
                    'attendance-sessions.show',
                    $attendanceSession
                )
                ->with(
                    'error',
                    'Sesi presensi sudah tidak aktif dan tidak dapat ditutup.'
                );
        }

        DB::transaction(
            function () use (
                $attendanceSession
            ): void {
                $lockedSession =
                    AttendanceSession::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $attendanceSession
                                ->getKey()
                        );

                if (! $lockedSession->isActive()) {
                    throw ValidationException::withMessages([
                        'attendance_session' => 'Sesi presensi sudah tidak aktif dan tidak dapat ditutup kembali.',
                    ]);
                }

                $lockedSession->update([
                    'status' => 'closed',

                    'closed_at' => CarbonImmutable::now(
                        config('app.timezone')
                    ),
                ]);
            },
            3
        );

        return redirect()
            ->route(
                'attendance-sessions.show',
                $attendanceSession
            )
            ->with(
                'success',
                'Sesi presensi berhasil ditutup.'
            );
    }

    /**
     * Menandai seluruh sesi aktif yang waktunya telah lewat.
     */
    private function expireElapsedSessions(): void
    {
        AttendanceSession::query()
            ->where('status', 'active')
            ->where(
                'end_time',
                '<',
                CarbonImmutable::now(
                    config('app.timezone')
                )
            )
            ->update([
                'status' => 'expired',
            ]);
    }

    /**
     * Menandai satu sesi sebagai expired apabila waktunya lewat.
     */
    private function expireSessionIfElapsed(
        AttendanceSession $attendanceSession
    ): void {
        if (! $attendanceSession->isActive()) {
            return;
        }

        $now = CarbonImmutable::now(
            config('app.timezone')
        );

        if (
            $attendanceSession
                ->end_time
                ->lessThan($now)
        ) {
            $attendanceSession->update([
                'status' => 'expired',
            ]);
        }
    }

    /**
     * Menggabungkan tanggal dan waktu formulir.
     */
    private function combineDateAndTime(
        string $sessionDate,
        string $time
    ): CarbonImmutable {
        $dateTime = CarbonImmutable::createFromFormat(
            '!Y-m-d H:i',
            "{$sessionDate} {$time}",
            config(
                'app.timezone',
                'Asia/Jakarta'
            )
        );

        if ($dateTime === false) {
            throw new RuntimeException(
                'Tanggal dan waktu sesi tidak dapat diproses.'
            );
        }

        return $dateTime;
    }

    /**
     * Memvalidasi filter tanggal.
     */
    private function validDateOrNull(
        string $value
    ): ?string {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $date = CarbonImmutable::createFromFormat(
            '!Y-m-d',
            $value,
            config(
                'app.timezone',
                'Asia/Jakarta'
            )
        );

        if (
            $date === false
            || $date->format('Y-m-d') !== $value
        ) {
            return null;
        }

        return $value;
    }
}
