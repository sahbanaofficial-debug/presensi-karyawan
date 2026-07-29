<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BranchDefaultEmployeeScheduleFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_default_work_schedule_accepts_null_approver_and_complete_snapshot(): void
    {
        $context = $this->createContext();

        $schedule = EmployeeSchedule::query()->create([
            'weekly_schedule_item_id' => null,
            'employee_id' => $context['employee']->id,
            'work_schedule_id' => $context['workSchedule']->id,
            'schedule_date' => '2026-11-02',
            'schedule_status' => 'work',
            'schedule_source' => EmployeeSchedule::SOURCE_BRANCH_DEFAULT,
            'work_schedule_name_snapshot' => $context['workSchedule']->name,
            'check_in_time_snapshot' => '08:45:00',
            'check_out_time_snapshot' => '21:30:00',
            'check_in_open_minutes_snapshot' => 30,
            'check_in_limit_minutes_snapshot' => 30,
            'late_tolerance_minutes_snapshot' => 5,
            'check_out_limit_minutes_snapshot' => 60,
            'approved_by' => null,
            'notes' => null,
        ])->fresh();

        $this->assertNull($schedule->approved_by);
        $this->assertNull($schedule->approver);
        $this->assertNull($schedule->weekly_schedule_item_id);
        $this->assertSame(
            EmployeeSchedule::SOURCE_BRANCH_DEFAULT,
            $schedule->schedule_source
        );
        $this->assertTrue(
            $schedule->isFromBranchDefaultSchedule()
        );
        $this->assertFalse(
            $schedule->isFromWeeklySchedule()
        );
        $this->assertTrue($schedule->isWorkDay());
        $this->assertTrue($schedule->requiresAttendance());
        $this->assertTrue(
            $schedule->hasCompleteWorkSnapshot()
        );
        $this->assertTrue(
            $schedule->hasValidScheduleConfiguration()
        );

        $this->assertDatabaseHas(
            'employee_schedules',
            [
                'id' => $schedule->id,
                'employee_id' => $context['employee']->id,
                'work_schedule_id' => $context['workSchedule']->id,
                'schedule_source' => 'branch_default',
                'approved_by' => null,
            ]
        );
    }

    public function test_branch_default_work_schedule_requires_complete_snapshot(): void
    {
        $context = $this->createContext();

        $schedule = EmployeeSchedule::query()->create([
            'weekly_schedule_item_id' => null,
            'employee_id' => $context['employee']->id,
            'work_schedule_id' => $context['workSchedule']->id,
            'schedule_date' => '2026-11-03',
            'schedule_status' => 'work',
            'schedule_source' => EmployeeSchedule::SOURCE_BRANCH_DEFAULT,
            'work_schedule_name_snapshot' => $context['workSchedule']->name,
            'check_in_time_snapshot' => '08:45:00',
            'check_out_time_snapshot' => null,
            'check_in_open_minutes_snapshot' => 30,
            'check_in_limit_minutes_snapshot' => 30,
            'late_tolerance_minutes_snapshot' => 5,
            'check_out_limit_minutes_snapshot' => 60,
            'approved_by' => null,
            'notes' => null,
        ]);

        $this->assertTrue(
            $schedule->isFromBranchDefaultSchedule()
        );
        $this->assertFalse(
            $schedule->hasCompleteWorkSnapshot()
        );
        $this->assertFalse(
            $schedule->hasValidScheduleConfiguration()
        );
    }

    public function test_branch_default_source_rejects_non_work_schedule(): void
    {
        $context = $this->createContext();

        $schedule = EmployeeSchedule::query()->create([
            'weekly_schedule_item_id' => null,
            'employee_id' => $context['employee']->id,
            'work_schedule_id' => null,
            'schedule_date' => '2026-11-04',
            'schedule_status' => 'off',
            'schedule_source' => EmployeeSchedule::SOURCE_BRANCH_DEFAULT,
            'work_schedule_name_snapshot' => null,
            'check_in_time_snapshot' => null,
            'check_out_time_snapshot' => null,
            'check_in_open_minutes_snapshot' => null,
            'check_in_limit_minutes_snapshot' => null,
            'late_tolerance_minutes_snapshot' => null,
            'check_out_limit_minutes_snapshot' => null,
            'approved_by' => null,
            'notes' => null,
        ]);

        $this->assertFalse($schedule->isWorkDay());
        $this->assertFalse($schedule->requiresAttendance());
        $this->assertTrue(
            $schedule->hasEmptyWorkSnapshot()
        );
        $this->assertFalse(
            $schedule->hasValidScheduleConfiguration()
        );
    }

    public function test_manual_work_schedule_remains_valid_without_snapshot(): void
    {
        $context = $this->createContext();

        $hrd = User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $schedule = EmployeeSchedule::query()->create([
            'weekly_schedule_item_id' => null,
            'employee_id' => $context['employee']->id,
            'work_schedule_id' => $context['workSchedule']->id,
            'schedule_date' => '2026-11-05',
            'schedule_status' => 'work',
            'schedule_source' => EmployeeSchedule::SOURCE_MANUAL,
            'work_schedule_name_snapshot' => null,
            'check_in_time_snapshot' => null,
            'check_out_time_snapshot' => null,
            'check_in_open_minutes_snapshot' => null,
            'check_in_limit_minutes_snapshot' => null,
            'late_tolerance_minutes_snapshot' => null,
            'check_out_limit_minutes_snapshot' => null,
            'approved_by' => $hrd->id,
            'notes' => null,
        ]);

        $this->assertFalse(
            $schedule->isFromBranchDefaultSchedule()
        );
        $this->assertFalse(
            $schedule->isFromWeeklySchedule()
        );
        $this->assertTrue(
            $schedule->hasValidScheduleConfiguration()
        );
    }

    /**
     * @return array{
     *     branch: Branch,
     *     employee: Employee,
     *     workSchedule: WorkSchedule
     * }
     */
    private function createContext(): array
    {
        $workSchedule = WorkSchedule::query()->create([
            'name' => 'Jadwal Default Cabang Fondasi',
            'check_in_time' => '08:45:00',
            'check_out_time' => '21:30:00',
            'check_in_open_minutes' => 30,
            'check_in_limit_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);

        $branch = Branch::factory()->create([
            'default_work_schedule_id' => $workSchedule->id,
            'status' => 'active',
        ]);

        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-BRANCH-DEFAULT-001',
            'full_name' => 'Karyawan Default Cabang',
            'position' => 'Karyawan',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        return [
            'branch' => $branch,
            'employee' => $employee,
            'workSchedule' => $workSchedule,
        ];
    }
}
