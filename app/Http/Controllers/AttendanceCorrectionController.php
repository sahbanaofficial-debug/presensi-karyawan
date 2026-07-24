<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AttendanceCorrectionController extends Controller
{
    /**
     * Menampilkan formulir pembuatan
     * presensi manual.
     */
    public function create(Request $request): View
    {
        $this->ensureHrd($request);

        return view(
            'attendance-corrections.create',
            [
                'employees' => $this->activeEmployees(),

                'defaultAttendanceDate' => CarbonImmutable::now(
                    $this->timezone()
                )->format('Y-m-d'),
            ]
        );
    }

    /**
     * Menyimpan presensi manual baru.
     */
    public function store(
        StoreAttendanceCorrectionRequest $request
    ): RedirectResponse {
        $attendance = DB::transaction(
            function () use ($request): Attendance {
                $hrd = $request->user();

                abort_unless(
                    $hrd !== null
                    && $hrd->role === 'hrd'
                    && $hrd->status === 'active',
                    403
                );

                [
                    $employee,
                    $employeeSchedule,
                    $workSchedule,
                    $branch,
                ] = $this->resolveCorrectionContext(
                    $request
                );

                $this->ensureAttendanceIsUnique(
                    employeeScheduleId: (int) $employeeSchedule->id,

                    attendanceType: $request->attendanceType(),

                    ignoredAttendanceId: null
                );

                $correctionMoment =
                    CarbonImmutable::now(
                        $this->timezone()
                    );

                $attendance =
                    Attendance::query()->create(
                        $this->manualAttributes(
                            request: $request,
                            employee: $employee,
                            employeeSchedule: $employeeSchedule,
                            workSchedule: $workSchedule,
                            branch: $branch,
                            correctedBy: (int) $hrd->id,
                            correctedAt: $correctionMoment
                        )
                    );

                $attendance->refresh();

                AttendanceCorrection::query()->create([
                    'attendance_id' => $attendance->id,

                    'corrected_by' => $hrd->id,

                    'action' => AttendanceCorrection::ACTION_CREATE,

                    'reason' => $request->correctionReason(),

                    'before_data' => null,

                    'after_data' => $this->attendanceSnapshot(
                        $attendance
                    ),
                ]);

                return $attendance;
            },
            3
        );

        return redirect()
            ->route(
                'attendance-monitoring.index',
                [
                    'attendance_date' => $attendance
                        ->attendance_date
                        ->format('Y-m-d'),

                    'employee_id' => $attendance->employee_id,
                ]
            )
            ->with(
                'success',
                'Presensi manual berhasil ditambahkan.'
            );
    }

    /**
     * Menampilkan formulir perubahan presensi.
     */
    public function edit(
        Request $request,
        Attendance $attendance
    ): View {
        $this->ensureHrd($request);

        $attendance->load([
            'employee.branch',
            'employeeSchedule.workSchedule',
            'lastCorrectedBy',
            'corrections.correctedBy',
        ]);

        return view(
            'attendance-corrections.edit',
            [
                'attendance' => $attendance,

                'employees' => $this->activeEmployees(),
            ]
        );
    }

    /**
     * Memperbarui transaksi presensi dan
     * mencatat jejak auditnya.
     */
    public function update(
        StoreAttendanceCorrectionRequest $request,
        Attendance $attendance
    ): RedirectResponse {
        $updatedAttendance = DB::transaction(
            function () use (
                $request,
                $attendance
            ): Attendance {
                $hrd = $request->user();

                abort_unless(
                    $hrd !== null
                    && $hrd->role === 'hrd'
                    && $hrd->status === 'active',
                    403
                );

                [
                    $employee,
                    $employeeSchedule,
                    $workSchedule,
                    $branch,
                ] = $this->resolveCorrectionContext(
                    $request
                );

                $lockedAttendance =
                    Attendance::query()
                        ->whereKey(
                            $attendance->getKey()
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->ensureAttendanceIsUnique(
                    employeeScheduleId: (int) $employeeSchedule->id,

                    attendanceType: $request->attendanceType(),

                    ignoredAttendanceId: (int) $lockedAttendance->id
                );

                $beforeData =
                    $this->attendanceSnapshot(
                        $lockedAttendance
                    );

                $correctionMoment =
                    CarbonImmutable::now(
                        $this->timezone()
                    );

                $lockedAttendance->fill(
                    $this->manualAttributes(
                        request: $request,
                        employee: $employee,
                        employeeSchedule: $employeeSchedule,
                        workSchedule: $workSchedule,
                        branch: $branch,
                        correctedBy: (int) $hrd->id,
                        correctedAt: $correctionMoment
                    )
                );

                $lockedAttendance->save();
                $lockedAttendance->refresh();

                AttendanceCorrection::query()->create([
                    'attendance_id' => $lockedAttendance->id,

                    'corrected_by' => $hrd->id,

                    'action' => AttendanceCorrection::ACTION_UPDATE,

                    'reason' => $request->correctionReason(),

                    'before_data' => $beforeData,

                    'after_data' => $this->attendanceSnapshot(
                        $lockedAttendance
                    ),
                ]);

                return $lockedAttendance;
            },
            3
        );

        return redirect()
            ->route(
                'attendance-monitoring.index',
                [
                    'attendance_date' => $updatedAttendance
                        ->attendance_date
                        ->format('Y-m-d'),

                    'employee_id' => $updatedAttendance->employee_id,
                ]
            )
            ->with(
                'success',
                'Data presensi berhasil dikoreksi.'
            );
    }

    /**
     * Memastikan pengguna merupakan HRD aktif.
     */
    private function ensureHrd(
        Request $request
    ): void {
        $user = $request->user();

        abort_unless(
            $user !== null
            && $user->role === 'hrd'
            && $user->status === 'active',
            403
        );
    }

    /**
     * Mengambil karyawan aktif dengan cabang
     * aktif untuk pilihan formulir.
     *
     * @return Collection<int, Employee>
     */
    private function activeEmployees(): Collection
    {
        return Employee::query()
            ->with('branch')
            ->where(
                'employment_status',
                'active'
            )
            ->whereHas(
                'branch',
                static fn ($query) => $query->where(
                    'status',
                    'active'
                )
            )
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Mengambil konteks karyawan, jadwal,
     * pola jadwal, dan cabang dengan lock.
     *
     * @return array{
     *     0: Employee,
     *     1: EmployeeSchedule,
     *     2: WorkSchedule,
     *     3: Branch
     * }
     */
    private function resolveCorrectionContext(
        StoreAttendanceCorrectionRequest $request
    ): array {
        $employee = Employee::query()
            ->whereKey(
                $request->employeeId()
            )
            ->where(
                'employment_status',
                'active'
            )
            ->lockForUpdate()
            ->first();

        if ($employee === null) {
            throw ValidationException::withMessages([
                'employee_id' => 'Karyawan aktif tidak ditemukan.',
            ]);
        }

        $branch = Branch::query()
            ->whereKey(
                $employee->branch_id
            )
            ->where(
                'status',
                'active'
            )
            ->lockForUpdate()
            ->first();

        if ($branch === null) {
            throw ValidationException::withMessages([
                'employee_id' => 'Cabang penempatan karyawan tidak aktif atau tidak ditemukan.',
            ]);
        }

        $employeeSchedule =
            EmployeeSchedule::query()
                ->where(
                    'employee_id',
                    $employee->id
                )
                ->whereDate(
                    'schedule_date',
                    $request->attendanceDate()
                )
                ->lockForUpdate()
                ->first();

        if ($employeeSchedule === null) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Jadwal karyawan pada tanggal tersebut tidak ditemukan.',
            ]);
        }

        if (
            $employeeSchedule->schedule_status
            !== 'work'
        ) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Koreksi presensi hanya dapat dilakukan pada jadwal kerja.',
            ]);
        }

        $workSchedule = WorkSchedule::query()
            ->whereKey(
                $employeeSchedule->work_schedule_id
            )
            ->lockForUpdate()
            ->first();

        if ($workSchedule === null) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Pola jadwal kerja tidak ditemukan.',
            ]);
        }

        return [
            $employee,
            $employeeSchedule,
            $workSchedule,
            $branch,
        ];
    }

    /**
     * Mencegah dua presensi dengan jenis sama
     * pada jadwal karyawan yang sama.
     */
    private function ensureAttendanceIsUnique(
        int $employeeScheduleId,
        string $attendanceType,
        ?int $ignoredAttendanceId
    ): void {
        $query = Attendance::query()
            ->where(
                'employee_schedule_id',
                $employeeScheduleId
            )
            ->where(
                'attendance_type',
                $attendanceType
            );

        if ($ignoredAttendanceId !== null) {
            $query->where(
                'id',
                '<>',
                $ignoredAttendanceId
            );
        }

        if ($query->exists()) {
            $label = $attendanceType === 'check_in'
                ? 'presensi masuk'
                : 'presensi pulang';

            throw ValidationException::withMessages([
                'attendance_type' => "Karyawan sudah memiliki {$label} pada jadwal tersebut.",
            ]);
        }
    }

    /**
     * Membentuk atribut presensi hasil
     * koreksi manual.
     *
     * @return array<string, mixed>
     */
    private function manualAttributes(
        StoreAttendanceCorrectionRequest $request,
        Employee $employee,
        EmployeeSchedule $employeeSchedule,
        WorkSchedule $workSchedule,
        Branch $branch,
        int $correctedBy,
        CarbonImmutable $correctedAt
    ): array {
        $attendanceMoment =
            $request->attendanceMoment();

        return [
            'employee_id' => $employee->id,

            /*
            |--------------------------------------------------------------------------
            | Metadata Pemindai
            |--------------------------------------------------------------------------
            |
            | Koreksi manual tidak menggunakan sesi QR,
            | koordinat perangkat, accuracy, atau Haversine.
            |
            */
            'attendance_session_id' => null,

            'employee_schedule_id' => $employeeSchedule->id,

            'branch_id' => $branch->id,

            'attendance_type' => $request->attendanceType(),

            'attendance_date' => $request->attendanceDate(),

            'attendance_time' => $attendanceMoment,

            'latitude' => null,
            'longitude' => null,
            'accuracy' => null,
            'distance' => null,
            'geofence_radius' => null,

            'attendance_status' => 'present',

            'punctuality_status' => $this->punctualityStatus(
                attendanceType: $request->attendanceType(),

                attendanceMoment: $attendanceMoment,

                attendanceDate: $request->attendanceDate(),

                workSchedule: $workSchedule
            ),

            'validation_status' => 'accepted',

            'record_source' => Attendance::RECORD_SOURCE_MANUAL,

            'last_corrected_by' => $correctedBy,

            'last_correction_reason' => $request->correctionReason(),

            'last_corrected_at' => $correctedAt,
        ];
    }

    /**
     * Menentukan status tepat waktu atau
     * terlambat berdasarkan pola jadwal.
     */
    private function punctualityStatus(
        string $attendanceType,
        CarbonImmutable $attendanceMoment,
        string $attendanceDate,
        WorkSchedule $workSchedule
    ): string {
        if ($attendanceType === 'check_out') {
            return 'not_applicable';
        }

        $scheduledCheckIn =
            CarbonImmutable::parse(
                sprintf(
                    '%s %s',
                    $attendanceDate,
                    $this->normalizeTime(
                        $workSchedule->check_in_time
                    )
                ),
                $this->timezone()
            );

        $lateDeadline =
            $scheduledCheckIn->addMinutes(
                (int) $workSchedule
                    ->late_tolerance_minutes
            );

        return $attendanceMoment->lessThanOrEqualTo(
            $lateDeadline
        )
            ? 'on_time'
            : 'late';
    }

    /**
     * Menormalkan nilai waktu jadwal menjadi
     * format H:i:s.
     */
    private function normalizeTime(
        mixed $value
    ): string {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i:s');
        }

        $value = trim(
            (string) $value
        );

        if (
            preg_match(
                '/^\d{2}:\d{2}$/',
                $value
            ) === 1
        ) {
            return "{$value}:00";
        }

        if (
            preg_match(
                '/^\d{2}:\d{2}:\d{2}$/',
                $value
            ) === 1
        ) {
            return $value;
        }

        return CarbonImmutable::parse(
            $value,
            $this->timezone()
        )->format('H:i:s');
    }

    /**
     * Membuat salinan data presensi untuk
     * kebutuhan audit.
     *
     * @return array<string, mixed>
     */
    private function attendanceSnapshot(
        Attendance $attendance
    ): array {
        return [
            'id' => (int) $attendance->id,

            'employee_id' => (int) $attendance->employee_id,

            'attendance_session_id' => $attendance->attendance_session_id
                    !== null
                        ? (int) $attendance
                            ->attendance_session_id
                        : null,

            'employee_schedule_id' => (int) $attendance
                ->employee_schedule_id,

            'branch_id' => (int) $attendance->branch_id,

            'attendance_type' => (string) $attendance
                ->attendance_type,

            'attendance_date' => $this->dateString(
                $attendance->attendance_date
            ),

            'attendance_time' => $this->dateTimeString(
                $attendance->attendance_time
            ),

            'latitude' => $this->numericOrNull(
                $attendance->latitude
            ),

            'longitude' => $this->numericOrNull(
                $attendance->longitude
            ),

            'accuracy' => $this->numericOrNull(
                $attendance->accuracy
            ),

            'distance' => $this->numericOrNull(
                $attendance->distance
            ),

            'geofence_radius' => $this->numericOrNull(
                $attendance->geofence_radius
            ),

            'attendance_status' => (string) $attendance
                ->attendance_status,

            'punctuality_status' => (string) $attendance
                ->punctuality_status,

            'validation_status' => (string) $attendance
                ->validation_status,

            'record_source' => (string) $attendance
                ->record_source,

            'last_corrected_by' => $attendance->last_corrected_by
                    !== null
                        ? (int) $attendance
                            ->last_corrected_by
                        : null,

            'last_correction_reason' => $attendance
                ->last_correction_reason,

            'last_corrected_at' => $this->dateTimeString(
                $attendance
                    ->last_corrected_at
            ),
        ];
    }

    /**
     * Mengubah nilai tanggal menjadi Y-m-d.
     */
    private function dateString(
        mixed $value
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return substr(
            (string) $value,
            0,
            10
        );
    }

    /**
     * Mengubah nilai waktu menjadi format
     * tanggal dan waktu database.
     */
    private function dateTimeString(
        mixed $value
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(
                'Y-m-d H:i:s'
            );
        }

        return (string) $value;
    }

    /**
     * Mengubah nilai numerik menjadi float
     * atau null.
     */
    private function numericOrNull(
        mixed $value
    ): ?float {
        if (
            $value === null
            || $value === ''
            || ! is_numeric($value)
        ) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Zona waktu sistem.
     */
    private function timezone(): string
    {
        return (string) config(
            'app.timezone',
            'Asia/Jakarta'
        );
    }
}
