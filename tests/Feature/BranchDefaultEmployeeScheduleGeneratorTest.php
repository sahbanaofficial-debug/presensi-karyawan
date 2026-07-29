<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\BranchDefaultEmployeeScheduleGeneratorService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BranchDefaultEmployeeScheduleGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private BranchDefaultEmployeeScheduleGeneratorService $generator;

    private int $branchSequence = 0;

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = app(
            BranchDefaultEmployeeScheduleGeneratorService::class
        );
    }

    public function test_generator_creates_schedule_for_active_employees_with_immutable_snapshot(): void
    {
        $workSchedule = $this->createWorkSchedule([
            'name' => 'Jadwal Default Generator',
            'check_in_time' => '08:45:00',
            'check_out_time' => '21:30:00',
        ]);

        $branch = $this->createBranch(
            $workSchedule
        );

        $firstEmployee = $this->createEmployee(
            $branch
        );

        $secondEmployee = $this->createEmployee(
            $branch
        );

        $inactiveEmployee = $this->createEmployee(
            $branch,
            [
                'employment_status' => 'inactive',
            ]
        );

        $summary = $this->generator->generate(
            '2026-11-10'
        );

        $this->assertSame(
            [
                'branches' => 1,
                'employees' => 2,
                'created' => 2,
                'existing' => 0,
                'skipped_branches' => 0,
            ],
            $summary
        );

        $schedules = EmployeeSchedule::query()
            ->whereIn(
                'employee_id',
                [
                    $firstEmployee->id,
                    $secondEmployee->id,
                ]
            )
            ->orderBy('employee_id')
            ->get();

        $this->assertCount(2, $schedules);

        foreach ($schedules as $schedule) {
            $this->assertSame(
                EmployeeSchedule::SOURCE_BRANCH_DEFAULT,
                $schedule->schedule_source
            );

            $this->assertSame(
                $workSchedule->id,
                $schedule->work_schedule_id
            );

            $this->assertNull(
                $schedule->weekly_schedule_item_id
            );

            $this->assertNull(
                $schedule->approved_by
            );

            $this->assertSame(
                'Jadwal Default Generator',
                $schedule
                    ->work_schedule_name_snapshot
            );

            $this->assertSame(
                '08:45:00',
                $schedule
                    ->check_in_time_snapshot
            );

            $this->assertSame(
                '21:30:00',
                $schedule
                    ->check_out_time_snapshot
            );

            $this->assertSame(
                30,
                $schedule
                    ->check_in_open_minutes_snapshot
            );

            $this->assertSame(
                30,
                $schedule
                    ->check_in_limit_minutes_snapshot
            );

            $this->assertSame(
                5,
                $schedule
                    ->late_tolerance_minutes_snapshot
            );

            $this->assertSame(
                60,
                $schedule
                    ->check_out_limit_minutes_snapshot
            );

            $this->assertTrue(
                $schedule
                    ->hasValidScheduleConfiguration()
            );
        }

        $this->assertDatabaseMissing(
            'employee_schedules',
            [
                'employee_id' => $inactiveEmployee->id,
            ]
        );
    }

    public function test_generator_is_idempotent_and_preserves_existing_manual_schedule(): void
    {
        $defaultSchedule =
            $this->createWorkSchedule([
                'name' => 'Jadwal Default',
            ]);

        $manualSchedule =
            $this->createWorkSchedule([
                'name' => 'Jadwal Manual',
                'check_in_time' => '10:00:00',
                'check_out_time' => '19:00:00',
            ]);

        $branch = $this->createBranch(
            $defaultSchedule
        );

        $manualEmployee = $this->createEmployee(
            $branch
        );

        $automaticEmployee =
            $this->createEmployee(
                $branch
            );

        $hrd = User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $existingManual =
            EmployeeSchedule::query()->create([
                'employee_id' => $manualEmployee->id,

                'work_schedule_id' => $manualSchedule->id,

                'schedule_date' => '2026-11-11',
                'schedule_status' => 'work',

                'schedule_source' => EmployeeSchedule::SOURCE_MANUAL,

                'approved_by' => $hrd->id,
                'notes' => 'Jadwal manual dipertahankan.',
            ]);

        $firstSummary = $this->generator->generate(
            '2026-11-11'
        );

        $secondSummary = $this->generator->generate(
            '2026-11-11'
        );

        $this->assertSame(1, $firstSummary['created']);
        $this->assertSame(1, $firstSummary['existing']);
        $this->assertSame(0, $secondSummary['created']);
        $this->assertSame(2, $secondSummary['existing']);

        $this->assertDatabaseCount(
            'employee_schedules',
            2
        );

        $manualAfter = $existingManual->fresh();

        $this->assertSame(
            EmployeeSchedule::SOURCE_MANUAL,
            $manualAfter->schedule_source
        );

        $this->assertSame(
            $manualSchedule->id,
            $manualAfter->work_schedule_id
        );

        $this->assertSame(
            $hrd->id,
            $manualAfter->approved_by
        );

        $this->assertDatabaseHas(
            'employee_schedules',
            [
                'employee_id' => $automaticEmployee->id,

                'work_schedule_id' => $defaultSchedule->id,

                'schedule_source' => EmployeeSchedule::SOURCE_BRANCH_DEFAULT,

                'approved_by' => null,
            ]
        );
    }

    public function test_generator_ignores_ineligible_branches_schedules_and_employees(): void
    {
        $activeSchedule =
            $this->createWorkSchedule();

        $inactiveSchedule =
            $this->createWorkSchedule([
                'status' => 'inactive',
            ]);

        $inactiveBranch = $this->createBranch(
            $activeSchedule,
            [
                'status' => 'inactive',
            ]
        );

        $this->createEmployee(
            $inactiveBranch
        );

        $branchWithoutDefault =
            $this->createBranch();

        $this->createEmployee(
            $branchWithoutDefault
        );

        $inactiveScheduleBranch =
            $this->createBranch(
                $inactiveSchedule
            );

        $this->createEmployee(
            $inactiveScheduleBranch
        );

        $validBranch = $this->createBranch(
            $activeSchedule
        );

        $this->createEmployee(
            $validBranch,
            [
                'employment_status' => 'inactive',
            ]
        );

        $summary = $this->generator->generate(
            '2026-11-12'
        );

        $this->assertSame(
            [
                'branches' => 1,
                'employees' => 0,
                'created' => 0,
                'existing' => 0,
                'skipped_branches' => 1,
            ],
            $summary
        );

        $this->assertDatabaseCount(
            'employee_schedules',
            0
        );
    }

    public function test_command_generates_schedule_for_requested_date_and_rejects_invalid_date(): void
    {
        $workSchedule =
            $this->createWorkSchedule();

        $branch = $this->createBranch(
            $workSchedule
        );

        $employee = $this->createEmployee(
            $branch
        );

        $this->artisan(
            'employee-schedules:generate-branch-default',
            [
                '--date' => '2026-11-13',
            ]
        )
            ->expectsOutputToContain(
                'Pembentukan jadwal harian default cabang selesai.'
            )
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'employee_schedules',
            [
                'employee_id' => $employee->id,
                'schedule_source' => EmployeeSchedule::SOURCE_BRANCH_DEFAULT,
            ]
        );

        $this->artisan(
            'employee-schedules:generate-branch-default',
            [
                '--date' => '2026-02-31',
            ]
        )
            ->expectsOutputToContain(
                'Opsi --date harus berupa tanggal valid dengan format YYYY-MM-DD.'
            )
            ->assertFailed();

        $this->assertDatabaseCount(
            'employee_schedules',
            1
        );
    }

    public function test_scheduler_runs_generator_every_five_minutes_with_mutex(): void
    {
        $schedule = $this->app->make(
            Schedule::class
        );

        $event = collect(
            $schedule->events()
        )->first(
            static fn (
                object $scheduledEvent
            ): bool => str_contains(
                (string) $scheduledEvent->command,
                'employee-schedules:generate-branch-default'
            )
        );

        $this->assertNotNull($event);

        $this->assertSame(
            '*/5 * * * *',
            $event->expression
        );

        $this->assertTrue(
            $event->withoutOverlapping
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
                        'Pola Generator %03d',
                        $this->workScheduleSequence
                    ),
                    'check_in_time' => '08:45:00',
                    'check_out_time' => '17:00:00',
                    'check_in_open_minutes' => 30,
                    'check_in_limit_minutes' => 30,
                    'late_tolerance_minutes' => 5,
                    'check_out_limit_minutes' => 60,
                    'status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBranch(
        ?WorkSchedule $defaultWorkSchedule = null,
        array $overrides = []
    ): Branch {
        $this->branchSequence++;

        return Branch::query()->create(
            array_replace(
                [
                    'default_work_schedule_id' => $defaultWorkSchedule?->id,

                    'code' => sprintf(
                        'GEN-%03d',
                        $this->branchSequence
                    ),

                    'name' => sprintf(
                        'Cabang Generator %03d',
                        $this->branchSequence
                    ),

                    'address' => 'Alamat pengujian generator',
                    'latitude' => 3.53775250,
                    'longitude' => 98.68468940,
                    'geofence_radius' => 30.00,
                    'maximum_accuracy' => 25.00,
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
                        'EMP-GEN-%03d',
                        $this->employeeSequence
                    ),

                    'full_name' => sprintf(
                        'Karyawan Generator %03d',
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
}
