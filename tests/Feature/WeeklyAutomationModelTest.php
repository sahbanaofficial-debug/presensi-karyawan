<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\Employee;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WeeklyScheduleItem;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WeeklyAutomationModelTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_weekly_schedule_exposes_casts_relationships_and_status_helpers(): void
    {
        $branch = Branch::factory()->create();
        $creator = $this->createHrd();
        $publisher = $this->createHrd();

        $weeklySchedule = WeeklySchedule::query()->create([
            'branch_id' => $branch->id,
            'week_start_date' => '2026-09-13',
            'week_end_date' => '2026-09-19',
            'status' => WeeklySchedule::STATUS_PUBLISHED,
            'created_by' => $creator->id,
            'published_by' => $publisher->id,
            'published_at' => '2026-09-12 10:00:00',
            'notes' => 'Roster minggu ketiga September.',
        ]);

        $this->assertSame(
            '2026-09-13',
            $weeklySchedule->week_start_date->format('Y-m-d')
        );

        $this->assertSame(
            '2026-09-19',
            $weeklySchedule->week_end_date->format('Y-m-d')
        );

        $this->assertSame(
            '2026-09-12 10:00:00',
            $weeklySchedule->published_at->format('Y-m-d H:i:s')
        );

        $this->assertTrue($weeklySchedule->isPublished());
        $this->assertFalse($weeklySchedule->isDraft());
        $this->assertFalse($weeklySchedule->isEditable());

        $this->assertTrue(
            $weeklySchedule->branch->is($branch)
        );

        $this->assertTrue(
            $weeklySchedule->creator->is($creator)
        );

        $this->assertTrue(
            $weeklySchedule->publisher->is($publisher)
        );
    }

    public function test_weekly_schedule_item_validates_work_and_nonwork_configuration(): void
    {
        $branch = Branch::factory()->create();
        $hrd = $this->createHrd();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $weeklySchedule = $this->createWeeklySchedule(
            branch: $branch,
            creator: $hrd
        );

        $workItem = WeeklyScheduleItem::query()->create([
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

        $this->assertTrue($workItem->isWorkDay());
        $this->assertTrue($workItem->requiresAttendance());
        $this->assertTrue($workItem->hasCompleteWorkSnapshot());
        $this->assertTrue($workItem->hasValidScheduleConfiguration());

        $this->assertSame(
            30,
            $workItem->check_in_limit_minutes_snapshot
        );

        $this->assertTrue(
            $workItem->weeklySchedule->is($weeklySchedule)
        );

        $this->assertTrue(
            $workItem->employee->is($employee)
        );

        $this->assertTrue(
            $workItem->workSchedule->is($workSchedule)
        );

        $leaveItem = WeeklyScheduleItem::query()->create([
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

        $this->assertTrue($leaveItem->isLeave());
        $this->assertFalse($leaveItem->requiresAttendance());
        $this->assertTrue($leaveItem->hasEmptyWorkSnapshot());
        $this->assertTrue($leaveItem->hasValidScheduleConfiguration());
    }

    public function test_branch_terminal_generates_public_uuid_hides_hashes_and_casts_dates(): void
    {
        $branch = Branch::factory()->create();
        $creator = $this->createHrd();

        $terminal = BranchTerminal::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Terminal Utama Cabang',
            'device_token_hash' => hash(
                'sha256',
                'device-token-model-test'
            ),
            'activation_code_hash' => hash(
                'sha256',
                '123456'
            ),
            'activation_expires_at' => now()->addMinutes(10),
            'activated_at' => now(),
            'last_seen_at' => now(),
            'status' => BranchTerminal::STATUS_ACTIVE,
            'created_by' => $creator->id,
            'revoked_by' => null,
            'revoked_at' => null,
        ]);

        $this->assertNotEmpty($terminal->public_id);
        $this->assertSame(36, strlen($terminal->public_id));
        $this->assertTrue($terminal->isActive());
        $this->assertFalse($terminal->isPending());
        $this->assertFalse($terminal->isRevoked());
        $this->assertFalse($terminal->isActivationExpired());

        $this->assertTrue(
            $terminal->branch->is($branch)
        );

        $this->assertTrue(
            $terminal->creator->is($creator)
        );

        $serialized = $terminal->toArray();

        $this->assertArrayNotHasKey(
            'device_token_hash',
            $serialized
        );

        $this->assertArrayNotHasKey(
            'activation_code_hash',
            $serialized
        );

        $this->assertNotNull(
            $terminal->activation_expires_at
        );

        $this->assertNotNull(
            $terminal->activated_at
        );

        $this->assertNotNull(
            $terminal->last_seen_at
        );
    }

    public function test_new_models_define_expected_relationship_types(): void
    {
        $weeklySchedule = new WeeklySchedule();
        $weeklyItem = new WeeklyScheduleItem();
        $terminal = new BranchTerminal();

        $this->assertInstanceOf(
            BelongsTo::class,
            $weeklySchedule->branch()
        );

        $this->assertInstanceOf(
            HasMany::class,
            $weeklySchedule->items()
        );

        $this->assertInstanceOf(
            HasMany::class,
            $weeklySchedule->attendanceSessions()
        );

        $this->assertInstanceOf(
            BelongsTo::class,
            $weeklyItem->weeklySchedule()
        );

        $this->assertInstanceOf(
            HasOne::class,
            $weeklyItem->employeeSchedule()
        );

        $this->assertInstanceOf(
            BelongsTo::class,
            $terminal->branch()
        );

        $this->assertInstanceOf(
            HasMany::class,
            $terminal->attendances()
        );

        $this->assertInstanceOf(
            HasMany::class,
            $terminal->validationLogs()
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
                'EMP-WEEKLY-MODEL-%03d',
                $this->sequence
            ),
            'full_name' => sprintf(
                'Karyawan Model Mingguan %03d',
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
                'Pola Model Mingguan %03d',
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