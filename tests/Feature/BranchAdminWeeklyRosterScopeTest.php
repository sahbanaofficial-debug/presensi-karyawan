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

final class BranchAdminWeeklyRosterScopeTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeSequence = 0;

    public function test_assigned_admin_index_is_scoped_to_own_branch(): void
    {
        $ownBranch = $this->createBranch(
            'OWN',
            'Cabang Sendiri'
        );

        $otherBranch = $this->createBranch(
            'OTHER',
            'Cabang Lain'
        );

        $admin = $this->createAdmin($ownBranch);
        $hrd = $this->createHrd();

        $ownRoster = $this->createRoster(
            $ownBranch,
            $admin,
            '2026-08-03'
        );

        $otherRoster = $this->createRoster(
            $otherBranch,
            $hrd,
            '2026-08-03'
        );

        $response = $this->actingAs($admin)
            ->get(
                route(
                    'weekly-rosters.index',
                    [
                        'branch_id' => $otherBranch
                            ->getKey(),
                    ]
                )
            )
            ->assertOk();

        $response->assertViewHas(
            'selectedBranchId',
            $ownBranch->getKey()
        );

        $response->assertViewHas(
            'branches',
            static fn ($branches): bool => $branches->modelKeys()
                    === [$ownBranch->getKey()]
        );

        $response->assertViewHas(
            'weeklySchedules',
            static function ($paginator) use (
                $ownRoster,
                $otherRoster
            ): bool {
                $ids = collect(
                    $paginator->items()
                )
                    ->pluck('id')
                    ->all();

                return in_array(
                    $ownRoster->getKey(),
                    $ids,
                    true
                )
                    && ! in_array(
                        $otherRoster->getKey(),
                        $ids,
                        true
                    );
            }
        );
    }

    public function test_assigned_admin_create_page_only_loads_own_branch_employees(): void
    {
        $ownBranch = $this->createBranch(
            'OWN',
            'Cabang Sendiri'
        );

        $otherBranch = $this->createBranch(
            'OTHER',
            'Cabang Lain'
        );

        $admin = $this->createAdmin($ownBranch);

        $ownEmployee = $this->createEmployee(
            $ownBranch,
            'Karyawan Cabang Sendiri'
        );

        $otherEmployee = $this->createEmployee(
            $otherBranch,
            'Karyawan Cabang Lain'
        );

        $this->createWorkSchedule();

        $response = $this->actingAs($admin)
            ->get(
                route('weekly-rosters.create')
            )
            ->assertOk();

        $response->assertViewHas(
            'branches',
            static function ($branches) use (
                $ownBranch,
                $ownEmployee,
                $otherEmployee
            ): bool {
                if (
                    $branches->modelKeys()
                    !== [$ownBranch->getKey()]
                ) {
                    return false;
                }

                $employeeIds = $branches
                    ->first()
                    ->employees
                    ->modelKeys();

                return in_array(
                    $ownEmployee->getKey(),
                    $employeeIds,
                    true
                )
                    && ! in_array(
                        $otherEmployee->getKey(),
                        $employeeIds,
                        true
                    );
            }
        );
    }

    public function test_assigned_admin_can_open_own_roster_but_not_another_branch_roster(): void
    {
        $ownBranch = $this->createBranch(
            'OWN',
            'Cabang Sendiri'
        );

        $otherBranch = $this->createBranch(
            'OTHER',
            'Cabang Lain'
        );

        $admin = $this->createAdmin($ownBranch);
        $hrd = $this->createHrd();

        $ownRoster = $this->createRoster(
            $ownBranch,
            $admin,
            '2026-08-03'
        );

        $otherRoster = $this->createRoster(
            $otherBranch,
            $hrd,
            '2026-08-03'
        );

        $this->actingAs($admin)
            ->get(
                route(
                    'weekly-rosters.show',
                    $ownRoster
                )
            )
            ->assertOk();

        $this->actingAs($admin)
            ->get(
                route(
                    'weekly-rosters.show',
                    $otherRoster
                )
            )
            ->assertForbidden();
    }

    public function test_unassigned_admin_is_forbidden_from_roster_pages(): void
    {
        $admin = User::factory()->create([
            'branch_id' => null,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(
                route('weekly-rosters.index')
            )
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(
                route('weekly-rosters.create')
            )
            ->assertForbidden();
    }

    public function test_employee_remains_forbidden_from_roster_pages(): void
    {
        $branch = $this->createBranch(
            'EMPLOYEE',
            'Cabang Karyawan'
        );

        $employeeProfile = $this->createEmployee(
            $branch,
            'Karyawan Terbatas'
        );

        $employeeUser = $employeeProfile
            ->user()
            ->firstOrFail();

        $this->actingAs($employeeUser)
            ->get(
                route('weekly-rosters.index')
            )
            ->assertForbidden();
    }

    public function test_hrd_index_retains_cross_branch_visibility(): void
    {
        $firstBranch = $this->createBranch(
            'FIRST',
            'Cabang Pertama'
        );

        $secondBranch = $this->createBranch(
            'SECOND',
            'Cabang Kedua'
        );

        $hrd = $this->createHrd();

        $firstRoster = $this->createRoster(
            $firstBranch,
            $hrd,
            '2026-08-03'
        );

        $secondRoster = $this->createRoster(
            $secondBranch,
            $hrd,
            '2026-08-03'
        );

        $response = $this->actingAs($hrd)
            ->get(
                route('weekly-rosters.index')
            )
            ->assertOk();

        $response->assertViewHas(
            'branches',
            static fn ($branches): bool => $branches->count() === 2
        );

        $response->assertViewHas(
            'weeklySchedules',
            static function ($paginator) use (
                $firstRoster,
                $secondRoster
            ): bool {
                $ids = collect(
                    $paginator->items()
                )
                    ->pluck('id')
                    ->all();

                return in_array(
                    $firstRoster->getKey(),
                    $ids,
                    true
                )
                    && in_array(
                        $secondRoster->getKey(),
                        $ids,
                        true
                    );
            }
        );
    }

    private function createBranch(
        string $code,
        string $name
    ): Branch {
        return Branch::factory()->create([
            'code' => $code,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    private function createAdmin(
        Branch $branch
    ): User {
        return User::factory()->create([
            'branch_id' => $branch->getKey(),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function createHrd(): User
    {
        return User::factory()->create([
            'branch_id' => null,
            'role' => 'hrd',
            'status' => 'active',
        ]);
    }

    private function createEmployee(
        Branch $branch,
        string $name
    ): Employee {
        $this->employeeSequence++;

        $user = User::factory()->create([
            'branch_id' => null,
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::query()->create([
            'user_id' => $user->getKey(),
            'branch_id' => $branch->getKey(),
            'employee_number' => sprintf(
                'SCOPE-%03d',
                $this->employeeSequence
            ),
            'full_name' => $name,
            'position' => 'Staff',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);
    }

    private function createWorkSchedule(): WorkSchedule
    {
        return WorkSchedule::query()->create([
            'name' => 'Pagi 08.45-17.00',
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'check_in_limit_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);
    }

    private function createRoster(
        Branch $branch,
        User $creator,
        string $weekStartDate
    ): WeeklySchedule {
        return WeeklySchedule::query()->create([
            'branch_id' => $branch->getKey(),
            'week_start_date' => $weekStartDate,
            'week_end_date' => '2026-08-09',
            'status' => WeeklySchedule::STATUS_DRAFT,
            'created_by' => $creator->getKey(),
            'published_by' => null,
            'published_at' => null,
            'notes' => null,
        ]);
    }
}
