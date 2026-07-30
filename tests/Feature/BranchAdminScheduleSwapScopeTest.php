<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\ScheduleSwapRequest;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

final class BranchAdminScheduleSwapScopeTest extends TestCase
{
    use RefreshDatabase;

    private int $branchSequence = 0;

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    public function test_assigned_admin_index_and_create_only_expose_own_branch_data(): void
    {
        $hrd = $this->createUser('hrd');

        $ownBranch = $this->createBranch();
        $foreignBranch = $this->createBranch();

        $ownPair = $this->createPairWithSchedules(
            branch: $ownBranch,
            approver: $hrd,
            requesterDate: '2026-12-01',
            partnerDate: '2026-12-02',
            label: 'Cabang Sendiri'
        );

        $foreignPair = $this->createPairWithSchedules(
            branch: $foreignBranch,
            approver: $hrd,
            requesterDate: '2026-12-03',
            partnerDate: '2026-12-04',
            label: 'Cabang Asing'
        );

        $ownRequest = $this->createSwapRequest(
            requester: $ownPair['requester'],
            partner: $ownPair['partner'],
            requesterDate: '2026-12-01',
            partnerDate: '2026-12-02'
        );

        $this->createSwapRequest(
            requester: $foreignPair['requester'],
            partner: $foreignPair['partner'],
            requesterDate: '2026-12-03',
            partnerDate: '2026-12-04'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->get(route('schedule-swap-requests.index'))
            ->assertOk()
            ->assertViewHas(
                'scheduleSwapRequests',
                static function (
                    LengthAwarePaginator $requests
                ) use ($ownRequest): bool {
                    return $requests->total() === 1
                        && $requests->count() === 1
                        && (int) $requests
                            ->first()
                            ?->id
                            === (int) $ownRequest->id;
                }
            )
            ->assertSeeText(
                $ownPair['requester']->employee_number
            )
            ->assertSeeText(
                $ownPair['partner']->employee_number
            )
            ->assertDontSeeText(
                $foreignPair['requester']->employee_number
            )
            ->assertDontSeeText(
                $foreignPair['partner']->employee_number
            );

        $this->get(route('schedule-swap-requests.create'))
            ->assertOk()
            ->assertViewHas(
                'employees',
                static function (
                    $employees
                ) use (
                    $ownPair,
                    $foreignPair
                ): bool {
                    $actualIds = $employees
                        ->pluck('id')
                        ->map(
                            static fn ($id): int => (int) $id
                        )
                        ->sort()
                        ->values()
                        ->all();

                    $expectedIds = [
                        (int) $ownPair['requester']->id,
                        (int) $ownPair['partner']->id,
                    ];

                    sort($expectedIds);

                    return $actualIds === $expectedIds
                        && ! $employees->contains(
                            'id',
                            $foreignPair['requester']->id
                        )
                        && ! $employees->contains(
                            'id',
                            $foreignPair['partner']->id
                        );
                }
            )
            ->assertSeeText(
                $ownPair['requester']->employee_number
            )
            ->assertSeeText(
                $ownPair['partner']->employee_number
            )
            ->assertDontSeeText(
                $foreignPair['requester']->employee_number
            )
            ->assertDontSeeText(
                $foreignPair['partner']->employee_number
            );
    }

    public function test_assigned_admin_can_store_and_open_own_branch_request(): void
    {
        $hrd = $this->createUser('hrd');
        $branch = $this->createBranch();

        $pair = $this->createPairWithSchedules(
            branch: $branch,
            approver: $hrd,
            requesterDate: '2026-12-05',
            partnerDate: '2026-12-06',
            label: 'Operasi Sendiri'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $branch
        );

        $response = $this->actingAs($admin)
            ->post(
                route('schedule-swap-requests.store'),
                [
                    'requester_employee_id' => $pair['requester']->id,
                    'partner_employee_id' => $pair['partner']->id,
                    'requester_date' => '2026-12-05',
                    'partner_date' => '2026-12-06',
                    'reason' => 'Permohonan cabang sendiri',
                ]
            );

        $scheduleSwapRequest =
            ScheduleSwapRequest::query()
                ->where(
                    'requester_employee_id',
                    $pair['requester']->id
                )
                ->where(
                    'partner_employee_id',
                    $pair['partner']->id
                )
                ->firstOrFail();

        $response->assertRedirect(
            route(
                'schedule-swap-requests.show',
                $scheduleSwapRequest
            )
        );

        $this->get(
            route(
                'schedule-swap-requests.show',
                $scheduleSwapRequest
            )
        )
            ->assertOk()
            ->assertSeeText(
                $pair['requester']->full_name
            )
            ->assertSeeText(
                $pair['partner']->full_name
            );

        $this->assertDatabaseHas(
            'schedule_swap_requests',
            [
                'id' => $scheduleSwapRequest->id,
                'requester_employee_id' => $pair['requester']->id,
                'partner_employee_id' => $pair['partner']->id,
                'status' => 'pending',
            ]
        );
    }

    public function test_assigned_admin_cannot_open_foreign_branch_request(): void
    {
        $hrd = $this->createUser('hrd');

        $ownBranch = $this->createBranch();
        $foreignBranch = $this->createBranch();

        $foreignPair = $this->createPairWithSchedules(
            branch: $foreignBranch,
            approver: $hrd,
            requesterDate: '2026-12-07',
            partnerDate: '2026-12-08',
            label: 'Detail Asing'
        );

        $foreignRequest = $this->createSwapRequest(
            requester: $foreignPair['requester'],
            partner: $foreignPair['partner'],
            requesterDate: '2026-12-07',
            partnerDate: '2026-12-08'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->get(
                route(
                    'schedule-swap-requests.show',
                    $foreignRequest
                )
            )
            ->assertForbidden();
    }

    public function test_assigned_admin_cannot_store_request_with_foreign_employee(): void
    {
        $hrd = $this->createUser('hrd');

        $ownBranch = $this->createBranch();
        $foreignBranch = $this->createBranch();

        $ownRequester = $this->createEmployee(
            $ownBranch,
            [
                'full_name' => 'Pengaju Cabang Admin',
            ]
        );

        $foreignPartner = $this->createEmployee(
            $foreignBranch,
            [
                'full_name' => 'Pasangan Cabang Asing',
            ]
        );

        $workSchedule = $this->createWorkSchedule();

        $this->createEmployeeSchedule(
            employee: $ownRequester,
            workSchedule: $workSchedule,
            approver: $hrd,
            scheduleDate: '2026-12-09'
        );

        $this->createEmployeeSchedule(
            employee: $foreignPartner,
            workSchedule: $workSchedule,
            approver: $hrd,
            scheduleDate: '2026-12-10'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->post(
                route('schedule-swap-requests.store'),
                [
                    'requester_employee_id' => $ownRequester->id,
                    'partner_employee_id' => $foreignPartner->id,
                    'requester_date' => '2026-12-09',
                    'partner_date' => '2026-12-10',
                    'reason' => 'Percobaan lintas cabang admin',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'schedule_swap_requests',
            [
                'requester_employee_id' => $ownRequester->id,
                'partner_employee_id' => $foreignPartner->id,
                'requester_date' => '2026-12-09',
                'partner_date' => '2026-12-10',
            ]
        );
    }

    public function test_unassigned_admin_is_forbidden_from_swap_pages_and_store(): void
    {
        $hrd = $this->createUser('hrd');
        $branch = $this->createBranch();

        $pair = $this->createPairWithSchedules(
            branch: $branch,
            approver: $hrd,
            requesterDate: '2026-12-11',
            partnerDate: '2026-12-12',
            label: 'Admin Tanpa Cabang'
        );

        $scheduleSwapRequest = $this->createSwapRequest(
            requester: $pair['requester'],
            partner: $pair['partner'],
            requesterDate: '2026-12-11',
            partnerDate: '2026-12-12'
        );

        $this->createEmployeeSchedule(
            employee: $pair['requester'],
            workSchedule: $pair['workSchedule'],
            approver: $hrd,
            scheduleDate: '2026-12-13'
        );

        $this->createEmployeeSchedule(
            employee: $pair['partner'],
            workSchedule: $pair['workSchedule'],
            approver: $hrd,
            scheduleDate: '2026-12-14'
        );

        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get(route('schedule-swap-requests.index'))
            ->assertForbidden();

        $this->get(route('schedule-swap-requests.create'))
            ->assertForbidden();

        $this->get(
            route(
                'schedule-swap-requests.show',
                $scheduleSwapRequest
            )
        )
            ->assertForbidden();

        $this->post(
            route('schedule-swap-requests.store'),
            [
                'requester_employee_id' => $pair['requester']->id,
                'partner_employee_id' => $pair['partner']->id,
                'requester_date' => '2026-12-13',
                'partner_date' => '2026-12-14',
                'reason' => 'Admin tanpa assignment cabang',
            ]
        )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'schedule_swap_requests',
            1
        );
    }

    public function test_admin_assigned_to_inactive_branch_is_forbidden_from_swap_pages_and_store(): void
    {
        $hrd = $this->createUser('hrd');

        $inactiveBranch = $this->createBranch([
            'status' => 'inactive',
        ]);

        $activeBranch = $this->createBranch();

        $pair = $this->createPairWithSchedules(
            branch: $activeBranch,
            approver: $hrd,
            requesterDate: '2026-12-15',
            partnerDate: '2026-12-16',
            label: 'Cabang Admin Nonaktif'
        );

        $scheduleSwapRequest = $this->createSwapRequest(
            requester: $pair['requester'],
            partner: $pair['partner'],
            requesterDate: '2026-12-15',
            partnerDate: '2026-12-16'
        );

        $this->createEmployeeSchedule(
            employee: $pair['requester'],
            workSchedule: $pair['workSchedule'],
            approver: $hrd,
            scheduleDate: '2026-12-17'
        );

        $this->createEmployeeSchedule(
            employee: $pair['partner'],
            workSchedule: $pair['workSchedule'],
            approver: $hrd,
            scheduleDate: '2026-12-18'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $inactiveBranch
        );

        $this->actingAs($admin)
            ->get(route('schedule-swap-requests.index'))
            ->assertForbidden();

        $this->get(route('schedule-swap-requests.create'))
            ->assertForbidden();

        $this->get(
            route(
                'schedule-swap-requests.show',
                $scheduleSwapRequest
            )
        )
            ->assertForbidden();

        $this->post(
            route('schedule-swap-requests.store'),
            [
                'requester_employee_id' => $pair['requester']->id,
                'partner_employee_id' => $pair['partner']->id,
                'requester_date' => '2026-12-17',
                'partner_date' => '2026-12-18',
                'reason' => 'Cabang admin sudah nonaktif',
            ]
        )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'schedule_swap_requests',
            1
        );
    }

    public function test_hrd_retains_cross_branch_visibility_and_access(): void
    {
        $hrd = $this->createUser('hrd');

        $firstBranch = $this->createBranch();
        $secondBranch = $this->createBranch();

        $firstPair = $this->createPairWithSchedules(
            branch: $firstBranch,
            approver: $hrd,
            requesterDate: '2026-12-19',
            partnerDate: '2026-12-20',
            label: 'HRD Cabang Pertama'
        );

        $secondPair = $this->createPairWithSchedules(
            branch: $secondBranch,
            approver: $hrd,
            requesterDate: '2026-12-21',
            partnerDate: '2026-12-22',
            label: 'HRD Cabang Kedua'
        );

        $firstRequest = $this->createSwapRequest(
            requester: $firstPair['requester'],
            partner: $firstPair['partner'],
            requesterDate: '2026-12-19',
            partnerDate: '2026-12-20'
        );

        $secondRequest = $this->createSwapRequest(
            requester: $secondPair['requester'],
            partner: $secondPair['partner'],
            requesterDate: '2026-12-21',
            partnerDate: '2026-12-22'
        );

        $this->actingAs($hrd)
            ->get(route('schedule-swap-requests.index'))
            ->assertOk()
            ->assertViewHas(
                'scheduleSwapRequests',
                static fn (
                    LengthAwarePaginator $requests
                ): bool => $requests->total() === 2
            )
            ->assertSeeText(
                $firstPair['requester']->employee_number
            )
            ->assertSeeText(
                $secondPair['requester']->employee_number
            );

        $this->get(route('schedule-swap-requests.create'))
            ->assertOk()
            ->assertViewHas(
                'employees',
                static fn (
                    $employees
                ): bool => $employees->count() === 4
            )
            ->assertSeeText(
                $firstPair['partner']->employee_number
            )
            ->assertSeeText(
                $secondPair['partner']->employee_number
            );

        $this->get(
            route(
                'schedule-swap-requests.show',
                $firstRequest
            )
        )
            ->assertOk();

        $this->get(
            route(
                'schedule-swap-requests.show',
                $secondRequest
            )
        )
            ->assertOk();
    }

    private function createUser(
        string $role,
        ?Branch $branch = null
    ): User {
        return User::factory()->create([
            'branch_id' => $branch?->id,
            'role' => $role,
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBranch(
        array $overrides = []
    ): Branch {
        $this->branchSequence++;

        return Branch::factory()->create(
            array_replace(
                [
                    'code' => sprintf(
                        'SWP-SCP-%03d',
                        $this->branchSequence
                    ),
                    'name' => sprintf(
                        'Cabang Scope Swap %03d',
                        $this->branchSequence
                    ),
                    'status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createEmployee(
        Branch $branch,
        array $overrides = []
    ): Employee {
        $this->employeeSequence++;

        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::query()->create(
            array_replace(
                [
                    'user_id' => $employeeUser->id,
                    'branch_id' => $branch->id,
                    'employee_number' => sprintf(
                        'EMP-SCOPE-SWAP-%03d',
                        $this->employeeSequence
                    ),
                    'full_name' => sprintf(
                        'Karyawan Scope Swap %03d',
                        $this->employeeSequence
                    ),
                    'position' => 'Karyawan',
                    'phone_number' => null,
                    'employment_status' => 'active',
                ],
                $overrides
            )
        );
    }

    private function createWorkSchedule(): WorkSchedule
    {
        $this->workScheduleSequence++;

        return WorkSchedule::query()->create([
            'name' => sprintf(
                'Pola Scope Swap %03d',
                $this->workScheduleSequence
            ),
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);
    }

    private function createEmployeeSchedule(
        Employee $employee,
        WorkSchedule $workSchedule,
        User $approver,
        string $scheduleDate
    ): EmployeeSchedule {
        return EmployeeSchedule::query()->create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => $scheduleDate,
            'schedule_status' => 'work',
            'approved_by' => $approver->id,
            'notes' => null,
        ]);
    }

    /**
     * @return array{
     *     requester: Employee,
     *     partner: Employee,
     *     workSchedule: WorkSchedule
     * }
     */
    private function createPairWithSchedules(
        Branch $branch,
        User $approver,
        string $requesterDate,
        string $partnerDate,
        string $label
    ): array {
        $requester = $this->createEmployee(
            $branch,
            [
                'full_name' => "{$label} Pengaju",
            ]
        );

        $partner = $this->createEmployee(
            $branch,
            [
                'full_name' => "{$label} Pasangan",
            ]
        );

        $workSchedule = $this->createWorkSchedule();

        $this->createEmployeeSchedule(
            employee: $requester,
            workSchedule: $workSchedule,
            approver: $approver,
            scheduleDate: $requesterDate
        );

        $this->createEmployeeSchedule(
            employee: $partner,
            workSchedule: $workSchedule,
            approver: $approver,
            scheduleDate: $partnerDate
        );

        return [
            'requester' => $requester,
            'partner' => $partner,
            'workSchedule' => $workSchedule,
        ];
    }

    private function createSwapRequest(
        Employee $requester,
        Employee $partner,
        string $requesterDate,
        string $partnerDate
    ): ScheduleSwapRequest {
        return ScheduleSwapRequest::query()->create([
            'requester_employee_id' => $requester->id,
            'partner_employee_id' => $partner->id,
            'requester_date' => $requesterDate,
            'partner_date' => $partnerDate,
            'reason' => 'Kontrak scope pertukaran jadwal',
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }
}
