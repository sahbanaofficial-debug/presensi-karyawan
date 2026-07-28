<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\AttendanceScheduleService;
use App\Services\AutomaticAttendanceTypeResolverService;
use App\Services\HaversineService;
use App\Services\TotpService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class AttendanceController extends Controller
{
    /**
     * Menampilkan halaman pemindai QR Code
     * dan pengambilan lokasi karyawan.
     */
    public function create(
        AttendanceScheduleService $attendanceScheduleService
    ): View {
        $user = auth()->user();

        abort_if(
            $user === null,
            401
        );

        $now = CarbonImmutable::now(
            $this->timezone()
        );

        $employee = Employee::query()
            ->with('branch')
            ->where(
                'user_id',
                $user->getKey()
            )
            ->where(
                'employment_status',
                'active'
            )
            ->firstOrFail();

        $branch = $employee->branch;

        $employeeSchedule =
            $attendanceScheduleService
                ->findForDate(
                    $employee,
                    $now
                );

        $attendanceRecords = collect();

        if ($employeeSchedule !== null) {
            $attendanceRecords =
                Attendance::query()
                    ->where(
                        'employee_schedule_id',
                        $employeeSchedule
                            ->getKey()
                    )
                    ->whereIn(
                        'attendance_type',
                        [
                            'check_in',
                            'check_out',
                        ]
                    )
                    ->orderBy(
                        'attendance_time'
                    )
                    ->get()
                    ->keyBy(
                        'attendance_type'
                    );
        }

        $checkInAttendance =
            $attendanceRecords->get(
                'check_in'
            );

        $checkOutAttendance =
            $attendanceRecords->get(
                'check_out'
            );

        $scheduleTimeWindow = null;
        $canScan = true;
        $scanBlockReason = null;

        if ($branch === null) {
            $canScan = false;

            $scanBlockReason =
                'Data cabang penempatan karyawan tidak tersedia.';
        } elseif (
            ! $branch->isActive()
            || ! $branch
                ->hasGeofenceConfiguration()
        ) {
            $canScan = false;

            $scanBlockReason =
                'Cabang tidak aktif atau konfigurasi geofence belum lengkap.';
        } elseif ($employeeSchedule === null) {
            $canScan = false;

            $scanBlockReason =
                'Jadwal kerja hari ini belum tersedia.';
        } elseif (
            ! $employeeSchedule
                ->requiresAttendance()
        ) {
            $canScan = false;

            $scanBlockReason = match (
                $employeeSchedule
                    ->schedule_status
            ) {
                'off' => 'Hari ini merupakan jadwal libur.',

                'permit' => 'Hari ini tercatat sebagai izin.',

                'sick' => 'Hari ini tercatat sebagai sakit.',

                default => 'Jadwal hari ini tidak memerlukan presensi.',
            };
        } elseif (
            ! $employeeSchedule
                ->hasValidScheduleConfiguration()
        ) {
            $canScan = false;

            $scanBlockReason =
                'Konfigurasi pola jadwal kerja hari ini belum lengkap.';
        } elseif (
            $checkInAttendance !== null
            && $checkOutAttendance !== null
        ) {
            $canScan = false;

            $scanBlockReason =
                'Presensi masuk dan pulang hari ini sudah tercatat.';
        }

        if (
            $employeeSchedule !== null
            && $employeeSchedule
                ->requiresAttendance()
            && $employeeSchedule
                ->hasValidScheduleConfiguration()
        ) {
            try {
                $scheduleTimeWindow =
                    $attendanceScheduleService
                        ->timeWindow(
                            $employeeSchedule
                        );
            } catch (\InvalidArgumentException) {
                if ($canScan) {
                    $canScan = false;

                    $scanBlockReason =
                        'Konfigurasi waktu jadwal kerja tidak valid.';
                }
            }
        }

        return view(
            'attendance.create',
            [
                'employee' => $employee,

                'branch' => $branch,

                'employeeSchedule' => $employeeSchedule,

                'scheduleTimeWindow' => $scheduleTimeWindow,

                'checkInAttendance' => $checkInAttendance,

                'checkOutAttendance' => $checkOutAttendance,

                'canScan' => $canScan,

                'scanBlockReason' => $scanBlockReason,

                'currentMoment' => $now,
            ]
        );
    }

    /**
     * Memproses transaksi presensi karyawan.
     */
    public function store(
        StoreAttendanceRequest $request,
        TotpService $totpService,
        HaversineService $haversineService,
        AttendanceScheduleService $attendanceScheduleService,
        AutomaticAttendanceTypeResolverService $attendanceTypeResolverService
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                [
                    'success' => false,
                    'code' => 'unauthenticated',
                    'message' => 'Sesi pengguna tidak tersedia.',
                ],
                401
            );
        }

        $coordinates = $request->coordinates();

        $payloadReference =
            $request->payloadReference();

        $now = CarbonImmutable::now(
            $this->timezone()
        );

        $result = DB::transaction(
            function () use (
                $request,
                $user,
                $coordinates,
                $payloadReference,
                $now,
                $totpService,
                $haversineService,
                $attendanceScheduleService,
                $attendanceTypeResolverService
            ): array {
                /*
                 * Profil karyawan kembali diperiksa dalam
                 * transaksi untuk mencegah perubahan status
                 * ketika proses presensi sedang berlangsung.
                 */
                $employee = Employee::query()
                    ->where(
                        'user_id',
                        $user->getKey()
                    )
                    ->where(
                        'employment_status',
                        'active'
                    )
                    ->lockForUpdate()
                    ->first();

                if ($employee === null) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: null,
                        code: 'employee_profile_inactive',
                        message: 'Profil karyawan tidak tersedia atau sudah tidak aktif.',
                        httpStatus: 403,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                /*
                 * UUID pada QR merupakan identitas publik.
                 * Primary key internal tidak diterima dari
                 * browser.
                 */
                $attendanceSession =
                    AttendanceSession::query()
                        ->where(
                            'public_id',
                            $request
                                ->sessionPublicId()
                        )
                        ->lockForUpdate()
                        ->first();

                if ($attendanceSession === null) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: null,
                        code: 'session_not_found',
                        message: 'Sesi presensi pada QR Code tidak ditemukan.',
                        httpStatus: 404,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                /*
                 * Sesi yang telah melewati end_time ditandai
                 * expired sebelum validasi status dilakukan.
                 */
                if (
                    $attendanceSession->isActive()
                    && $now->greaterThan(
                        $attendanceSession
                            ->end_time
                    )
                ) {
                    $attendanceSession->update([
                        'status' => 'expired',
                    ]);
                }

                if (
                    $attendanceSession->isClosed()
                ) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'session_closed',
                        message: 'Sesi presensi sudah ditutup.',
                        httpStatus: 409,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                if (
                    $attendanceSession->isExpired()
                ) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'session_expired',
                        message: 'Sesi presensi telah kedaluwarsa.',
                        httpStatus: 410,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                if (
                    $now->lessThan(
                        $attendanceSession
                            ->start_time
                    )
                ) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'session_not_started',
                        message: 'Sesi presensi belum dimulai.',
                        httpStatus: 422,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                if (
                    ! $attendanceSession
                        ->isWithinTimeWindow($now)
                ) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'outside_session_time',
                        message: 'Presensi dilakukan di luar rentang waktu sesi.',
                        httpStatus: 422,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                if (
                    $attendanceSession
                        ->session_date
                        ->format('Y-m-d')
                    !== $now->format('Y-m-d')
                ) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'session_date_mismatch',
                        message: 'Tanggal sesi tidak sesuai dengan tanggal server.',
                        httpStatus: 422,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                /*
                 * Cabang dikunci agar koordinat, radius,
                 * batas accuracy, dan status tidak berubah
                 * selama transaksi diproses.
                 */
                $branch = Branch::query()
                    ->lockForUpdate()
                    ->find(
                        $attendanceSession
                            ->branch_id
                    );

                if ($branch === null) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'branch_not_found',
                        message: 'Cabang sesi presensi tidak ditemukan.',
                        httpStatus: 409,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                /*
                 * Karyawan Cabang 02 tidak diperbolehkan
                 * menggunakan sesi milik cabang lain.
                 */
                if (
                    (int) $employee->branch_id
                    !== (int) $branch->getKey()
                ) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'branch_mismatch',
                        message: 'Sesi presensi tidak berasal dari cabang penempatan karyawan.',
                        httpStatus: 403,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                if (
                    ! $branch->isActive()
                    || ! $branch
                        ->hasGeofenceConfiguration()
                ) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'branch_configuration_invalid',
                        message: 'Cabang tidak aktif atau konfigurasi geofence belum lengkap.',
                        httpStatus: 409,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                /*
                 * Validasi TOTP menggunakan waktu server.
                 * Window nol berarti hanya token periode
                 * aktif yang diterima.
                 */
                $tokenIsValid =
                    $totpService->verifyCode(
                        $attendanceSession
                            ->encrypted_secret,
                        $request->totpToken(),
                        $now->getTimestamp(),
                        0
                    );

                if (! $tokenIsValid) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'totp_invalid',
                        message: 'Token QR Code tidak valid atau telah kedaluwarsa.',
                        httpStatus: 422,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                /*
                 * Jadwal dicari berdasarkan profil karyawan
                 * dan tanggal sesi.
                 */
                $employeeSchedule =
                    $attendanceScheduleService
                        ->findForDate(
                            $employee,
                            $attendanceSession
                                ->session_date,
                            true
                        );

                if ($employeeSchedule === null) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'employee_schedule_not_found',
                        message: 'Jadwal harian karyawan tidak ditemukan untuk tanggal sesi.',
                        httpStatus: 422,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                $resolvedAttendanceType =
                    $attendanceTypeResolverService
                        ->resolve(
                            $attendanceSession,
                            $employeeSchedule
                        );

                if ($resolvedAttendanceType === null) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'attendance_completed',
                        message: 'Presensi masuk dan pulang untuk jadwal harian ini sudah lengkap.',
                        httpStatus: 409,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                $scheduleResult =
                    $attendanceScheduleService
                        ->evaluate(
                            $employeeSchedule,
                            $resolvedAttendanceType,
                            $now
                        );

                if (
                    ! $scheduleResult['allowed']
                ) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: (string)
                            $scheduleResult['code'],
                        message: (string)
                            $scheduleResult['message'],
                        httpStatus: 422,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                /*
                 * Accuracy merupakan perkiraan ketidakpastian
                 * lokasi perangkat. Accuracy harus diperiksa
                 * sebelum keputusan geofence.
                 */
                if (
                    $coordinates['accuracy']
                    > (float) $branch
                        ->maximum_accuracy
                ) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'location_accuracy_too_low',
                        message: sprintf(
                            'Akurasi lokasi %.2f meter melebihi batas cabang %.2f meter. Ambil ulang lokasi perangkat.',
                            $coordinates['accuracy'],
                            (float) $branch
                                ->maximum_accuracy
                        ),
                        httpStatus: 422,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now
                    );
                }

                $rawDistance =
                    $haversineService
                        ->distanceInMeters(
                            (float) $branch->latitude,
                            (float) $branch->longitude,
                            $coordinates['latitude'],
                            $coordinates['longitude']
                        );

                $roundedDistance = round(
                    $rawDistance,
                    2
                );

                if (
                    $rawDistance
                    > (float) $branch
                        ->geofence_radius
                ) {
                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'outside_geofence',
                        message: sprintf(
                            'Lokasi berada %.2f meter dari titik cabang dan berada di luar radius %.2f meter.',
                            $roundedDistance,
                            (float) $branch
                                ->geofence_radius
                        ),
                        httpStatus: 422,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now,
                        distance: $roundedDistance
                    );
                }

                /*
                 * Pemeriksaan aplikasi dilakukan sebelum
                 * constraint unik pada basis data.
                 */
                $attendanceExists =
                    Attendance::query()
                        ->where(
                            'employee_schedule_id',
                            $employeeSchedule
                                ->getKey()
                        )
                        ->where(
                            'attendance_type',
                            $resolvedAttendanceType
                        )
                        ->exists();

                if ($attendanceExists) {
                    $attendanceTypeLabel =
                        $resolvedAttendanceType
                        === 'check_in'
                            ? 'masuk'
                            : 'pulang';

                    return $this->rejectedResult(
                        userId: (int) $user->getKey(),
                        attendanceSession: $attendanceSession,
                        code: 'duplicate_attendance',
                        message: sprintf(
                            'Presensi %s untuk jadwal harian ini sudah tercatat.',
                            $attendanceTypeLabel
                        ),
                        httpStatus: 409,
                        payloadReference: $payloadReference,
                        coordinates: $coordinates,
                        occurredAt: $now,
                        distance: $roundedDistance
                    );
                }

                $punctualityStatus =
                    (string) $scheduleResult[
                        'punctuality_status'
                    ];

                $attendance =
                    Attendance::query()->create([
                        'employee_id' => $employee->getKey(),

                        'attendance_session_id' => $attendanceSession
                            ->getKey(),

                        'employee_schedule_id' => $employeeSchedule
                            ->getKey(),

                        'branch_id' => $branch->getKey(),

                        'attendance_type' => $resolvedAttendanceType,

                        'attendance_date' => $now->format('Y-m-d'),

                        'attendance_time' => $now,

                        'latitude' => $coordinates['latitude'],

                        'longitude' => $coordinates['longitude'],

                        'accuracy' => round(
                            $coordinates[
                                'accuracy'
                            ],
                            2
                        ),

                        'distance' => $roundedDistance,

                        'geofence_radius' => (float) $branch
                            ->geofence_radius,

                        'attendance_status' => 'present',

                        'punctuality_status' => $punctualityStatus,

                        'validation_status' => 'accepted',
                    ]);

                $this->writeValidationLog(
                    userId: (int) $user->getKey(),
                    attendanceSessionId: (int) $attendanceSession
                        ->getKey(),
                    validationType: 'attendance_accepted',
                    status: 'accepted',
                    reason: $scheduleResult['message'],
                    payloadReference: $payloadReference,
                    coordinates: $coordinates,
                    distance: $roundedDistance,
                    occurredAt: $now
                );

                return [
                    'accepted' => true,

                    'http_status' => 201,

                    'code' => 'attendance_accepted',

                    'message' => (string) $scheduleResult[
                            'message'
                        ],

                    'data' => [
                        'attendance_id' => $attendance->getKey(),

                        'attendance_type' => $attendance
                            ->attendance_type,

                        'attendance_date' => $attendance
                            ->attendance_date
                            ->format('Y-m-d'),

                        'attendance_time' => $attendance
                            ->attendance_time
                            ->toIso8601String(),

                        'attendance_status' => $attendance
                            ->attendance_status,

                        'punctuality_status' => $attendance
                            ->punctuality_status,

                        'distance' => $attendance->distance,

                        'geofence_radius' => $attendance
                            ->geofence_radius,

                        'accuracy' => $attendance->accuracy,
                    ],
                ];
            },
            3
        );

        if (! $result['accepted']) {
            return response()->json(
                [
                    'success' => false,
                    'code' => $result['code'],
                    'message' => $result['message'],
                ],
                $result['http_status']
            );
        }

        return response()->json(
            [
                'success' => true,
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => $result['data'],
            ],
            $result['http_status']
        );
    }

    /**
     * Membentuk hasil penolakan dan menyimpan
     * validation log dalam transaksi yang sama.
     *
     * @param array{
     *     latitude: float,
     *     longitude: float,
     *     accuracy: float
     * } $coordinates
     * @return array{
     *     accepted: false,
     *     http_status: int,
     *     code: string,
     *     message: string,
     *     data: null
     * }
     */
    private function rejectedResult(
        int $userId,
        ?AttendanceSession $attendanceSession,
        string $code,
        string $message,
        int $httpStatus,
        string $payloadReference,
        array $coordinates,
        CarbonImmutable $occurredAt,
        ?float $distance = null
    ): array {
        $this->writeValidationLog(
            userId: $userId,
            attendanceSessionId: $attendanceSession === null
                    ? null
                    : (int) $attendanceSession
                        ->getKey(),
            validationType: $code,
            status: 'rejected',
            reason: $message,
            payloadReference: $payloadReference,
            coordinates: $coordinates,
            distance: $distance,
            occurredAt: $occurredAt
        );

        return [
            'accepted' => false,
            'http_status' => $httpStatus,
            'code' => $code,
            'message' => $message,
            'data' => null,
        ];
    }

    /**
     * Menyimpan log tanpa menyimpan token TOTP asli.
     *
     * Tabel validation_logs hanya menyimpan
     * payload_reference berupa hash SHA-256.
     *
     * @param array{
     *     latitude: float,
     *     longitude: float,
     *     accuracy: float
     * } $coordinates
     */
    private function writeValidationLog(
        int $userId,
        ?int $attendanceSessionId,
        string $validationType,
        string $status,
        ?string $reason,
        string $payloadReference,
        array $coordinates,
        ?float $distance,
        CarbonImmutable $occurredAt
    ): void {
        DB::table('validation_logs')->insert([
            'user_id' => $userId,

            'attendance_session_id' => $attendanceSessionId,

            'validation_type' => $validationType,

            'status' => $status,

            'reason' => $reason,

            'payload_reference' => $payloadReference,

            'latitude' => $coordinates['latitude'],

            'longitude' => $coordinates['longitude'],

            'accuracy' => round(
                $coordinates['accuracy'],
                2
            ),

            'distance' => $distance,

            'created_at' => $occurredAt,
        ]);
    }

    /**
     * Zona waktu operasional sistem.
     */
    private function timezone(): string
    {
        return (string) config(
            'app.timezone',
            'Asia/Jakarta'
        );
    }
}
