<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;

final class Aug13AttendanceHistoryRestorer
{
    private const RESTORATION_KEY = 'attendance-history-2026-08-13';

    private const DATE = '2026-08-13';

    private const SESSION_PUBLIC_ID = '019ff78f-75e0-72ab-875f-5f2938b67e70';

    /**
     * @var array<string, array<string, int|string|null>>
     */
    private const SCHEDULES = [
        'P001' => ['created_at' => '2026-08-11 22:46:53'],
        'P002' => ['created_at' => '2026-08-11 22:47:12'],
        'P003' => ['created_at' => '2026-08-11 22:47:28'],
        'P004' => ['created_at' => '2026-08-11 22:47:46'],
        'P005' => ['created_at' => '2026-08-11 22:48:06'],
    ];

    /**
     * Data asli dari basis data lokal sebelum aplikasi dipindahkan.
     *
     * @var list<array<string, int|string|null>>
     */
    private const ATTENDANCES = [
        [
            'employee_number' => 'P001',
            'attendance_type' => 'check_in',
            'attendance_time' => '2026-08-13 08:17:05',
            'latitude' => '3.53743474',
            'longitude' => '98.68448658',
            'accuracy' => '8.99',
            'distance' => '3.35',
            'punctuality_status' => 'on_time',
        ],
        [
            'employee_number' => 'P002',
            'attendance_type' => 'check_in',
            'attendance_time' => '2026-08-13 08:36:26',
            'latitude' => '3.53726418',
            'longitude' => '98.68432349',
            'accuracy' => '4.80',
            'distance' => '25.53',
            'punctuality_status' => 'on_time',
        ],
        [
            'employee_number' => 'P003',
            'attendance_type' => 'check_in',
            'attendance_time' => '2026-08-13 08:43:21',
            'latitude' => '3.53753151',
            'longitude' => '98.68449434',
            'accuracy' => '20.00',
            'distance' => '9.94',
            'punctuality_status' => 'on_time',
        ],
        [
            'employee_number' => 'P004',
            'attendance_type' => 'check_in',
            'attendance_time' => '2026-08-13 08:46:57',
            'latitude' => '3.53753151',
            'longitude' => '98.68449434',
            'accuracy' => '20.00',
            'distance' => '9.94',
            'punctuality_status' => 'on_time',
        ],
        [
            'employee_number' => 'P005',
            'attendance_type' => 'check_in',
            'attendance_time' => '2026-08-13 08:51:13',
            'latitude' => '3.53752894',
            'longitude' => '98.68449008',
            'accuracy' => '19.98',
            'distance' => '9.49',
            'punctuality_status' => 'late',
        ],
        [
            'employee_number' => 'P004',
            'attendance_type' => 'check_out',
            'attendance_time' => '2026-08-13 17:01:45',
            'latitude' => '3.53743474',
            'longitude' => '98.68448658',
            'accuracy' => '8.99',
            'distance' => '3.35',
            'punctuality_status' => 'not_applicable',
        ],
    ];

