<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\StoreWeeklyScheduleRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WorkSchedule;
use App\Services\WeeklyRosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class BranchAdminWeeklyRosterAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT =
        '/_tests/branch-admin-weekly-rosters';

    private WeeklyRosterService $service;

    private int $employeeSequence = 0;

    private int $scheduleSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(
            WeeklyRosterService::class
        );

        Route::post(
            self::ENDPOINT,
            static function (
                StoreWeeklyScheduleRequest $request
            ): JsonResponse {
                return response()->json(
                    [
                        'data' => $request->validated(),
                    ],
                    201
                );
            }
        )->middleware('web');
    }

    public function test_branch_admin_request_is_authorized_for_assigned_branch(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createAdmin($branch);
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $this->actingAs($admin)
            ->postJson(
                self::ENDPOINT,
                $this->payload(
                    branch: $branch,
                    employee: $employee,
                    workSchedule: $workSchedule,
                    weekStartDate: '2026-08-03'
                )
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.branch_id',
                $branch->getKey()
            );
    }

    public function test_branch_admin_request_is_forbidden_for_another_branch(): void
    {
        $assignedBranch = $this->createBranch();
        $otherBranch = $this->createBranch();
        $admin = $this->createAdmin(
            $assignedBranch
        );
        $employee = $this->createEmployee(
            $otherBranch
        );
        $workSchedule = $this->createWorkSchedule();

        $this->actingAs($admin)
            ->postJson(
                self::ENDPOINT,
                $this->payload(
                    branch: $otherBranch,
                    employee: $employee,
                    workSchedule: $workSchedule,
                    weekStartDate: '2026-08-03'
                )
            )
            ->assertForbidden();
    }

    public function test_branch_admin_can_create_and_publish_assigned_branch_roster(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createAdmin($branch);
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $roster = $this->service->createDraft(
            $this->payload(
                branch: $branch,
                employee: $employee,
                workSchedule: $workSchedule,
                weekStartDate: '2026-08-03'
            ),
            $admin
        );

        $published = $this->service->publish(
            $roster,
            $admin
        );

        $this->assertTrue($published->isPublished());

        $this->assertSame(
            $admin->getKey(),
            $published->published_by
        );

        $this->assertTrue(
            EmployeeSchedule::query()
                ->where(
                    'employee_id',
                    $employee->getKey()
                )
                ->where(
                    'weekly_schedule_item_id',
                    $roster
                        ->items()
                        ->value('id')
                )
                ->whereDate(
                    'schedule_date',
                    '2026-08-03'
                )
                ->where(
                    'schedule_status',
                    'work'
                )
                ->where(
                    'approved_by',
                    $admin->getKey()
                )
                ->exists()
        );
    }

    public function test_branch_admin_cannot_create_roster_for_another_branch(): void
    {
        $assignedBranch = $this->createBranch();
        $otherBranch = $this->createBranch();
        $admin = $this->createAdmin(
            $assignedBranch
        );
        $employee = $this->createEmployee(
            $otherBranch
        );
        $workSchedule = $this->createWorkSchedule();

        try {
            $this->service->createDraft(
                $this->payload(
                    branch: $otherBranch,
                    employee: $employee,
                    workSchedule: $workSchedule,
                    weekStartDate: '2026-08-03'
                ),
                $admin
            );

            $this->fail(
                'Admin Cabang tidak boleh membuat roster cabang lain.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'authorization',
                $exception->errors()
            );
        }

        $this->assertDatabaseMissing(
            'weekly_schedules',
            [
                'branch_id' => $otherBranch->getKey(),
                'week_start_date' => '2026-08-03',
            ]
        );
    }

    public function test_branch_admin_cannot_publish_roster_for_another_branch(): void
    {
        $assignedBranch = $this->createBranch();
        $otherBranch = $this->createBranch();
        $admin = $this->createAdmin(
            $assignedBranch
        );
        $hrd = $this->createHrd();
        $employee = $this->createEmployee(
            $otherBranch
        );
        $workSchedule = $this->createWorkSchedule();

        $roster = $this->service->createDraft(
            $this->payload(
                branch: $otherBranch,
                employee: $employee,
                workSchedule: $workSchedule,
                weekStartDate: '2026-08-03'
            ),
            $hrd
        );

        try {
            $this->service->publish(
                $roster,
                $admin
            );

            $this->fail(
                'Admin Cabang tidak boleh menerbitkan roster cabang lain.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'authorization',
                $exception->errors()
            );
        }

        $this->assertDatabaseHas(
            'weekly_schedules',
            [
                'id' => $roster->getKey(),
                'status' => WeeklySchedule::STATUS_DRAFT,
                'published_by' => null,
            ]
        );

        $this->assertSame(
            0,
            EmployeeSchedule::query()->count()
        );
    }

    public function test_inactive_or_unassigned_admin_cannot_manage_roster(): void
    {
        $branch = $this->createBranch();

        $inactiveAdmin = User::factory()->create([
            'branch_id' => $branch->getKey(),
            'role' => 'admin',
            'status' => 'inactive',
        ]);

        $unassignedAdmin = User::factory()->create([
            'branch_id' => null,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->assertFalse(
            $inactiveAdmin
                ->canManageWeeklyRosterForBranch(
                    $branch->getKey()
                )
        );

        $this->assertFalse(
            $unassignedAdmin
                ->canManageWeeklyRosterForBranch(
                    $branch->getKey()
                )
        );
    }

    private function createBranch(): Branch
    {
        return Branch::factory()->create([
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
        Branch $branch
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
                'BAUTH-%03d',
                $this->employeeSequence
            ),
            'full_name' => sprintf(
                'Karyawan Otorisasi %03d',
                $this->employeeSequence
            ),
            'position' => 'Staff',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);
    }

    private function createWorkSchedule(): WorkSchedule
    {
        $this->scheduleSequence++;

        return WorkSchedule::query()->create([
            'name' => sprintf(
                'Pola Otorisasi %03d',
                $this->scheduleSequence
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

    /**
     * @return array{
     *     branch_id: int,
     *     week_start_date: string,
     *     items: list<array{
     *         employee_id: int,
     *         work_schedule_id: int,
     *         schedule_date: string,
     *         schedule_status: string,
     *         notes: null
     *     }>
     * }
     */
    private function payload(
        Branch $branch,
        Employee $employee,
        WorkSchedule $workSchedule,
        string $weekStartDate
    ): array {
        return [
            'branch_id' => $branch->getKey(),
            'week_start_date' => $weekStartDate,
            'items' => [
                [
                    'employee_id' => $employee->getKey(),
                    'work_schedule_id' => $workSchedule
                        ->getKey(),
                    'schedule_date' => $weekStartDate,
                    'schedule_status' => 'work',
                    'notes' => null,
                ],
            ],
        ];
    }
}
