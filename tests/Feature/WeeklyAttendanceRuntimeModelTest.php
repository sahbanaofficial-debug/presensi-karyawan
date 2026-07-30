<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\ValidationLog;
use App\Models\WeeklySchedule;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WeeklyAttendanceRuntimeModelTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_automatic_session_is_connected_to_weekly_schedule(): void
    {
        $branch = $this->createBranch();
        $hrd = $this->createHrd();

        $weeklySchedule = $this->createWeeklySchedule(
            branch: $branch,
            creator: $hrd
        );

        $session = $this->createAutomaticSession(
            branch: $branch,
            weeklySchedule: $weeklySchedule
        );

        $this->assertTrue($session->isAutomatic());
        $this->assertFalse($session->isManual());
        $this->assertTrue($session->isAutoType());
        $this->assertNull($session->creator);

        $this->assertTrue(
            $session->weeklySchedule->is($weeklySchedule)
        );

        $this->assertTrue(
            $session->branch->is($branch)
        );

        $this->assertTrue(
            $weeklySchedule
                ->attendanceSessions()
                ->whereKey($session->id)
                ->exists()
        );
    }

    public function test_attendance_stores_terminal_and_late_minutes(): void
    {
        $branch = $this->createBranch();
        $hrd = $this->createHrd();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $weeklySchedule = $this->createWeeklySchedule(
            branch: $branch,
            creator: $hrd
        );

        $session = $this->createAutomaticSession(
            branch: $branch,
            weeklySchedule: $weeklySchedule
        );

        $terminal = $this->createTerminal(
            branch: $branch,
            creator: $hrd
        );

        $employeeSchedule = $this->createEmployeeSchedule(
            employee: $employee,
            workSchedule: $workSchedule,
            approver: $hrd
        );

        $attendance = Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_session_id' => $session->id,
            'branch_terminal_id' => $terminal->id,
            'employee_schedule_id' => $employeeSchedule->id,
            'branch_id' => $branch->id,
            'attendance_type' => 'check_in',
            'attendance_date' => '2026-09-21',
            'attendance_time' => '2026-09-21 08:51:00',
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'accuracy' => 5.00,
            'distance' => 0.00,
            'geofence_radius' => $branch->geofence_radius,
            'attendance_status' => 'present',
            'punctuality_status' => 'late',
            'late_minutes' => 6,
            'validation_status' => 'accepted',
            'record_source' => Attendance::RECORD_SOURCE_SCANNER,
            'last_corrected_by' => null,
            'last_correction_reason' => null,
            'last_corrected_at' => null,
        ]);

        $this->assertSame(
            $terminal->id,
            $attendance->branch_terminal_id
        );

        $this->assertSame(
            6,
            $attendance->late_minutes
        );

        $this->assertTrue(
            $attendance->wasRecordedThroughTerminal()
        );

        $this->assertTrue(
            $attendance->branchTerminal->is($terminal)
        );

        $this->assertTrue(
            $terminal
                ->attendances()
                ->whereKey($attendance->id)
                ->exists()
        );
    }

    public function test_validation_log_is_connected_to_terminal(): void
    {
        $branch = $this->createBranch();
        $hrd = $this->createHrd();
        $employee = $this->createEmployee($branch);

        $weeklySchedule = $this->createWeeklySchedule(
            branch: $branch,
            creator: $hrd
        );

        $session = $this->createAutomaticSession(
            branch: $branch,
            weeklySchedule: $weeklySchedule
        );

        $terminal = $this->createTerminal(
            branch: $branch,
            creator: $hrd
        );

        $log = ValidationLog::query()->create([
            'user_id' => $employee->user_id,
            'attendance_session_id' => $session->id,
            'branch_terminal_id' => $terminal->id,
            'validation_type' => 'attendance_accepted',
            'status' => 'accepted',
            'reason' => 'Presensi otomatis diterima.',
            'payload_reference' => hash(
                'sha256',
                'weekly-attendance-runtime-payload'
            ),
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'accuracy' => 5.00,
            'distance' => 0.00,
        ]);

        $this->assertSame(
            $terminal->id,
            $log->branch_terminal_id
        );

        $this->assertTrue(
            $log->wasGeneratedThroughTerminal()
        );

        $this->assertTrue(
            $log->branchTerminal->is($terminal)
        );

        $this->assertTrue(
            $terminal
                ->validationLogs()
                ->whereKey($log->id)
                ->exists()
        );
    }

    public function test_inverse_automation_relationships_are_available(): void
    {
        $user = new User;
        $branch = new Branch;
        $employee = new Employee;

        $this->assertInstanceOf(
            HasMany::class,
            $user->createdWeeklySchedules()
        );

        $this->assertInstanceOf(
            HasMany::class,
            $user->publishedWeeklySchedules()
        );

        $this->assertInstanceOf(
            HasMany::class,
            $user->createdBranchTerminals()
        );

        $this->assertInstanceOf(
            HasMany::class,
            $user->revokedBranchTerminals()
        );

        $this->assertInstanceOf(
            HasMany::class,
            $branch->weeklySchedules()
        );

        $this->assertInstanceOf(
            HasMany::class,
            $branch->branchTerminals()
        );

        $this->assertInstanceOf(
            HasMany::class,
            $employee->weeklyScheduleItems()
        );
    }

    private function createBranch(): Branch
    {
        return Branch::factory()->create([
            'latitude' => 3.5374963,
            'longitude' => 98.6844756,
            'geofence_radius' => 30.00,
            'maximum_accuracy' => 25.00,
            'status' => 'active',
        ]);
    }

    private function createHrd(): User
    {
        return User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);
    }

    private function createEmployee(
        Branch $branch
    ): Employee {
        $this->sequence++;

        $user = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => sprintf(
                'EMP-ATT-RUNTIME-%03d',
                $this->sequence
            ),
            'full_name' => sprintf(
                'Karyawan Attendance Runtime %03d',
                $this->sequence
            ),
            'position' => 'Karyawan',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);
    }

    private function createWorkSchedule(): WorkSchedule
    {
        $this->sequence++;

        return WorkSchedule::query()->create([
            'name' => sprintf(
                'Pola Attendance Runtime %03d',
                $this->sequence
            ),
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'check_in_limit_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);
    }

    private function createWeeklySchedule(
        Branch $branch,
        User $creator
    ): WeeklySchedule {
        return WeeklySchedule::query()->create([
            'branch_id' => $branch->id,
            'week_start_date' => '2026-09-20',
            'week_end_date' => '2026-09-26',
            'status' => WeeklySchedule::STATUS_PUBLISHED,
            'created_by' => $creator->id,
            'published_by' => $creator->id,
            'published_at' => '2026-09-19 10:00:00',
            'notes' => null,
        ]);
    }

    private function createAutomaticSession(
        Branch $branch,
        WeeklySchedule $weeklySchedule
    ): AttendanceSession {
        return AttendanceSession::query()->create([
            'branch_id' => $branch->id,
            'weekly_schedule_id' => $weeklySchedule->id,
            'attendance_type' => AttendanceSession::TYPE_AUTO,
            'session_source' => AttendanceSession::SOURCE_AUTOMATIC,
            'automation_key' => 'AUTO:'.$branch->id.':2026-09-21',
            'session_date' => '2026-09-21',
            'start_time' => '2026-09-21 08:15:00',
            'end_time' => '2026-09-21 18:00:00',
            'encrypted_secret' => 'JBSWY3DPEHPK3PXP',
            'status' => 'active',
            'created_by' => null,
            'closed_at' => null,
        ]);
    }

    private function createTerminal(
        Branch $branch,
        User $creator
    ): BranchTerminal {
        return BranchTerminal::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Terminal Attendance Runtime',
            'device_token_hash' => hash(
                'sha256',
                'attendance-runtime-device'
            ),
            'activation_code_hash' => null,
            'activation_expires_at' => null,
            'activated_at' => now(),
            'last_seen_at' => now(),
            'status' => BranchTerminal::STATUS_ACTIVE,
            'created_by' => $creator->id,
            'revoked_by' => null,
            'revoked_at' => null,
        ]);
    }

    private function createEmployeeSchedule(
        Employee $employee,
        WorkSchedule $workSchedule,
        User $approver
    ): EmployeeSchedule {
        return EmployeeSchedule::query()->create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => '2026-09-21',
            'schedule_status' => 'work',
            'schedule_source' => EmployeeSchedule::SOURCE_MANUAL,
            'approved_by' => $approver->id,
            'notes' => null,
        ]);
    }
}
