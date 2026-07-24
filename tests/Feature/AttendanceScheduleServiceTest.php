<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

final class AttendanceScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceScheduleService $attendanceScheduleService;

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attendanceScheduleService = app(
            AttendanceScheduleService::class
        );
    }

    public function test_find_for_date_returns_employee_schedule_and_work_schedule(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                scheduleOverrides: [
                    'schedule_date' => '2026-09-01',
                ]
            );

        $employee = Employee::query()->findOrFail(
            $employeeSchedule->employee_id
        );

        $result =
            $this->attendanceScheduleService
                ->findForDate(
                    $employee,
                    '2026-09-01'
                );

        $this->assertNotNull($result);

        $this->assertTrue(
            $result->is($employeeSchedule)
        );

        $this->assertTrue(
            $result->relationLoaded(
                'workSchedule'
            )
        );

        $this->assertNotNull(
            $result->workSchedule
        );
    }

    public function test_find_for_date_returns_null_when_schedule_does_not_exist(): void
    {
        $branch = Branch::factory()->create();

        $employee = $this->createEmployee(
            $branch
        );

        $result =
            $this->attendanceScheduleService
                ->findForDate(
                    $employee,
                    '2026-09-02'
                );

        $this->assertNull($result);
    }

    public function test_time_window_is_calculated_from_work_schedule(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                workScheduleOverrides: [
                    'check_in_time' => '08:45:00',

                    'check_out_time' => '17:00:00',

                    'check_in_open_minutes' => 30,

                    'late_tolerance_minutes' => 5,

                    'check_out_limit_minutes' => 60,
                ],
                scheduleOverrides: [
                    'schedule_date' => '2026-09-03',
                ]
            );

        $timeWindow =
            $this->attendanceScheduleService
                ->timeWindow(
                    $employeeSchedule
                );

        $this->assertSame(
            '2026-09-03 00:00:00',
            $timeWindow[
                'schedule_date'
            ]->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-09-03 08:15:00',
            $timeWindow[
                'check_in_opens_at'
            ]->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-09-03 08:45:00',
            $timeWindow[
                'scheduled_check_in_at'
            ]->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-09-03 08:50:00',
            $timeWindow[
                'late_limit_at'
            ]->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-09-03 17:00:00',
            $timeWindow[
                'scheduled_check_out_at'
            ]->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-09-03 18:00:00',
            $timeWindow[
                'check_out_limit_at'
            ]->format('Y-m-d H:i:s')
        );
    }

    public function test_check_in_before_opening_time_is_rejected(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                scheduleOverrides: [
                    'schedule_date' => '2026-09-04',
                ]
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_in',
                    '2026-09-04 08:14:59'
                );

        $this->assertFalse(
            $result['allowed']
        );

        $this->assertSame(
            'check_in_not_open',
            $result['code']
        );

        $this->assertNull(
            $result[
                'punctuality_status'
            ]
        );

        $this->assertSame(
            'Presensi masuk belum dibuka. Presensi dapat dilakukan mulai pukul 08:15 WIB.',
            $result['message']
        );
    }

    public function test_check_in_at_opening_time_is_accepted_as_on_time(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                scheduleOverrides: [
                    'schedule_date' => '2026-09-05',
                ]
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_in',
                    '2026-09-05 08:15:00'
                );

        $this->assertTrue(
            $result['allowed']
        );

        $this->assertSame(
            'accepted',
            $result['code']
        );

        $this->assertSame(
            'on_time',
            $result[
                'punctuality_status'
            ]
        );
    }

    public function test_check_in_at_late_tolerance_boundary_is_still_on_time(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                scheduleOverrides: [
                    'schedule_date' => '2026-09-06',
                ]
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_in',
                    '2026-09-06 08:50:00'
                );

        $this->assertTrue(
            $result['allowed']
        );

        $this->assertSame(
            'on_time',
            $result[
                'punctuality_status'
            ]
        );

        $this->assertSame(
            'Presensi masuk diterima dengan status tepat waktu.',
            $result['message']
        );
    }

    public function test_check_in_after_tolerance_boundary_is_accepted_as_late(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                scheduleOverrides: [
                    'schedule_date' => '2026-09-07',
                ]
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_in',
                    '2026-09-07 08:50:01'
                );

        $this->assertTrue(
            $result['allowed']
        );

        $this->assertSame(
            'accepted',
            $result['code']
        );

        $this->assertSame(
            'late',
            $result[
                'punctuality_status'
            ]
        );

        $this->assertSame(
            'Presensi masuk diterima dengan status terlambat.',
            $result['message']
        );
    }

    public function test_check_out_before_scheduled_time_is_rejected(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                scheduleOverrides: [
                    'schedule_date' => '2026-09-08',
                ]
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_out',
                    '2026-09-08 16:59:59'
                );

        $this->assertFalse(
            $result['allowed']
        );

        $this->assertSame(
            'check_out_too_early',
            $result['code']
        );

        $this->assertNull(
            $result[
                'punctuality_status'
            ]
        );

        $this->assertSame(
            'Presensi pulang belum diperbolehkan. Jadwal pulang adalah pukul 17:00 WIB.',
            $result['message']
        );
    }

    public function test_check_out_at_scheduled_time_is_accepted(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                scheduleOverrides: [
                    'schedule_date' => '2026-09-09',
                ]
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_out',
                    '2026-09-09 17:00:00'
                );

        $this->assertTrue(
            $result['allowed']
        );

        $this->assertSame(
            'accepted',
            $result['code']
        );

        $this->assertSame(
            'not_applicable',
            $result[
                'punctuality_status'
            ]
        );

        $this->assertSame(
            'Presensi pulang diterima.',
            $result['message']
        );
    }

    public function test_check_out_at_final_limit_is_still_accepted(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                scheduleOverrides: [
                    'schedule_date' => '2026-09-10',
                ]
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_out',
                    '2026-09-10 18:00:00'
                );

        $this->assertTrue(
            $result['allowed']
        );

        $this->assertSame(
            'not_applicable',
            $result[
                'punctuality_status'
            ]
        );
    }

    public function test_check_out_after_final_limit_is_rejected(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                scheduleOverrides: [
                    'schedule_date' => '2026-09-11',
                ]
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_out',
                    '2026-09-11 18:00:01'
                );

        $this->assertFalse(
            $result['allowed']
        );

        $this->assertSame(
            'check_out_limit_passed',
            $result['code']
        );

        $this->assertSame(
            'Batas akhir presensi pulang telah lewat pada pukul 18:00 WIB.',
            $result['message']
        );
    }

    public function test_day_off_schedule_is_rejected(): void
    {
        $employeeSchedule =
            $this->createNonWorkingSchedule(
                'off',
                '2026-09-12'
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_in',
                    '2026-09-12 08:45:00'
                );

        $this->assertFalse(
            $result['allowed']
        );

        $this->assertSame(
            'schedule_not_working',
            $result['code']
        );

        $this->assertSame(
            'Karyawan berstatus libur pada tanggal sesi.',
            $result['message']
        );
    }

    public function test_permit_schedule_is_rejected(): void
    {
        $employeeSchedule =
            $this->createNonWorkingSchedule(
                'permit',
                '2026-09-13'
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_in',
                    '2026-09-13 08:45:00'
                );

        $this->assertFalse(
            $result['allowed']
        );

        $this->assertSame(
            'schedule_not_working',
            $result['code']
        );

        $this->assertSame(
            'Karyawan berstatus izin pada tanggal sesi.',
            $result['message']
        );
    }

    public function test_sick_schedule_is_rejected(): void
    {
        $employeeSchedule =
            $this->createNonWorkingSchedule(
                'sick',
                '2026-09-14'
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_out',
                    '2026-09-14 17:00:00'
                );

        $this->assertFalse(
            $result['allowed']
        );

        $this->assertSame(
            'schedule_not_working',
            $result['code']
        );

        $this->assertSame(
            'Karyawan berstatus sakit pada tanggal sesi.',
            $result['message']
        );
    }

    public function test_work_schedule_without_work_pattern_is_rejected(): void
    {
        $branch = Branch::factory()->create();

        $employee = $this->createEmployee(
            $branch
        );

        $approver = $this->createHrd();

        $employeeSchedule =
            EmployeeSchedule::query()->create([
                'employee_id' => $employee->id,

                'work_schedule_id' => null,

                'schedule_date' => '2026-09-15',

                'schedule_status' => 'work',

                'approved_by' => $approver->id,

                'notes' => 'Data konfigurasi tidak lengkap',
            ]);

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_in',
                    '2026-09-15 08:45:00'
                );

        $this->assertFalse(
            $result['allowed']
        );

        $this->assertSame(
            'schedule_configuration_invalid',
            $result['code']
        );

        $this->assertSame(
            'Jadwal kerja karyawan tidak memiliki pola waktu yang valid.',
            $result['message']
        );
    }

    public function test_invalid_work_time_configuration_is_rejected(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                workScheduleOverrides: [
                    'check_in_time' => '17:00:00',

                    'check_out_time' => '08:45:00',
                ],
                scheduleOverrides: [
                    'schedule_date' => '2026-09-16',
                ]
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_in',
                    '2026-09-16 17:00:00'
                );

        $this->assertFalse(
            $result['allowed']
        );

        $this->assertSame(
            'schedule_configuration_invalid',
            $result['code']
        );

        $this->assertSame(
            'Konfigurasi waktu pada jadwal kerja tidak valid.',
            $result['message']
        );
    }

    public function test_attendance_on_different_schedule_date_is_rejected(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                scheduleOverrides: [
                    'schedule_date' => '2026-09-17',
                ]
            );

        $result =
            $this->attendanceScheduleService
                ->evaluate(
                    $employeeSchedule,
                    'check_in',
                    '2026-09-18 08:45:00'
                );

        $this->assertFalse(
            $result['allowed']
        );

        $this->assertSame(
            'outside_schedule_date',
            $result['code']
        );

        $this->assertSame(
            'Presensi tidak dilakukan pada tanggal jadwal karyawan.',
            $result['message']
        );
    }

    public function test_unsupported_attendance_type_throws_exception(): void
    {
        $employeeSchedule =
            $this->createWorkingSchedule(
                scheduleOverrides: [
                    'schedule_date' => '2026-09-19',
                ]
            );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Jenis presensi tidak didukung.'
        );

        $this->attendanceScheduleService
            ->evaluate(
                $employeeSchedule,
                'break',
                CarbonImmutable::parse(
                    '2026-09-19 12:00:00',
                    'Asia/Jakarta'
                )
            );
    }

    /**
     * @param array<string, mixed>
     *     $workScheduleOverrides
     * @param array<string, mixed>
     *     $scheduleOverrides
     */
    private function createWorkingSchedule(
        array $workScheduleOverrides = [],
        array $scheduleOverrides = []
    ): EmployeeSchedule {
        $branch = Branch::factory()->create();

        $employee = $this->createEmployee(
            $branch
        );

        $approver = $this->createHrd();

        $workSchedule =
            $this->createWorkSchedule(
                $workScheduleOverrides
            );

        return EmployeeSchedule::query()->create(
            array_replace(
                [
                    'employee_id' => $employee->id,

                    'work_schedule_id' => $workSchedule->id,

                    'schedule_date' => '2026-09-30',

                    'schedule_status' => 'work',

                    'approved_by' => $approver->id,

                    'notes' => null,
                ],
                $scheduleOverrides
            )
        );
    }

    private function createNonWorkingSchedule(
        string $scheduleStatus,
        string $scheduleDate
    ): EmployeeSchedule {
        $branch = Branch::factory()->create();

        $employee = $this->createEmployee(
            $branch
        );

        $approver = $this->createHrd();

        return EmployeeSchedule::query()->create([
            'employee_id' => $employee->id,

            'work_schedule_id' => null,

            'schedule_date' => $scheduleDate,

            'schedule_status' => $scheduleStatus,

            'approved_by' => $approver->id,

            'notes' => null,
        ]);
    }

    private function createHrd(): User
    {
        return User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createEmployee(
        Branch $branch,
        array $overrides = []
    ): Employee {
        $this->employeeSequence++;

        $employeeUser =
            User::factory()->create([
                'role' => 'employee',
                'status' => 'active',
            ]);

        return Employee::query()->create(
            array_replace(
                [
                    'user_id' => $employeeUser->id,

                    'branch_id' => $branch->id,

                    'employee_number' => sprintf(
                        'EMP-SCHEDULE-SERVICE-%03d',
                        $this->employeeSequence
                    ),

                    'full_name' => sprintf(
                        'Karyawan Schedule Service %03d',
                        $this->employeeSequence
                    ),

                    'position' => 'Karyawan',

                    'phone_number' => null,

                    'employment_status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createWorkSchedule(
        array $overrides = []
    ): WorkSchedule {
        $this->workScheduleSequence++;

        return WorkSchedule::query()->create(
            array_replace(
                [
                    'name' => sprintf(
                        'Pola Schedule Service %03d',
                        $this->workScheduleSequence
                    ),

                    'check_in_time' => '08:45:00',

                    'check_out_time' => '17:00:00',

                    'check_in_open_minutes' => 30,

                    'late_tolerance_minutes' => 5,

                    'check_out_limit_minutes' => 60,

                    'status' => 'active',
                ],
                $overrides
            )
        );
    }
}
