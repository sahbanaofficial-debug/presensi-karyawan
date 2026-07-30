<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class WeeklyAutomationDatabaseStructureTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_weekly_automation_tables_and_columns_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable('weekly_schedules')
        );

        $this->assertTrue(
            Schema::hasColumns(
                'weekly_schedules',
                [
                    'id',
                    'branch_id',
                    'week_start_date',
                    'week_end_date',
                    'status',
                    'created_by',
                    'published_by',
                    'published_at',
                    'notes',
                    'created_at',
                    'updated_at',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasTable('weekly_schedule_items')
        );

        $this->assertTrue(
            Schema::hasColumns(
                'weekly_schedule_items',
                [
                    'id',
                    'weekly_schedule_id',
                    'employee_id',
                    'work_schedule_id',
                    'schedule_date',
                    'schedule_status',
                    'work_schedule_name_snapshot',
                    'check_in_time_snapshot',
                    'check_out_time_snapshot',
                    'check_in_open_minutes_snapshot',
                    'check_in_limit_minutes_snapshot',
                    'late_tolerance_minutes_snapshot',
                    'check_out_limit_minutes_snapshot',
                    'notes',
                    'created_at',
                    'updated_at',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasTable('branch_terminals')
        );

        $this->assertTrue(
            Schema::hasColumns(
                'branch_terminals',
                [
                    'id',
                    'public_id',
                    'branch_id',
                    'name',
                    'device_token_hash',
                    'activation_code_hash',
                    'activation_expires_at',
                    'activated_at',
                    'last_seen_at',
                    'status',
                    'created_by',
                    'revoked_by',
                    'revoked_at',
                    'created_at',
                    'updated_at',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasColumns(
                'work_schedules',
                [
                    'check_in_limit_minutes',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasColumns(
                'employee_schedules',
                [
                    'weekly_schedule_item_id',
                    'schedule_source',
                    'work_schedule_name_snapshot',
                    'check_in_time_snapshot',
                    'check_out_time_snapshot',
                    'check_in_open_minutes_snapshot',
                    'check_in_limit_minutes_snapshot',
                    'late_tolerance_minutes_snapshot',
                    'check_out_limit_minutes_snapshot',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasColumns(
                'attendance_sessions',
                [
                    'weekly_schedule_id',
                    'session_source',
                    'automation_key',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasColumns(
                'attendances',
                [
                    'branch_terminal_id',
                    'late_minutes',
                ]
            )
        );

        $this->assertTrue(
            Schema::hasColumns(
                'validation_logs',
                [
                    'branch_terminal_id',
                ]
            )
        );
    }

    public function test_check_in_limit_defaults_to_thirty_minutes(): void
    {
        $workSchedule = $this->createWorkSchedule();

        $this->assertSame(
            30,
            (int) $workSchedule
                ->fresh()
                ->check_in_limit_minutes
        );
    }

    public function test_weekly_schedule_is_unique_per_branch_and_week_start(): void
    {
        $branch = Branch::factory()->create();
        $hrd = $this->createHrd();

        $this->insertWeeklySchedule(
            branch: $branch,
            hrd: $hrd,
            weekStartDate: '2026-08-02',
            weekEndDate: '2026-08-08'
        );

        $this->expectException(
            QueryException::class
        );

        $this->insertWeeklySchedule(
            branch: $branch,
            hrd: $hrd,
            weekStartDate: '2026-08-02',
            weekEndDate: '2026-08-08'
        );
    }

    public function test_weekly_item_is_unique_per_employee_and_date(): void
    {
        $branch = Branch::factory()->create();
        $hrd = $this->createHrd();
        $employee = $this->createEmployee($branch);

        $weeklyScheduleId =
            $this->insertWeeklySchedule(
                branch: $branch,
                hrd: $hrd,
                weekStartDate: '2026-08-09',
                weekEndDate: '2026-08-15'
            );

        $this->insertWeeklyItem(
            weeklyScheduleId: $weeklyScheduleId,
            employee: $employee,
            scheduleDate: '2026-08-10',
            scheduleStatus: 'off'
        );

        $this->expectException(
            QueryException::class
        );

        $this->insertWeeklyItem(
            weeklyScheduleId: $weeklyScheduleId,
            employee: $employee,
            scheduleDate: '2026-08-10',
            scheduleStatus: 'off'
        );
    }

    public function test_weekly_item_keeps_snapshot_after_work_schedule_master_changes(): void
    {
        $branch = Branch::factory()->create();
        $hrd = $this->createHrd();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $weeklyScheduleId =
            $this->insertWeeklySchedule(
                branch: $branch,
                hrd: $hrd,
                weekStartDate: '2026-09-06',
                weekEndDate: '2026-09-12'
            );

        $originalName = $workSchedule->name;

        $weeklyItemId = DB::table(
            'weekly_schedule_items'
        )->insertGetId([
            'weekly_schedule_id' => $weeklyScheduleId,
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => '2026-09-07',
            'schedule_status' => 'work',
            'work_schedule_name_snapshot' => $originalName,
            'check_in_time_snapshot' => '08:45:00',
            'check_out_time_snapshot' => '17:00:00',
            'check_in_open_minutes_snapshot' => 30,
            'check_in_limit_minutes_snapshot' => 30,
            'late_tolerance_minutes_snapshot' => 5,
            'check_out_limit_minutes_snapshot' => 60,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('work_schedules')
            ->where('id', $workSchedule->id)
            ->update([
                'name' => 'Pola Kerja yang Sudah Diubah',
                'check_in_time' => '09:00:00',
                'check_out_time' => '18:00:00',
                'check_in_open_minutes' => 15,
                'check_in_limit_minutes' => 45,
                'late_tolerance_minutes' => 10,
                'check_out_limit_minutes' => 90,
                'updated_at' => now(),
            ]);

        $weeklyItem = DB::table(
            'weekly_schedule_items'
        )
            ->where('id', $weeklyItemId)
            ->first();

        $this->assertNotNull($weeklyItem);

        $this->assertSame(
            $originalName,
            $weeklyItem->work_schedule_name_snapshot
        );

        $this->assertSame(
            '08:45:00',
            $weeklyItem->check_in_time_snapshot
        );

        $this->assertSame(
            '17:00:00',
            $weeklyItem->check_out_time_snapshot
        );

        $this->assertSame(
            30,
            (int) $weeklyItem
                ->check_in_open_minutes_snapshot
        );

        $this->assertSame(
            30,
            (int) $weeklyItem
                ->check_in_limit_minutes_snapshot
        );

        $this->assertSame(
            5,
            (int) $weeklyItem
                ->late_tolerance_minutes_snapshot
        );

        $this->assertSame(
            60,
            (int) $weeklyItem
                ->check_out_limit_minutes_snapshot
        );
    }

    public function test_published_daily_schedule_can_store_leave_and_historical_source(): void
    {
        $branch = Branch::factory()->create();
        $hrd = $this->createHrd();
        $employee = $this->createEmployee($branch);

        $weeklyScheduleId =
            $this->insertWeeklySchedule(
                branch: $branch,
                hrd: $hrd,
                weekStartDate: '2026-08-16',
                weekEndDate: '2026-08-22'
            );

        $weeklyItemId = $this->insertWeeklyItem(
            weeklyScheduleId: $weeklyScheduleId,
            employee: $employee,
            scheduleDate: '2026-08-18',
            scheduleStatus: 'leave'
        );

        $employeeScheduleId = DB::table(
            'employee_schedules'
        )->insertGetId([
            'weekly_schedule_item_id' => $weeklyItemId,
            'employee_id' => $employee->id,
            'work_schedule_id' => null,
            'schedule_date' => '2026-08-18',
            'schedule_status' => 'leave',
            'schedule_source' => 'weekly',
            'work_schedule_name_snapshot' => null,
            'check_in_time_snapshot' => null,
            'check_out_time_snapshot' => null,
            'check_in_open_minutes_snapshot' => null,
            'check_in_limit_minutes_snapshot' => null,
            'late_tolerance_minutes_snapshot' => null,
            'check_out_limit_minutes_snapshot' => null,
            'approved_by' => $hrd->id,
            'notes' => 'Cuti tahunan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas(
            'employee_schedules',
            [
                'id' => $employeeScheduleId,
                'weekly_schedule_item_id' => $weeklyItemId,
                'schedule_status' => 'leave',
                'schedule_source' => 'weekly',
                'work_schedule_id' => null,
            ]
        );
    }

    public function test_automatic_session_accepts_null_creator_and_unique_automation_key(): void
    {
        $branch = Branch::factory()->create();
        $hrd = $this->createHrd();

        $weeklyScheduleId =
            $this->insertWeeklySchedule(
                branch: $branch,
                hrd: $hrd,
                weekStartDate: '2026-08-23',
                weekEndDate: '2026-08-29'
            );

        $automationKey =
            'AUTO:'.$branch->id.':2026-08-24';

        DB::table('attendance_sessions')->insert([
            'public_id' => (string) Str::uuid(),
            'branch_id' => $branch->id,
            'weekly_schedule_id' => $weeklyScheduleId,
            'attendance_type' => 'auto',
            'session_source' => 'automatic',
            'automation_key' => $automationKey,
            'session_date' => '2026-08-24',
            'start_time' => '2026-08-24 08:15:00',
            'end_time' => '2026-08-24 22:30:00',
            'encrypted_secret' => 'JBSWY3DPEHPK3PXP',
            'status' => 'active',
            'created_by' => null,
            'closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas(
            'attendance_sessions',
            [
                'branch_id' => $branch->id,
                'weekly_schedule_id' => $weeklyScheduleId,
                'attendance_type' => 'auto',
                'session_source' => 'automatic',
                'automation_key' => $automationKey,
                'created_by' => null,
            ]
        );

        $this->expectException(
            QueryException::class
        );

        DB::table('attendance_sessions')->insert([
            'public_id' => (string) Str::uuid(),
            'branch_id' => $branch->id,
            'weekly_schedule_id' => $weeklyScheduleId,
            'attendance_type' => 'auto',
            'session_source' => 'automatic',
            'automation_key' => $automationKey,
            'session_date' => '2026-08-24',
            'start_time' => '2026-08-24 08:15:00',
            'end_time' => '2026-08-24 22:30:00',
            'encrypted_secret' => 'JBSWY3DPEHPK3PXP',
            'status' => 'active',
            'created_by' => null,
            'closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_terminal_identity_and_tracking_can_be_stored(): void
    {
        $branch = Branch::factory()->create();
        $hrd = $this->createHrd();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $weeklyScheduleId =
            $this->insertWeeklySchedule(
                branch: $branch,
                hrd: $hrd,
                weekStartDate: '2026-08-30',
                weekEndDate: '2026-09-05'
            );

        $weeklyItemId = $this->insertWeeklyItem(
            weeklyScheduleId: $weeklyScheduleId,
            employee: $employee,
            scheduleDate: '2026-08-31',
            scheduleStatus: 'work',
            workScheduleId: $workSchedule->id
        );

        $employeeScheduleId = DB::table(
            'employee_schedules'
        )->insertGetId([
            'weekly_schedule_item_id' => $weeklyItemId,
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => '2026-08-31',
            'schedule_status' => 'work',
            'schedule_source' => 'weekly',
            'work_schedule_name_snapshot' => $workSchedule->name,
            'check_in_time_snapshot' => '08:45:00',
            'check_out_time_snapshot' => '17:00:00',
            'check_in_open_minutes_snapshot' => 30,
            'check_in_limit_minutes_snapshot' => 30,
            'late_tolerance_minutes_snapshot' => 5,
            'check_out_limit_minutes_snapshot' => 60,
            'approved_by' => $hrd->id,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $terminalId = DB::table(
            'branch_terminals'
        )->insertGetId([
            'public_id' => (string) Str::uuid(),
            'branch_id' => $branch->id,
            'name' => 'Terminal QA Cabang',
            'device_token_hash' => hash(
                'sha256',
                'device-token-qa'
            ),
            'activation_code_hash' => null,
            'activation_expires_at' => null,
            'activated_at' => now(),
            'last_seen_at' => now(),
            'status' => 'active',
            'created_by' => $hrd->id,
            'revoked_by' => null,
            'revoked_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sessionId = DB::table(
            'attendance_sessions'
        )->insertGetId([
            'public_id' => (string) Str::uuid(),
            'branch_id' => $branch->id,
            'weekly_schedule_id' => $weeklyScheduleId,
            'attendance_type' => 'auto',
            'session_source' => 'automatic',
            'automation_key' => 'AUTO:'.$branch->id.':2026-08-31',
            'session_date' => '2026-08-31',
            'start_time' => '2026-08-31 08:15:00',
            'end_time' => '2026-08-31 18:00:00',
            'encrypted_secret' => 'JBSWY3DPEHPK3PXP',
            'status' => 'active',
            'created_by' => null,
            'closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attendanceId = DB::table(
            'attendances'
        )->insertGetId([
            'employee_id' => $employee->id,
            'attendance_session_id' => $sessionId,
            'branch_terminal_id' => $terminalId,
            'employee_schedule_id' => $employeeScheduleId,
            'branch_id' => $branch->id,
            'attendance_type' => 'check_in',
            'attendance_date' => '2026-08-31',
            'attendance_time' => '2026-08-31 08:51:00',
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'accuracy' => 5.00,
            'distance' => 0.00,
            'geofence_radius' => $branch->geofence_radius,
            'attendance_status' => 'present',
            'punctuality_status' => 'late',
            'late_minutes' => 6,
            'validation_status' => 'accepted',
            'record_source' => 'scanner',
            'last_corrected_by' => null,
            'last_correction_reason' => null,
            'last_corrected_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('validation_logs')->insert([
            'user_id' => $employee->user_id,
            'attendance_session_id' => $sessionId,
            'branch_terminal_id' => $terminalId,
            'validation_type' => 'attendance_accepted',
            'status' => 'accepted',
            'reason' => 'Presensi otomatis diterima.',
            'payload_reference' => hash(
                'sha256',
                'payload-terminal-qa'
            ),
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'accuracy' => 5.00,
            'distance' => 0.00,
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas(
            'branch_terminals',
            [
                'id' => $terminalId,
                'branch_id' => $branch->id,
                'status' => 'active',
            ]
        );

        $this->assertDatabaseHas(
            'attendances',
            [
                'id' => $attendanceId,
                'branch_terminal_id' => $terminalId,
                'late_minutes' => 6,
            ]
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'attendance_session_id' => $sessionId,
                'branch_terminal_id' => $terminalId,
                'status' => 'accepted',
            ]
        );
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
                'EMP-AUTO-SCHEMA-%03d',
                $this->sequence
            ),
            'full_name' => sprintf(
                'Karyawan Schema Otomatisasi %03d',
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
                'Pola Otomatisasi %03d',
                $this->sequence
            ),
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);
    }

    private function insertWeeklySchedule(
        Branch $branch,
        User $hrd,
        string $weekStartDate,
        string $weekEndDate
    ): int {
        return (int) DB::table(
            'weekly_schedules'
        )->insertGetId([
            'branch_id' => $branch->id,
            'week_start_date' => $weekStartDate,
            'week_end_date' => $weekEndDate,
            'status' => 'draft',
            'created_by' => $hrd->id,
            'published_by' => null,
            'published_at' => null,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertWeeklyItem(
        int $weeklyScheduleId,
        Employee $employee,
        string $scheduleDate,
        string $scheduleStatus,
        ?int $workScheduleId = null
    ): int {
        return (int) DB::table(
            'weekly_schedule_items'
        )->insertGetId([
            'weekly_schedule_id' => $weeklyScheduleId,
            'employee_id' => $employee->id,
            'work_schedule_id' => $workScheduleId,
            'schedule_date' => $scheduleDate,
            'schedule_status' => $scheduleStatus,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