    /**
     * @return array<string, int|string>
     */
    public function restore(): array
    {
        return DB::transaction(function (): array {
            $references = $this->resolveReferences();
            $this->assertNoConflicts($references);

            $existingSnapshot = DB::table('data_restore_snapshots')
                ->where('restoration_key', self::RESTORATION_KEY)
                ->first();

            if ($existingSnapshot?->restored_at !== null) {
                $summary = $this->verifyRestoredState($references);
                $summary['status'] = 'already_restored';

                return $summary;
            }

            DB::table('data_restore_snapshots')->updateOrInsert(
                ['restoration_key' => self::RESTORATION_KEY],
                [
                    'snapshot_before' => json_encode(
                        $this->captureSnapshot($references),
                        JSON_THROW_ON_ERROR
                    ),
                    'result_summary' => null,
                    'restored_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $scheduleIds = $this->restoreSchedules($references);
            $sessionId = $this->restoreSession($references);

            $this->restoreAttendances(
                $references,
                $scheduleIds,
                $sessionId
            );

            $summary = $this->verifyRestoredState($references);
            $summary['status'] = 'restored';

            DB::table('data_restore_snapshots')
                ->where('restoration_key', self::RESTORATION_KEY)
                ->update([
                    'result_summary' => json_encode(
                        $summary,
                        JSON_THROW_ON_ERROR
                    ),
                    'restored_at' => now(),
                    'updated_at' => now(),
                ]);

            Log::info(
                'Riwayat presensi 13 Agustus 2026 berhasil dipulihkan.',
                $summary
            );

            return $summary;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveReferences(): array
    {
        $branch = DB::table('branches')
            ->where('code', 'CB02')
            ->first();

        $workSchedule = DB::table('work_schedules')
            ->where('name', 'Pulang Sore')
            ->first();

        $approver = DB::table('users')
            ->where('email', 'hrd@presensi.test')
            ->where('role', 'hrd')
            ->where('status', 'active')
            ->first();

        $employeeNumbers = array_keys(self::SCHEDULES);
        $employees = DB::table('employees')
            ->whereIn('employee_number', $employeeNumbers)
            ->get()
            ->keyBy('employee_number');

        if ($branch === null) {
            throw new RuntimeException('Cabang CB02 tidak ditemukan.');
        }

        if ($workSchedule === null) {
            throw new RuntimeException('Jadwal Pulang Sore tidak ditemukan.');
        }

        if ($approver === null) {
            throw new RuntimeException('Akun HRD aktif tidak ditemukan.');
        }

        if ($employees->count() !== count($employeeNumbers)) {
            throw new RuntimeException(
                'Akun P001 sampai P005 belum lengkap.'
            );
        }

        foreach ($employees as $employee) {
            if ((int) $employee->branch_id !== (int) $branch->id) {
                throw new RuntimeException(
                    "{$employee->employee_number} tidak berada di CB02."
                );
            }
        }

        $this->assertWorkScheduleMatches($workSchedule);

        $terminal = DB::table('branch_terminals')
            ->where('branch_id', $branch->id)
            ->where('status', 'active')
            ->orderByDesc('activated_at')
            ->orderByDesc('id')
            ->first();

        if ($terminal === null) {
            throw new RuntimeException(
                'Terminal aktif untuk CB02 tidak ditemukan.'
            );
        }

        return [
            'branch' => $branch,
            'work_schedule' => $workSchedule,
            'approver' => $approver,
            'employees' => $employees,
            'terminal' => $terminal,
        ];
    }

    private function assertWorkScheduleMatches(stdClass $workSchedule): void
    {
        $expected = [
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'check_in_limit_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
        ];

        foreach ($expected as $column => $value) {
            if ((string) $workSchedule->{$column} !== (string) $value) {
                throw new RuntimeException(
                    "Konfigurasi Pulang Sore berbeda pada {$column}."
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $references
     */
    private function assertNoConflicts(array $references): void
    {
        foreach (self::SCHEDULES as $employeeNumber => $source) {
            $employee = $references['employees']->get($employeeNumber);
            $schedule = DB::table('employee_schedules')
                ->where('employee_id', $employee->id)
                ->whereDate('schedule_date', self::DATE)
                ->first();

            if ($schedule === null) {
                continue;
            }

            $matches = (int) $schedule->work_schedule_id
                    === (int) $references['work_schedule']->id
                && $schedule->schedule_status === 'work'
                && $schedule->schedule_source === 'manual';

            if (! $matches) {
                throw new RuntimeException(
                    "Jadwal {$employeeNumber} tanggal 13 Agustus "
                    .'sudah ada tetapi isinya berbeda.'
                );
            }
        }

        $sessionByKey = DB::table('attendance_sessions')
            ->where('automation_key', $this->automationKey($references))
            ->first();
        $sessionByPublicId = DB::table('attendance_sessions')
            ->where('public_id', self::SESSION_PUBLIC_ID)
            ->first();

        if (
            $sessionByKey !== null
            && $sessionByPublicId !== null
            && (int) $sessionByKey->id !== (int) $sessionByPublicId->id
        ) {
            throw new RuntimeException(
                'Kunci sesi dan identitas sesi menunjuk dua data berbeda.'
            );
        }

        $session = $sessionByKey ?? $sessionByPublicId;

        if ($session !== null && ! $this->sessionMatches($session, $references)) {
            throw new RuntimeException(
                'Sesi tanggal 13 Agustus sudah ada tetapi isinya berbeda.'
            );
        }

        $expectedKeys = collect(self::ATTENDANCES)
            ->mapWithKeys(static fn (array $row): array => [
                $row['employee_number'].'|'.$row['attendance_type'] => true,
            ]);

        $existingAttendances = DB::table('attendances as a')
            ->join('employees as e', 'e.id', '=', 'a.employee_id')
            ->whereDate('a.attendance_date', self::DATE)
            ->whereIn('e.employee_number', array_keys(self::SCHEDULES))
            ->get([
                'a.*',
                'e.employee_number',
            ]);

        foreach ($existingAttendances as $attendance) {
            $key = $attendance->employee_number
                .'|'.$attendance->attendance_type;

            if (! $expectedKeys->has($key)) {
                throw new RuntimeException(
                    "Ditemukan transaksi tambahan {$key} pada 13 Agustus."
                );
            }

            $expected = collect(self::ATTENDANCES)->first(
                static fn (array $row): bool => $row['employee_number'] === $attendance->employee_number
                    && $row['attendance_type'] === $attendance->attendance_type
            );

            if (! $this->attendanceMatches($attendance, $expected)) {
                throw new RuntimeException(
                    "Transaksi {$key} sudah ada tetapi isinya berbeda."
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $references
     * @return array<string, mixed>
     */
    private function captureSnapshot(array $references): array
    {
        $employeeIds = $references['employees']->pluck('id')->all();

        return [
            'captured_at' => now()->toDateTimeString(),
            'branch_code' => 'CB02',
            'date' => self::DATE,
            'terminal_mapping' => [
                'id' => $references['terminal']->id,
                'name' => $references['terminal']->name,
            ],
            'employee_schedules' => DB::table('employee_schedules')
                ->whereDate('schedule_date', self::DATE)
                ->whereIn('employee_id', $employeeIds)
                ->orderBy('employee_id')
                ->get()
                ->map(static fn (stdClass $row): array => (array) $row)
                ->all(),
            'attendance_sessions' => DB::table('attendance_sessions')
                ->where('branch_id', $references['branch']->id)
                ->whereDate('session_date', self::DATE)
                ->orderBy('id')
                ->get()
                ->map(static function (stdClass $row): array {
                    $data = (array) $row;
                    unset($data['encrypted_secret']);

                    return $data;
                })
                ->all(),
            'attendances' => DB::table('attendances')
                ->whereDate('attendance_date', self::DATE)
                ->whereIn('employee_id', $employeeIds)
                ->orderBy('attendance_time')
                ->get()
                ->map(static fn (stdClass $row): array => (array) $row)
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $references
     * @return array<string, int>
     */
    private function restoreSchedules(array $references): array
    {
        $scheduleIds = [];

        foreach (self::SCHEDULES as $employeeNumber => $source) {
            $employee = $references['employees']->get($employeeNumber);
            $existing = DB::table('employee_schedules')
                ->where('employee_id', $employee->id)
                ->whereDate('schedule_date', self::DATE)
                ->first();

            if ($existing !== null) {
                $scheduleIds[$employeeNumber] = (int) $existing->id;

                continue;
            }

            $scheduleIds[$employeeNumber] = DB::table(
                'employee_schedules'
            )->insertGetId([
                'weekly_schedule_item_id' => null,
                'employee_id' => $employee->id,
                'work_schedule_id' => $references['work_schedule']->id,
                'schedule_date' => self::DATE,
                'schedule_status' => 'work',
                'schedule_source' => 'manual',
                'work_schedule_name_snapshot' => null,
                'check_in_time_snapshot' => null,
                'check_out_time_snapshot' => null,
                'check_in_open_minutes_snapshot' => null,
                'check_in_limit_minutes_snapshot' => null,
                'late_tolerance_minutes_snapshot' => null,
                'check_out_limit_minutes_snapshot' => null,
                'approved_by' => $references['approver']->id,
                'notes' => null,
                'created_at' => $source['created_at'],
                'updated_at' => $source['created_at'],
            ]);
        }

        return $scheduleIds;
    }

    /**
     * @param  array<string, mixed>  $references
     */
    private function restoreSession(array $references): int
    {
        $existing = DB::table('attendance_sessions')
            ->where('automation_key', $this->automationKey($references))
            ->orWhere('public_id', self::SESSION_PUBLIC_ID)
            ->first();

        if ($existing !== null) {
            return (int) $existing->id;
        }

        return DB::table('attendance_sessions')->insertGetId([
            'public_id' => self::SESSION_PUBLIC_ID,
            'branch_id' => $references['branch']->id,
            'weekly_schedule_id' => null,
            'attendance_type' => 'auto',
            'session_source' => 'automatic',
            'automation_key' => $this->automationKey($references),
            'session_date' => self::DATE,
            'start_time' => '2026-08-13 08:15:00',
            'end_time' => '2026-08-13 18:00:00',
            'encrypted_secret' => Crypt::encryptString(Str::random(64)),
            'status' => 'expired',
            'created_by' => null,
            'closed_at' => null,
            'created_at' => '2026-08-13 03:00:02',
            'updated_at' => '2026-08-13 18:13:08',
        ]);
    }

    /**
     * @param  array<string, mixed>  $references
     * @param  array<string, int>  $scheduleIds
     */
    private function restoreAttendances(
        array $references,
        array $scheduleIds,
        int $sessionId
    ): void {
        foreach (self::ATTENDANCES as $source) {
            $employee = $references['employees']->get(
                $source['employee_number']
            );
            $scheduleId = $scheduleIds[$source['employee_number']];

            $existing = DB::table('attendances')
                ->where('employee_schedule_id', $scheduleId)
                ->where('attendance_type', $source['attendance_type'])
                ->first();

            if ($existing !== null) {
                continue;
            }

            DB::table('attendances')->insert([
                'employee_id' => $employee->id,
                'attendance_session_id' => $sessionId,
                'branch_terminal_id' => $references['terminal']->id,
                'employee_schedule_id' => $scheduleId,
                'branch_id' => $references['branch']->id,
                'attendance_type' => $source['attendance_type'],
                'attendance_date' => self::DATE,
                'attendance_time' => $source['attendance_time'],
                'latitude' => $source['latitude'],
                'longitude' => $source['longitude'],
                'accuracy' => $source['accuracy'],
                'distance' => $source['distance'],
                'geofence_radius' => '30.00',
                'attendance_status' => 'present',
                'punctuality_status' => $source['punctuality_status'],
                'late_minutes' => null,
                'validation_status' => 'accepted',
                'record_source' => 'scanner',
                'last_corrected_by' => null,
                'last_correction_reason' => null,
                'last_corrected_at' => null,
                'created_at' => $source['attendance_time'],
                'updated_at' => $source['attendance_time'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $references
     * @return array<string, int|string>
     */
    private function verifyRestoredState(array $references): array
    {
        $employeeIds = $references['employees']->pluck('id')->all();
        $scheduleCount = DB::table('employee_schedules')
            ->whereDate('schedule_date', self::DATE)
            ->whereIn('employee_id', $employeeIds)
            ->count();
        $sessionCount = DB::table('attendance_sessions')
            ->where('public_id', self::SESSION_PUBLIC_ID)
            ->where('automation_key', $this->automationKey($references))
            ->count();
        $attendanceCount = DB::table('attendances')
            ->whereDate('attendance_date', self::DATE)
            ->whereIn('employee_id', $employeeIds)
            ->count();

        if ($scheduleCount !== 5 || $sessionCount !== 1 || $attendanceCount !== 6) {
            throw new RuntimeException(
                'Hasil pemulihan tidak lengkap: '
                ."{$scheduleCount} jadwal, {$sessionCount} sesi, "
                ."{$attendanceCount} transaksi."
            );
        }

        return [
            'date' => self::DATE,
            'branch' => 'CB02',
            'employee_schedules' => $scheduleCount,
            'attendance_sessions' => $sessionCount,
            'attendances' => $attendanceCount,
        ];
    }

    /**
     * @param  array<string, mixed>  $references
     */
    private function automationKey(array $references): string
    {
        return 'AUTO:'.$references['branch']->id.':'.self::DATE;
    }

    /**
     * @param  array<string, mixed>  $references
     */
    private function sessionMatches(
        stdClass $session,
        array $references
    ): bool {
        return (int) $session->branch_id === (int) $references['branch']->id
            && $session->public_id === self::SESSION_PUBLIC_ID
            && $session->attendance_type === 'auto'
            && $session->session_source === 'automatic'
            && $session->automation_key === $this->automationKey($references)
            && (string) $session->session_date === self::DATE
            && (string) $session->start_time === '2026-08-13 08:15:00'
            && (string) $session->end_time === '2026-08-13 18:00:00';
    }

    /**
     * @param  array<string, int|string|null>  $expected
     */
    private function attendanceMatches(
        stdClass $attendance,
        array $expected
    ): bool {
        $textColumns = [
            'attendance_type',
            'attendance_time',
            'punctuality_status',
        ];

        foreach ($textColumns as $column) {
            if ((string) $attendance->{$column} !== (string) $expected[$column]) {
                return false;
            }
        }

        foreach (['latitude', 'longitude', 'accuracy', 'distance'] as $column) {
            if (
                abs(
                    (float) $attendance->{$column}
                    - (float) $expected[$column]
                ) > 0.00000001
            ) {
                return false;
            }
        }

        return $attendance->attendance_status === 'present'
            && $attendance->validation_status === 'accepted'
            && $attendance->record_source === 'scanner';
    }
}
