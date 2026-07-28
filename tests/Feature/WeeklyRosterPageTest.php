<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WorkSchedule;
use App\Services\WeeklyRosterService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WeeklyRosterPageTest extends TestCase
{
    use RefreshDatabase;

    private WeeklyRosterService $service;

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(
            WeeklyRosterService::class
        );
    }

    public function test_guest_is_redirected_from_weekly_roster_pages(): void
    {
        $this->get(
            route('weekly-rosters.index')
        )->assertRedirect(route('login'));

        $this->get(
            route('weekly-rosters.create')
        )->assertRedirect(route('login'));
    }

    public function test_admin_and_employee_cannot_access_weekly_roster_pages(): void
    {
        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee =
            $this->createEmployee($branch);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $employeeUser =
            $employee->user()->firstOrFail();

        foreach (
            [
                $admin,
                $employeeUser,
            ] as $user
        ) {
            $this->actingAs($user)
                ->get(
                    route(
                        'weekly-rosters.index'
                    )
                )
                ->assertForbidden();

            $this->actingAs($user)
                ->get(
                    route(
                        'weekly-rosters.create'
                    )
                )
                ->assertForbidden();
        }
    }

    public function test_hrd_can_open_index_and_see_roster(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'code' => 'WEEK-INDEX',
            'name' => 'Cabang Roster Index',
            'status' => 'active',
        ]);

        $employee =
            $this->createEmployee(
                $branch,
                [
                    'employee_number' => 'EMP-WEEK-INDEX',

                    'full_name' => 'Karyawan Roster Index',
                ]
            );

        $workSchedule =
            $this->createWorkSchedule([
                'name' => 'Pola Roster Index',
            ]);

        $weeklySchedule =
            $this->createRoster(
                $hrd,
                $branch,
                $employee,
                $workSchedule,
                '2026-08-03'
            );

        $this->actingAs($hrd)
            ->get(
                route(
                    'weekly-rosters.index'
                )
            )
            ->assertOk()
            ->assertViewIs(
                'weekly-rosters.index'
            )
            ->assertViewHas(
                'weeklySchedules',
                static fn ($paginator): bool => $paginator
                    ->getCollection()
                    ->contains(
                        static fn (
                            WeeklySchedule $roster
                        ): bool => $roster->is(
                            $weeklySchedule
                        )
                    )
            )
            ->assertSee('Roster Mingguan')
            ->assertSee('WEEK-INDEX')
            ->assertSee('Cabang Roster Index')
            ->assertSee('Draft');
    }

    public function test_index_supports_branch_status_and_week_filters(): void
    {
        $hrd = $this->createHrd();

        $firstBranch =
            Branch::factory()->create([
                'code' => 'WEEK-FILTER-A',
                'status' => 'active',
            ]);

        $secondBranch =
            Branch::factory()->create([
                'code' => 'WEEK-FILTER-B',
                'status' => 'active',
            ]);

        $firstEmployee =
            $this->createEmployee(
                $firstBranch
            );

        $secondEmployee =
            $this->createEmployee(
                $secondBranch
            );

        $workSchedule =
            $this->createWorkSchedule();

        $expected = $this->createRoster(
            $hrd,
            $firstBranch,
            $firstEmployee,
            $workSchedule,
            '2026-08-03'
        );

        $published = $this->createRoster(
            $hrd,
            $secondBranch,
            $secondEmployee,
            $workSchedule,
            '2026-08-10'
        );

        $this->service->publish(
            $published,
            $hrd
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'weekly-rosters.index',
                    [
                        'branch_id' => $firstBranch->id,

                        'status' => 'draft',

                        'week_start_date' => '2026-08-03',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedBranchId',
                $firstBranch->id
            )
            ->assertViewHas(
                'selectedStatus',
                'draft'
            )
            ->assertViewHas(
                'selectedWeekStartDate',
                '2026-08-03'
            )
            ->assertViewHas(
                'weeklySchedules',
                static function (
                    $paginator
                ) use ($expected): bool {
                    return $paginator->count() === 1
                        && $paginator
                            ->first()
                            ->is($expected);
                }
            );
    }

    public function test_index_uses_fifteen_items_per_page(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee =
            $this->createEmployee($branch);

        $workSchedule =
            $this->createWorkSchedule();

        $weekStart =
            CarbonImmutable::parse(
                '2026-09-07'
            );

        for (
            $index = 0;
            $index < 16;
            $index++
        ) {
            $this->createRoster(
                $hrd,
                $branch,
                $employee,
                $workSchedule,
                $weekStart
                    ->addWeeks($index)
                    ->toDateString()
            );
        }

        $this->actingAs($hrd)
            ->get(
                route(
                    'weekly-rosters.index'
                )
            )
            ->assertOk()
            ->assertViewHas(
                'weeklySchedules',
                static fn ($paginator): bool => $paginator->count() === 15
                    && $paginator->total() === 16
                    && $paginator->lastPage() === 2
            );
    }

    public function test_create_page_only_displays_active_reference_data(): void
    {
        $hrd = $this->createHrd();

        $activeBranch =
            Branch::factory()->create([
                'code' => 'WEEK-CREATE-ACTIVE',
                'name' => 'Cabang Roster Aktif',

                'status' => 'active',
            ]);

        $inactiveBranch =
            Branch::factory()->create([
                'code' => 'WEEK-CREATE-INACTIVE',

                'name' => 'Cabang Roster Tidak Aktif',

                'status' => 'inactive',
            ]);

        $activeEmployee =
            $this->createEmployee(
                $activeBranch,
                [
                    'employee_number' => 'EMP-WEEK-ACTIVE',

                    'full_name' => 'Karyawan Roster Aktif',
                ]
            );

        $inactiveEmployee =
            $this->createEmployee(
                $activeBranch,
                [
                    'employee_number' => 'EMP-WEEK-INACTIVE',

                    'full_name' => 'Karyawan Roster Tidak Aktif',

                    'employment_status' => 'inactive',
                ]
            );

        $otherBranchEmployee =
            $this->createEmployee(
                $inactiveBranch,
                [
                    'employee_number' => 'EMP-WEEK-OTHER-BRANCH',

                    'full_name' => 'Karyawan Cabang Tidak Aktif',
                ]
            );

        $activeWorkSchedule =
            $this->createWorkSchedule([
                'name' => 'Pola Roster Aktif',

                'status' => 'active',
            ]);

        $inactiveWorkSchedule =
            $this->createWorkSchedule([
                'name' => 'Pola Roster Tidak Aktif',

                'status' => 'inactive',
            ]);

        $this->actingAs($hrd)
            ->get(
                route(
                    'weekly-rosters.create'
                )
            )
            ->assertOk()
            ->assertViewIs(
                'weekly-rosters.create'
            )
            ->assertSee(
                'Susun Roster Mingguan'
            )
            ->assertSee(
                $activeBranch->code
            )
            ->assertSee(
                $activeEmployee
                    ->employee_number
            )
            ->assertSee(
                $activeEmployee->full_name
            )
            ->assertSee(
                $activeWorkSchedule->name
            )
            ->assertDontSee(
                $inactiveBranch->code
            )
            ->assertDontSee(
                $inactiveEmployee
                    ->employee_number
            )
            ->assertDontSee(
                $otherBranchEmployee
                    ->employee_number
            )
            ->assertDontSee(
                $inactiveWorkSchedule->name
            );
    }

    public function test_hrd_can_open_roster_detail_with_snapshot_data(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'code' => 'WEEK-DETAIL',
            'name' => 'Cabang Roster Detail',
            'status' => 'active',
        ]);

        $employee =
            $this->createEmployee(
                $branch,
                [
                    'employee_number' => 'EMP-WEEK-DETAIL',

                    'full_name' => 'Karyawan Roster Detail',
                ]
            );

        $workSchedule =
            $this->createWorkSchedule([
                'name' => 'Pola Snapshot Detail',

                'check_in_time' => '08:45:00',

                'check_out_time' => '17:00:00',
            ]);

        $weeklySchedule =
            $this->createRoster(
                $hrd,
                $branch,
                $employee,
                $workSchedule,
                '2026-08-03'
            );

        $workSchedule->update([
            'name' => 'Pola Master Sudah Diubah',
        ]);

        $this->actingAs($hrd)
            ->get(
                route(
                    'weekly-rosters.show',
                    $weeklySchedule
                )
            )
            ->assertOk()
            ->assertViewIs(
                'weekly-rosters.show'
            )
            ->assertViewHas(
                'weeklySchedule',
                static fn (
                    WeeklySchedule $viewRoster
                ): bool => $viewRoster->is(
                    $weeklySchedule
                )
            )
            ->assertSee(
                'Detail Roster Mingguan'
            )
            ->assertSee('WEEK-DETAIL')
            ->assertSee(
                'Karyawan Roster Detail'
            )
            ->assertSee(
                'EMP-WEEK-DETAIL'
            )
            ->assertSee(
                'Pola Snapshot Detail'
            )
            ->assertDontSee(
                'Pola Master Sudah Diubah'
            )
            ->assertSee(
                'Publikasikan Roster'
            );
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

        $user = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::query()->create(
            array_replace(
                [
                    'user_id' => $user->id,

                    'branch_id' => $branch->id,

                    'employee_number' => sprintf(
                        'EMP-WEEK-PAGE-%03d',
                        $this
                            ->employeeSequence
                    ),

                    'full_name' => sprintf(
                        'Karyawan Roster Page %03d',
                        $this
                            ->employeeSequence
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
                        'Pola Roster Page %03d',
                        $this
                            ->workScheduleSequence
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

    private function createRoster(
        User $hrd,
        Branch $branch,
        Employee $employee,
        WorkSchedule $workSchedule,
        string $weekStartDate
    ): WeeklySchedule {
        return $this->service->createDraft(
            [
                'branch_id' => $branch->id,

                'week_start_date' => $weekStartDate,

                'items' => [
                    [
                        'employee_id' => $employee->id,

                        'work_schedule_id' => $workSchedule->id,

                        'schedule_date' => $weekStartDate,

                        'schedule_status' => 'work',

                        'notes' => 'Shift utama roster',
                    ],
                ],
            ],
            $hrd
        );
    }
}
