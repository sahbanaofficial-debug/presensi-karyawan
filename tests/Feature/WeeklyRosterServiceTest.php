<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\WeeklyRosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class WeeklyRosterServiceTest extends TestCase
{
    use RefreshDatabase;

    private WeeklyRosterService $service;

    private int $employeeSequence = 0;

    private int $scheduleSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(
            WeeklyRosterService::class
        );
    }

    public function test_hrd_can_persist_draft_with_schedule_snapshots(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule([
                'name' => 'Shift Snapshot Awal',

                'check_in_time' => '08:45:00',

                'check_out_time' => '17:00:00',

                'check_in_open_minutes' => 25,

                'late_tolerance_minutes' => 10,

                'check_in_limit_minutes' => 35,

                'check_out_limit_minutes' => 75,
            ]);

        $roster = $this->service->createDraft(
            $this->payload(
                $branch,
                $employee,
                $workSchedule
            ),
            $hrd
        );

        $this->assertSame(
            'draft',
            $roster->status
        );

        $this->assertSame(
            $branch->id,
            $roster->branch_id
        );

        $this->assertSame(
            $hrd->id,
            $roster->created_by
        );

        $this->assertCount(
            2,
            $roster->items
        );

        $workItem = $roster->items
            ->firstWhere(
                'schedule_status',
                'work'
            );

        $offItem = $roster->items
            ->firstWhere(
                'schedule_status',
                'off'
            );

        $this->assertNotNull($workItem);
        $this->assertNotNull($offItem);

        $this->assertSame(
            'Shift Snapshot Awal',
            $workItem->work_schedule_name_snapshot
        );

        $this->assertSame(
            25,
            $workItem
                ->check_in_open_minutes_snapshot
        );

        $this->assertSame(
            10,
            $workItem
                ->late_tolerance_minutes_snapshot
        );

        $this->assertSame(
            35,
            $workItem
                ->check_in_limit_minutes_snapshot
        );

        $this->assertSame(
            75,
            $workItem
                ->check_out_limit_minutes_snapshot
        );

        $this->assertNull(
            $offItem->work_schedule_id
        );

        $this->assertNull(
            $offItem->work_schedule_name_snapshot
        );

        $workSchedule->update([
            'name' => 'Shift Master Diubah',

            'check_in_time' => '09:00:00',
        ]);

        $workItem->refresh();

        $this->assertSame(
            'Shift Snapshot Awal',
            $workItem->work_schedule_name_snapshot
        );

        $this->assertSame(
            '08:45:00',
            substr(
                (string) $workItem
                    ->getRawOriginal(
                        'check_in_time_snapshot'
                    ),
                0,
                8
            )
        );
    }

    public function test_duplicate_branch_and_week_is_rejected(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule();

        $payload = $this->payload(
            $branch,
            $employee,
            $workSchedule
        );

        $this->service->createDraft(
            $payload,
            $hrd
        );

        try {
            $this->service->createDraft(
                $payload,
                $hrd
            );

            $this->fail(
                'Roster duplikat seharusnya ditolak.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'week_start_date',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'weekly_schedules',
            1
        );

        $this->assertDatabaseCount(
            'weekly_schedule_items',
            2
        );
    }

    public function test_draft_can_be_published_to_daily_schedules(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule();

        $roster = $this->service->createDraft(
            $this->payload(
                $branch,
                $employee,
                $workSchedule
            ),
            $hrd
        );

        $published = $this->service->publish(
            $roster,
            $hrd
        );

        $this->assertSame(
            'published',
            $published->status
        );

        $this->assertSame(
            $hrd->id,
            $published->published_by
        );

        $this->assertNotNull(
            $published->published_at
        );

        $this->assertDatabaseCount(
            'employee_schedules',
            2
        );

        foreach ($published->items as $item) {
            $dailySchedule =
                EmployeeSchedule::query()
                    ->where(
                        'weekly_schedule_item_id',
                        $item->id
                    )
                    ->firstOrFail();

            $this->assertSame(
                $item->employee_id,
                $dailySchedule->employee_id
            );

            $this->assertSame(
                $item->work_schedule_id,
                $dailySchedule
                    ->work_schedule_id
            );

            $this->assertSame(
                $item->schedule_status,
                $dailySchedule
                    ->schedule_status
            );

            $this->assertSame(
                $hrd->id,
                $dailySchedule->approved_by
            );

            $this->assertSame(
                $item->id,
                $dailySchedule
                    ->weekly_schedule_item_id
            );
        }
    }

    public function test_publication_is_atomic_when_daily_schedule_conflicts(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule();

        $roster = $this->service->createDraft(
            $this->payload(
                $branch,
                $employee,
                $workSchedule
            ),
            $hrd
        );

        EmployeeSchedule::query()->create([
            'employee_id' => $employee->id,

            'work_schedule_id' => $workSchedule->id,

            'weekly_schedule_item_id' => null,

            'schedule_date' => '2026-08-03',

            'schedule_status' => 'work',

            'approved_by' => $hrd->id,

            'notes' => 'Jadwal manual existing',
        ]);

        try {
            $this->service->publish(
                $roster,
                $hrd
            );

            $this->fail(
                'Publikasi konflik seharusnya ditolak.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'publish',
                $exception->errors()
            );
        }

        $roster->refresh();

        $this->assertSame(
            'draft',
            $roster->status
        );

        $this->assertNull(
            $roster->published_by
        );

        $this->assertNull(
            $roster->published_at
        );

        $this->assertDatabaseCount(
            'employee_schedules',
            1
        );
    }

    public function test_published_roster_cannot_be_published_again(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule();

        $roster = $this->service->createDraft(
            $this->payload(
                $branch,
                $employee,
                $workSchedule
            ),
            $hrd
        );

        $this->service->publish(
            $roster,
            $hrd
        );

        try {
            $this->service->publish(
                $roster,
                $hrd
            );

            $this->fail(
                'Roster published seharusnya tidak dapat dipublikasikan ulang.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'publish',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'employee_schedules',
            2
        );

        $this->assertDatabaseCount(
            'weekly_schedules',
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

    private function createEmployee(
        Branch $branch
    ): Employee {
        $this->employeeSequence++;

        $user = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::query()->create([
            'user_id' => $user->id,

            'branch_id' => $branch->id,

            'employee_number' => sprintf(
                'EMP-ROSTER-SERVICE-%03d',
                $this->employeeSequence
            ),

            'full_name' => sprintf(
                'Karyawan Roster Service %03d',
                $this->employeeSequence
            ),

            'position' => 'Karyawan',

            'phone_number' => null,

            'employment_status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createWorkSchedule(
        array $overrides = []
    ): WorkSchedule {
        $this->scheduleSequence++;

        return WorkSchedule::query()->create(
            array_replace(
                [
                    'name' => sprintf(
                        'Pola Roster Service %03d',
                        $this->scheduleSequence
                    ),

                    'check_in_time' => '08:45:00',

                    'check_out_time' => '17:00:00',

                    'check_in_open_minutes' => 30,

                    'late_tolerance_minutes' => 5,

                    'check_in_limit_minutes' => 30,

                    'check_out_limit_minutes' => 60,

                    'status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        Branch $branch,
        Employee $employee,
        WorkSchedule $workSchedule
    ): array {
        return [
            'branch_id' => $branch->id,

            'week_start_date' => '2026-08-03',

            'items' => [
                [
                    'employee_id' => $employee->id,

                    'work_schedule_id' => $workSchedule->id,

                    'schedule_date' => '2026-08-03',

                    'schedule_status' => 'work',

                    'notes' => 'Shift utama',
                ],

                [
                    'employee_id' => $employee->id,

                    'work_schedule_id' => null,

                    'schedule_date' => '2026-08-04',

                    'schedule_status' => 'off',

                    'notes' => 'Libur terjadwal',
                ],
            ],
        ];
    }
}
