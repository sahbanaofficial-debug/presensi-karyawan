<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WeeklyScheduleItem;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WeeklyScheduleRuntimeModelTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_work_schedule_calculates_final_check_in_limit(): void
    {
        $workSchedule = $this->createWorkSchedule();

        $this->assertSame(
            30,
            $workSchedule->check_in_limit_minutes
        );

        $this->assertSame(
            '2026-09-14 09:15:00',
            $workSchedule
                ->getCheckInLimit('2026-09-14')
                ->format('Y-m-d H:i:s')
        );

        $this->assertInstanceOf(
            HasMany::class,
            $workSchedule->weeklyScheduleItems()
        );
    }

    public function test_weekly_work_schedule_uses_snapshot_and_roster_relation(): void
    {
        $branch = Branch::factory()->create();
        $hrd = $this->createHrd();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $weeklySchedule = $this->createWeeklySchedule(
            branch: $branch,
            creator: $hrd
        );

        $weeklyItem = WeeklyScheduleItem::query()->create([
            'weekly_schedule_id' => $weeklySchedule->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => '2026-09-14',
            'schedule_status' => WeeklyScheduleItem::STATUS_WORK,
            'work_schedule_name_snapshot' => $workSchedule->name,
            'check_in_time_snapshot' => '08:45:00',
            'check_out_time_snapshot' => '17:00:00',
            'check_in_open_minutes_snapshot' => 30,
            'check_in_limit_minutes_snapshot' => 30,
            'late_tolerance_minutes_snapshot' => 5,
            'check_out_limit_minutes_snapshot' => 60,
            'notes' => null,
        ]);

        $employeeSchedule = EmployeeSchedule::query()->create([
            'weekly_schedule_item_id' => $weeklyItem->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => '2026-09-14',
            'schedule_status' => 'work',
            'schedule_source' => EmployeeSchedule::SOURCE_WEEKLY,
            'work_schedule_name_snapshot' => $workSchedule->name,
            'check_in_time_snapshot' => '08:45:00',
            'check_out_time_snapshot' => '17:00:00',
            'check_in_open_minutes_snapshot' => 30,
            'check_in_limit_minutes_snapshot' => 30,
            'late_tolerance_minutes_snapshot' => 5,
            'check_out_limit_minutes_snapshot' => 60,
            'approved_by' => $hrd->id,
            'notes' => null,
        ]);

        $this->assertTrue(
            $employeeSchedule->isFromWeeklySchedule()
        );

        $this->assertTrue(
            $employeeSchedule->isWorkDay()
        );

        $this->assertTrue(
            $employeeSchedule->requiresAttendance()
        );

        $this->assertTrue(
            $employeeSchedule->hasCompleteWorkSnapshot()
        );

        $this->assertTrue(
            $employeeSchedule->hasValidScheduleConfiguration()
        );

        $this->assertSame(
            30,
            $employeeSchedule->check_in_limit_minutes_snapshot
        );

        $this->assertTrue(
            $employeeSchedule
                ->weeklyScheduleItem
                ->is($weeklyItem)
        );

        $this->assertInstanceOf(
            BelongsTo::class,
            $employeeSchedule->weeklyScheduleItem()
        );
    }

    public function test_weekly_leave_schedule_is_valid_without_work_snapshot(): void
    {
        $branch = Branch::factory()->create();
        $hrd = $this->createHrd();
        $employee = $this->createEmployee($branch);

        $weeklySchedule = $this->createWeeklySchedule(
            branch: $branch,
            creator: $hrd
        );

        $weeklyItem = WeeklyScheduleItem::query()->create([
            'weekly_schedule_id' => $weeklySchedule->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => null,
            'schedule_date' => '2026-09-15',
            'schedule_status' => WeeklyScheduleItem::STATUS_LEAVE,
            'work_schedule_name_snapshot' => null,
            'check_in_time_snapshot' => null,
            'check_out_time_snapshot' => null,
            'check_in_open_minutes_snapshot' => null,
            'check_in_limit_minutes_snapshot' => null,
            'late_tolerance_minutes_snapshot' => null,
            'check_out_limit_minutes_snapshot' => null,
            'notes' => 'Cuti tahunan.',
        ]);

        $employeeSchedule = EmployeeSchedule::query()->create([
            'weekly_schedule_item_id' => $weeklyItem->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => null,
            'schedule_date' => '2026-09-15',
            'schedule_status' => 'leave',
            'schedule_source' => EmployeeSchedule::SOURCE_WEEKLY,
            'work_schedule_name_snapshot' => null,
            'check_in_time_snapshot' => null,
            'check_out_time_snapshot' => null,
            'check_in_open_minutes_snapshot' => null,
            'check_in_limit_minutes_snapshot' => null,
            'late_tolerance_minutes_snapshot' => null,
            'check_out_limit_minutes_snapshot' => null,
            'approved_by' => $hrd->id,
            'notes' => 'Cuti tahunan.',
        ]);

        $this->assertTrue($employeeSchedule->isLeave());

        $this->assertFalse(
            $employeeSchedule->requiresAttendance()
        );

        $this->assertTrue(
            $employeeSchedule->hasEmptyWorkSnapshot()
        );

        $this->assertTrue(
            $employeeSchedule->hasValidScheduleConfiguration()
        );
    }

    public function test_legacy_manual_work_schedule_remains_valid_without_snapshot(): void
    {
        $branch = Branch::factory()->create();
        $hrd = $this->createHrd();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $employeeSchedule = EmployeeSchedule::query()->create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => '2026-09-16',
            'schedule_status' => 'work',
            'approved_by' => $hrd->id,
            'notes' => null,
        ])->fresh();

        $this->assertSame(
            EmployeeSchedule::SOURCE_MANUAL,
            $employeeSchedule->schedule_source
        );

        $this->assertFalse(
            $employeeSchedule->isFromWeeklySchedule()
        );

        $this->assertTrue(
            $employeeSchedule->hasValidScheduleConfiguration()
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
                'EMP-RUNTIME-%03d',
                $this->sequence
            ),
            'full_name' => sprintf(
                'Karyawan Runtime %03d',
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
                'Pola Runtime %03d',
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
            'week_start_date' => '2026-09-13',
            'week_end_date' => '2026-09-19',
            'status' => WeeklySchedule::STATUS_DRAFT,
            'created_by' => $creator->id,
            'published_by' => null,
            'published_at' => null,
            'notes' => null,
        ]);
    }
}