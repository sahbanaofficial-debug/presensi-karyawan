<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WeeklyRosterControllerTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    public function test_guest_is_redirected_from_weekly_roster_store(): void
    {
        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee =
            $this->createEmployee($branch);

        $workSchedule =
            $this->createWorkSchedule();

        $this->post(
            route('weekly-rosters.store'),
            $this->payload(
                $branch,
                $employee,
                $workSchedule
            )
        )->assertRedirect(route('login'));

        $this->assertDatabaseCount(
            'weekly_schedules',
            0
        );
    }

    public function test_admin_and_employee_cannot_store_weekly_roster(): void
    {
        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee =
            $this->createEmployee($branch);

        $workSchedule =
            $this->createWorkSchedule();

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $employeeUser = $employee
            ->user()
            ->firstOrFail();

        foreach ([$admin, $employeeUser] as $user) {
            $this->actingAs($user)
                ->post(
                    route(
                        'weekly-rosters.store'
                    ),
                    $this->payload(
                        $branch,
                        $employee,
                        $workSchedule
                    )
                )
                ->assertForbidden();
        }

        $this->assertDatabaseCount(
            'weekly_schedules',
            0
        );
    }

    public function test_hrd_can_store_weekly_roster_draft(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee =
            $this->createEmployee($branch);

        $workSchedule =
            $this->createWorkSchedule();

        $response = $this->actingAs($hrd)
            ->post(
                route(
                    'weekly-rosters.store'
                ),
                $this->payload(
                    $branch,
                    $employee,
                    $workSchedule
                )
            );

        $weeklySchedule =
            WeeklySchedule::query()
                ->firstOrFail();

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Roster mingguan berhasil disimpan sebagai draft.'
            )
            ->assertJsonPath(
                'data.id',
                $weeklySchedule->id
            )
            ->assertJsonPath(
                'data.branch_id',
                $branch->id
            )
            ->assertJsonPath(
                'data.week_start_date',
                '2026-08-03'
            )
            ->assertJsonPath(
                'data.week_end_date',
                '2026-08-09'
            )
            ->assertJsonPath(
                'data.status',
                'draft'
            )
            ->assertJsonPath(
                'data.items_count',
                2
            );

        $this->assertDatabaseHas(
            'weekly_schedules',
            [
                'id' => $weeklySchedule->id,
                'branch_id' => $branch->id,
                'status' => 'draft',
                'created_by' => $hrd->id,
            ]
        );

        $this->assertDatabaseCount(
            'weekly_schedule_items',
            2
        );

        $this->assertDatabaseCount(
            'employee_schedules',
            0
        );
    }

    public function test_invalid_payload_is_rejected(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee =
            $this->createEmployee($branch);

        $workSchedule =
            $this->createWorkSchedule();

        $payload = $this->payload(
            $branch,
            $employee,
            $workSchedule
        );

        $payload['week_start_date'] =
            '2026-08-04';

        $this->actingAs($hrd)
            ->postJson(
                route(
                    'weekly-rosters.store'
                ),
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'week_start_date',
            ]);

        $this->assertDatabaseCount(
            'weekly_schedules',
            0
        );
    }

    public function test_hrd_can_publish_weekly_roster(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee =
            $this->createEmployee($branch);

        $workSchedule =
            $this->createWorkSchedule();

        $this->actingAs($hrd)
            ->post(
                route(
                    'weekly-rosters.store'
                ),
                $this->payload(
                    $branch,
                    $employee,
                    $workSchedule
                )
            )
            ->assertCreated();

        $weeklySchedule =
            WeeklySchedule::query()
                ->firstOrFail();

        $this->actingAs($hrd)
            ->patch(
                route(
                    'weekly-rosters.publish',
                    $weeklySchedule
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Roster mingguan berhasil dipublikasikan.'
            )
            ->assertJsonPath(
                'data.status',
                'published'
            )
            ->assertJsonPath(
                'data.published_by',
                $hrd->id
            )
            ->assertJsonPath(
                'data.items_count',
                2
            );

        $weeklySchedule->refresh();

        $this->assertSame(
            'published',
            $weeklySchedule->status
        );

        $this->assertSame(
            $hrd->id,
            $weeklySchedule->published_by
        );

        $this->assertNotNull(
            $weeklySchedule->published_at
        );

        $this->assertDatabaseCount(
            'employee_schedules',
            2
        );
    }

    public function test_published_roster_cannot_be_published_again(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee =
            $this->createEmployee($branch);

        $workSchedule =
            $this->createWorkSchedule();

        $this->actingAs($hrd)
            ->post(
                route(
                    'weekly-rosters.store'
                ),
                $this->payload(
                    $branch,
                    $employee,
                    $workSchedule
                )
            )
            ->assertCreated();

        $weeklySchedule =
            WeeklySchedule::query()
                ->firstOrFail();

        $publishRoute = route(
            'weekly-rosters.publish',
            $weeklySchedule
        );

        $this->actingAs($hrd)
            ->patch($publishRoute)
            ->assertOk();

        $this->actingAs($hrd)
            ->patchJson($publishRoute)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'publish',
            ]);

        $this->assertDatabaseCount(
            'weekly_schedules',
            1
        );

        $this->assertDatabaseCount(
            'employee_schedules',
            2
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
                'EMP-ROSTER-ENDPOINT-%03d',
                $this->employeeSequence
            ),

            'full_name' => sprintf(
                'Karyawan Roster Endpoint %03d',
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
        $this->workScheduleSequence++;

        return WorkSchedule::query()->create(
            array_replace(
                [
                    'name' => sprintf(
                        'Pola Roster Endpoint %03d',
                        $this->workScheduleSequence
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
