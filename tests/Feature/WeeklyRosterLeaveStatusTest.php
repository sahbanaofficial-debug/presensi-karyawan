<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WeeklyScheduleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WeeklyRosterLeaveStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_hrd_can_store_and_view_leave_item_without_work_schedule(): void
    {
        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $hrd = User::factory()->create([
            'branch_id' => null,
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $employeeUser = User::factory()->create([
            'branch_id' => $branch->id,
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'KRY-CUTI-001',
            'full_name' => 'Karyawan Pengujian Cuti',
            'position' => 'Staf Pengujian',
            'phone_number' => '081234567890',
            'employment_status' => 'active',
        ]);

        $response = $this
            ->actingAs($hrd)
            ->postJson(
                route('weekly-rosters.store'),
                [
                    'branch_id' => $branch->id,
                    'week_start_date' => '2026-08-03',

                    'items' => [
                        [
                            'employee_id' => $employee->id,
                            'work_schedule_id' => null,
                            'schedule_date' => '2026-08-03',
                            'schedule_status' => 'leave',
                            'notes' => 'Cuti tahunan.',
                        ],
                    ],
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Roster mingguan berhasil disimpan sebagai draft.'
            )
            ->assertJsonPath('data.items_count', 1);

        $weeklySchedule = WeeklySchedule::query()
            ->where('branch_id', $branch->id)
            ->whereDate(
                'week_start_date',
                '2026-08-03'
            )
            ->firstOrFail();

        $this->assertDatabaseHas(
            'weekly_schedule_items',
            [
                'weekly_schedule_id' => $weeklySchedule->id,
                'employee_id' => $employee->id,
                'work_schedule_id' => null,
                'schedule_status' => 'leave',
                'notes' => 'Cuti tahunan.',
            ]
        );

        $this->assertTrue(
            WeeklyScheduleItem::query()
                ->where(
                    'weekly_schedule_id',
                    $weeklySchedule->id
                )
                ->where('employee_id', $employee->id)
                ->whereDate(
                    'schedule_date',
                    '2026-08-03'
                )
                ->exists()
        );

        $this
            ->actingAs($hrd)
            ->get(
                route(
                    'weekly-rosters.show',
                    $weeklySchedule
                )
            )
            ->assertOk()
            ->assertSee('Cuti')
            ->assertSee('text-bg-danger', false);
    }
}
