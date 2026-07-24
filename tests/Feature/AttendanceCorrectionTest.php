<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(
            route('attendance-corrections.create')
        )->assertRedirect(route('login'));
    }

    public function test_admin_and_employee_cannot_access_correction_pages(): void
    {
        $hrd = $this->createHrd();
        $admin = $this->createAdmin();
        $branch = $this->createBranch();
        $employee = $this->createEmployee($branch);
        $employeeUser = User::query()->findOrFail(
            $employee->user_id
        );

        $workSchedule = $this->createWorkSchedule();
        $employeeSchedule = $this->createEmployeeSchedule(
            employee: $employee,
            workSchedule: $workSchedule,
            approver: $hrd
        );

        $attendance = $this->createScannerAttendance(
            employeeSchedule: $employeeSchedule,
            employee: $employee,
            branch: $branch,
            creator: $hrd
        );

        foreach ([$admin, $employeeUser] as $user) {
            $this->actingAs($user)
                ->get(
                    route(
                        'attendance-corrections.create'
                    )
                )
                ->assertForbidden();

            $this->actingAs($user)
                ->get(
                    route(
                        'attendance-corrections.edit',
                        $attendance
                    )
                )
                ->assertForbidden();
        }
    }

    public function test_hrd_can_open_create_and_edit_pages(): void
    {
    
        $hrd = $this->createHrd();
        $branch = $this->createBranch();

        $employee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-CORRECTION-PAGE',

                'full_name' => 'Karyawan Halaman Koreksi',
            ]
        );

        $workSchedule = $this->createWorkSchedule();

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                workSchedule: $workSchedule,
                approver: $hrd
            );

        $attendance = $this->createScannerAttendance(
            employeeSchedule: $employeeSchedule,
            employee: $employee,
            branch: $branch,
            creator: $hrd
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-corrections.create'
                )
            )
            ->assertOk()
            ->assertViewIs(
                'attendance-corrections.create'
            )
            ->assertSee(
                'Tambah Presensi Manual'
            )
            ->assertSee(
                'EMP-CORRECTION-PAGE'
            )
            ->assertSee(
                'Karyawan Halaman Koreksi'
            );
   
        $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-corrections.edit',
                    $attendance
                )
            )
            ->assertOk()
            ->assertViewIs(
                'attendance-corrections.edit'
            )
            ->assertSee(
                'Koreksi Data Presensi'
            )
            ->assertSee(
                'EMP-CORRECTION-PAGE'
            )
            ->assertSee(
                'Karyawan Halaman Koreksi'
            );
    }

    public function test_hrd_can_store_on_time_manual_check_in_with_audit(): void
    {
        $hrd = $this->createHrd();
        $branch = $this->createBranch();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                workSchedule: $workSchedule,
                approver: $hrd
            );

        $date = $employeeSchedule
            ->schedule_date
            ->format('Y-m-d');

        $response = $this->actingAs($hrd)
            ->post(
                route(
                    'attendance-corrections.store'
                ),
                [
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'attendance_type' => 'check_in',
                    'attendance_time' => '08:50',
                    'reason' => ' Presensi masuk dibuat berdasarkan verifikasi HRD. ',
                ]
            );

        $attendance = Attendance::query()
            ->firstOrFail();

        $response
            ->assertRedirect(
                route(
                    'attendance-monitoring.index',
                    [
                        'attendance_date' => $date,

                        'employee_id' => $employee->id,
                    ]
                )
            )
            ->assertSessionHas(
                'success',
                'Presensi manual berhasil ditambahkan.'
            );

        $this->assertSame(
            $employee->id,
            $attendance->employee_id
        );

        $this->assertSame(
            $employeeSchedule->id,
            $attendance->employee_schedule_id
        );

        $this->assertSame(
            $branch->id,
            $attendance->branch_id
        );

        $this->assertSame(
            'check_in',
            $attendance->attendance_type
        );

        $this->assertSame(
            $date,
            $attendance->attendance_date
                ->format('Y-m-d')
        );

        $this->assertSame(
            "{$date} 08:50:00",
            $attendance->attendance_time
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            'present',
            $attendance->attendance_status
        );

        $this->assertSame(
            'on_time',
            $attendance->punctuality_status
        );

        $this->assertSame(
            'accepted',
            $attendance->validation_status
        );

        $this->assertSame(
            Attendance::RECORD_SOURCE_MANUAL,
            $attendance->record_source
        );

        $this->assertNull(
            $attendance->attendance_session_id
        );

        $this->assertNull($attendance->latitude);
        $this->assertNull($attendance->longitude);
        $this->assertNull($attendance->accuracy);
        $this->assertNull($attendance->distance);

        $this->assertNull(
            $attendance->geofence_radius
        );

        $this->assertSame(
            $hrd->id,
            $attendance->last_corrected_by
        );

        $this->assertSame(
            'Presensi masuk dibuat berdasarkan verifikasi HRD.',
            $attendance->last_correction_reason
        );

        $this->assertNotNull(
            $attendance->last_corrected_at
        );

        $correction =
            AttendanceCorrection::query()
                ->firstOrFail();

        $this->assertSame(
            $attendance->id,
            $correction->attendance_id
        );

        $this->assertSame(
            $hrd->id,
            $correction->corrected_by
        );

        $this->assertSame(
            AttendanceCorrection::ACTION_CREATE,
            $correction->action
        );

        $this->assertSame(
            'Presensi masuk dibuat berdasarkan verifikasi HRD.',
            $correction->reason
        );

        $this->assertNull(
            $correction->before_data
        );

        $this->assertSame(
            Attendance::RECORD_SOURCE_MANUAL,
            $correction->after_data[
                'record_source'
            ]
        );

        $this->assertSame(
            'on_time',
            $correction->after_data[
                'punctuality_status'
            ]
        );

        $this->assertDatabaseCount(
            'attendances',
            1
        );

        $this->assertDatabaseCount(
            'attendance_corrections',
            1
        );
    }

    public function test_manual_check_in_after_tolerance_is_late(): void
    {
        $hrd = $this->createHrd();
        $branch = $this->createBranch();
        $employee = $this->createEmployee($branch);

        $workSchedule = $this->createWorkSchedule([
            'check_in_time' => '08:45:00',
            'late_tolerance_minutes' => 5,
        ]);

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                workSchedule: $workSchedule,
                approver: $hrd
            );

        $date = $employeeSchedule
            ->schedule_date
            ->format('Y-m-d');

        $this->actingAs($hrd)
            ->post(
                route(
                    'attendance-corrections.store'
                ),
                [
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'attendance_type' => 'check_in',
                    'attendance_time' => '08:51',
                    'reason' => 'Presensi masuk terlambat telah diverifikasi HRD.',
                ]
            )
            ->assertSessionHasNoErrors();

        $attendance = Attendance::query()
            ->firstOrFail();

        $this->assertSame(
            'late',
            $attendance->punctuality_status
        );
    }

    public function test_manual_check_out_uses_not_applicable_punctuality(): void
    {
        $hrd = $this->createHrd();
        $branch = $this->createBranch();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                workSchedule: $workSchedule,
                approver: $hrd
            );

        $date = $employeeSchedule
            ->schedule_date
            ->format('Y-m-d');

        $this->actingAs($hrd)
            ->post(
                route(
                    'attendance-corrections.store'
                ),
                [
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'attendance_type' => 'check_out',
                    'attendance_time' => '17:00',
                    'reason' => 'Presensi pulang dibuat berdasarkan verifikasi HRD.',
                ]
            )
            ->assertSessionHasNoErrors();

        $attendance = Attendance::query()
            ->firstOrFail();

        $this->assertSame(
            'check_out',
            $attendance->attendance_type
        );

        $this->assertSame(
            'not_applicable',
            $attendance->punctuality_status
        );
    }

    public function test_manual_attendance_is_rejected_when_schedule_is_missing(): void
    {
        $hrd = $this->createHrd();
        $branch = $this->createBranch();
        $employee = $this->createEmployee($branch);
        $date = $this->attendanceDate();

        $this->actingAs($hrd)
            ->from(
                route(
                    'attendance-corrections.create'
                )
            )
            ->post(
                route(
                    'attendance-corrections.store'
                ),
                [
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'attendance_type' => 'check_in',
                    'attendance_time' => '08:45',
                    'reason' => 'Presensi manual tanpa jadwal tidak boleh diterima.',
                ]
            )
            ->assertRedirect(
                route(
                    'attendance-corrections.create'
                )
            )
            ->assertSessionHasErrors([
                'attendance_date',
            ]);

        $this->assertDatabaseCount(
            'attendances',
            0
        );

        $this->assertDatabaseCount(
            'attendance_corrections',
            0
        );
    }

    public function test_duplicate_attendance_type_on_same_schedule_is_rejected(): void
    {
        $hrd = $this->createHrd();
        $branch = $this->createBranch();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                workSchedule: $workSchedule,
                approver: $hrd
            );

        $this->createScannerAttendance(
            employeeSchedule: $employeeSchedule,
            employee: $employee,
            branch: $branch,
            creator: $hrd
        );

        $date = $employeeSchedule
            ->schedule_date
            ->format('Y-m-d');

        $this->actingAs($hrd)
            ->from(
                route(
                    'attendance-corrections.create'
                )
            )
            ->post(
                route(
                    'attendance-corrections.store'
                ),
                [
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'attendance_type' => 'check_in',
                    'attendance_time' => '08:50',
                    'reason' => 'Percobaan presensi masuk ganda untuk pengujian.',
                ]
            )
            ->assertRedirect(
                route(
                    'attendance-corrections.create'
                )
            )
            ->assertSessionHasErrors([
                'attendance_type',
            ]);

        $this->assertDatabaseCount(
            'attendances',
            1
        );

        $this->assertDatabaseCount(
            'attendance_corrections',
            0
        );
    }

    public function test_hrd_can_change_scanner_attendance_to_manual_and_store_audit(): void
    {
        $hrd = $this->createHrd();
        $branch = $this->createBranch();
        $employee = $this->createEmployee($branch);

        $workSchedule = $this->createWorkSchedule([
            'check_in_time' => '08:45:00',
            'late_tolerance_minutes' => 5,
        ]);

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                workSchedule: $workSchedule,
                approver: $hrd
            );

        $attendance = $this->createScannerAttendance(
            employeeSchedule: $employeeSchedule,
            employee: $employee,
            branch: $branch,
            creator: $hrd
        );

        $originalSessionId =
            $attendance->attendance_session_id;

        $date = $employeeSchedule
            ->schedule_date
            ->format('Y-m-d');

        $response = $this->actingAs($hrd)
            ->put(
                route(
                    'attendance-corrections.update',
                    $attendance
                ),
                [
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'attendance_type' => 'check_in',
                    'attendance_time' => '09:10',
                    'reason' => ' Waktu presensi diperbarui berdasarkan pemeriksaan HRD. ',
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'attendance-monitoring.index',
                    [
                        'attendance_date' => $date,

                        'employee_id' => $employee->id,
                    ]
                )
            )
            ->assertSessionHas(
                'success',
                'Data presensi berhasil dikoreksi.'
            );

        $attendance->refresh();

        $this->assertSame(
            "{$date} 09:10:00",
            $attendance->attendance_time
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            Attendance::RECORD_SOURCE_MANUAL,
            $attendance->record_source
        );

        $this->assertSame(
            'late',
            $attendance->punctuality_status
        );

        $this->assertNull(
            $attendance->attendance_session_id
        );

        $this->assertNull($attendance->latitude);
        $this->assertNull($attendance->longitude);
        $this->assertNull($attendance->accuracy);
        $this->assertNull($attendance->distance);

        $this->assertNull(
            $attendance->geofence_radius
        );

        $this->assertSame(
            $hrd->id,
            $attendance->last_corrected_by
        );

        $this->assertSame(
            'Waktu presensi diperbarui berdasarkan pemeriksaan HRD.',
            $attendance->last_correction_reason
        );

        $correction =
            AttendanceCorrection::query()
                ->firstOrFail();

        $this->assertSame(
            AttendanceCorrection::ACTION_UPDATE,
            $correction->action
        );

        $this->assertSame(
            $hrd->id,
            $correction->corrected_by
        );

        $this->assertSame(
            'Waktu presensi diperbarui berdasarkan pemeriksaan HRD.',
            $correction->reason
        );

        $this->assertSame(
            Attendance::RECORD_SOURCE_SCANNER,
            $correction->before_data[
                'record_source'
            ]
        );

        $this->assertSame(
            $originalSessionId,
            $correction->before_data[
                'attendance_session_id'
            ]
        );

        $this->assertSame(
            3.595196,
            $correction->before_data[
                'latitude'
            ]
        );

        $this->assertEqualsWithDelta(
            5.0,
            (float) $correction->before_data[
            'distance'
    ],
            0.001
        );

        $this->assertSame(
            Attendance::RECORD_SOURCE_MANUAL,
            $correction->after_data[
                'record_source'
            ]
        );

        $this->assertNull(
            $correction->after_data[
                'attendance_session_id'
            ]
        );

        $this->assertNull(
            $correction->after_data[
                'latitude'
            ]
        );

        $this->assertNull(
            $correction->after_data[
                'distance'
            ]
        );

        $this->assertSame(
            'late',
            $correction->after_data[
                'punctuality_status'
            ]
        );

        $this->assertDatabaseCount(
            'attendances',
            1
        );

        $this->assertDatabaseCount(
            'attendance_corrections',
            1
        );
    }

    private function createHrd(): User
    {
        return User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBranch(
        array $overrides = []
    ): Branch {
        return Branch::factory()->create(
            array_replace(
                [
                    'code' => 'CORRECTION-BRANCH',
                    'name' => 'Cabang Koreksi',
                    'latitude' => '3.59519600',
                    'longitude' => '98.67222600',
                    'geofence_radius' => '30.00',
                    'maximum_accuracy' => '50.00',
                    'status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createEmployee(
        Branch $branch,
        array $overrides = []
    ): Employee {
        $this->employeeSequence++;

        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::query()->create(
            array_replace(
                [
                    'user_id' => $employeeUser->id,
                    'branch_id' => $branch->id,

                    'employee_number' => sprintf(
                        'EMP-CORRECTION-%03d',
                        $this->employeeSequence
                    ),

                    'full_name' => sprintf(
                        'Karyawan Koreksi %03d',
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
                        'Pola Koreksi %03d',
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createEmployeeSchedule(
        Employee $employee,
        WorkSchedule $workSchedule,
        User $approver,
        array $overrides = []
    ): EmployeeSchedule {
        return EmployeeSchedule::query()->create(
            array_replace(
                [
                    'employee_id' => $employee->id,

                    'work_schedule_id' => $workSchedule->id,

                    'schedule_date' => $this->attendanceDate(),

                    'schedule_status' => 'work',
                    'approved_by' => $approver->id,
                    'notes' => null,
                ],
                $overrides
            )
        );
    }

    private function createScannerAttendance(
        EmployeeSchedule $employeeSchedule,
        Employee $employee,
        Branch $branch,
        User $creator
    ): Attendance {
        $date = $employeeSchedule
            ->schedule_date
            ->format('Y-m-d');

        $now = now();

        $attendanceSessionId =
            DB::table('attendance_sessions')
                ->insertGetId([
                    'public_id' => (string) Str::uuid(),

                    'branch_id' => $branch->id,

                    'attendance_type' => 'check_in',

                    'session_date' => $date,

                    'start_time' => "{$date} 08:15:00",

                    'end_time' => "{$date} 09:30:00",

                    'encrypted_secret' => 'encrypted-secret-for-correction-test',

                    'status' => 'closed',

                    'created_by' => $creator->id,

                    'closed_at' => "{$date} 09:30:00",

                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

        return Attendance::query()->create([
            'employee_id' => $employee->id,

            'attendance_session_id' => $attendanceSessionId,

            'employee_schedule_id' => $employeeSchedule->id,

            'branch_id' => $branch->id,

            'attendance_type' => 'check_in',

            'attendance_date' => $date,

            'attendance_time' => "{$date} 08:45:00",

            'latitude' => '3.59519600',
            'longitude' => '98.67222600',
            'accuracy' => '10.00',
            'distance' => '5.00',
            'geofence_radius' => '30.00',

            'attendance_status' => 'present',

            'punctuality_status' => 'on_time',

            'validation_status' => 'accepted',

            'record_source' => Attendance::RECORD_SOURCE_SCANNER,

            'last_corrected_by' => null,

            'last_correction_reason' => null,

            'last_corrected_at' => null,
        ]);
    }

    private function attendanceDate(): string
    {
        return now(
            config(
                'app.timezone',
                'Asia/Jakarta'
            )
        )
            ->subDay()
            ->format('Y-m-d');
    }
}
