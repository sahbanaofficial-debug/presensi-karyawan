<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WorkSchedule;
use App\Services\WeeklyRosterService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WeeklyAttendanceSessionAutomationCommandTest extends TestCase
{
    use RefreshDatabase;

    private WeeklyRosterService $rosterService;

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rosterService = $this->app->make(
            WeeklyRosterService::class
        );
    }

    public function test_command_creates_session_for_published_roster_on_target_date(): void
    {
        $hrd = $this->createHrd();
        $branch = $this->createBranch();

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule = $this->createWorkSchedule();

        $weeklySchedule = $this->createRoster(
            hrd: $hrd,
            branch: $branch,
            weekStartDate: '2026-08-03',
            items: [
                $this->workItem(
                    employee: $employee,
                    workSchedule: $workSchedule,
                    scheduleDate: '2026-08-03'
                ),
            ],
            publish: true
        );

        $this->artisan(
            'attendance-sessions:generate-automatic',
            [
                '--date' => '2026-08-03',
            ]
        )
            ->expectsOutputToContain(
                'Pembentukan sesi otomatis selesai.'
            )
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'attendance_sessions',
            [
                'branch_id' => $branch->id,

                'weekly_schedule_id' => $weeklySchedule->id,

                'attendance_type' => AttendanceSession::TYPE_AUTO,

                'session_source' => AttendanceSession::SOURCE_AUTOMATIC,

                'automation_key' => 'AUTO:'.$branch->id.':2026-08-03',

                'created_by' => null,
            ]
        );

        $session = AttendanceSession::query()
            ->where(
                'automation_key',
                'AUTO:'.$branch->id.':2026-08-03'
            )
            ->firstOrFail();

        $this->assertSame(
            '2026-08-03',
            $session->session_date
                ->format('Y-m-d')
        );
    }

    public function test_command_is_idempotent_when_executed_repeatedly(): void
    {
        $hrd = $this->createHrd();
        $branch = $this->createBranch();

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule = $this->createWorkSchedule();

        $this->createRoster(
            hrd: $hrd,
            branch: $branch,
            weekStartDate: '2026-08-10',
            items: [
                $this->workItem(
                    employee: $employee,
                    workSchedule: $workSchedule,
                    scheduleDate: '2026-08-10'
                ),
            ],
            publish: true
        );

        $arguments = [
            '--date' => '2026-08-10',
        ];

        $this->artisan(
            'attendance-sessions:generate-automatic',
            $arguments
        )->assertSuccessful();

        $this->artisan(
            'attendance-sessions:generate-automatic',
            $arguments
        )->assertSuccessful();

        $this->assertDatabaseCount(
            'attendance_sessions',
            1
        );
    }

    public function test_command_ignores_draft_future_and_nonwork_rosters(): void
    {
        $hrd = $this->createHrd();

        $draftBranch = $this->createBranch();
        $draftEmployee = $this->createEmployee(
            $draftBranch
        );

        $draftWorkSchedule =
            $this->createWorkSchedule();

        $this->createRoster(
            hrd: $hrd,
            branch: $draftBranch,
            weekStartDate: '2026-08-17',
            items: [
                $this->workItem(
                    employee: $draftEmployee,
                    workSchedule: $draftWorkSchedule,
                    scheduleDate: '2026-08-17'
                ),
            ],
            publish: false
        );

        $offBranch = $this->createBranch();
        $offEmployee = $this->createEmployee(
            $offBranch
        );

        $this->createRoster(
            hrd: $hrd,
            branch: $offBranch,
            weekStartDate: '2026-08-17',
            items: [
                [
                    'employee_id' => $offEmployee->id,
                    'work_schedule_id' => null,
                    'schedule_date' => '2026-08-17',
                    'schedule_status' => 'off',
                    'notes' => 'Libur',
                ],
            ],
            publish: true
        );

        $futureBranch = $this->createBranch();
        $futureEmployee = $this->createEmployee(
            $futureBranch
        );

        $futureWorkSchedule =
            $this->createWorkSchedule();

        $this->createRoster(
            hrd: $hrd,
            branch: $futureBranch,
            weekStartDate: '2026-08-24',
            items: [
                $this->workItem(
                    employee: $futureEmployee,
                    workSchedule: $futureWorkSchedule,
                    scheduleDate: '2026-08-24'
                ),
            ],
            publish: true
        );

        $this->artisan(
            'attendance-sessions:generate-automatic',
            [
                '--date' => '2026-08-17',
            ]
        )
            ->expectsOutputToContain(
                'Tidak ada roster terpublikasi dengan jadwal kerja pada 2026-08-17.'
            )
            ->assertSuccessful();

        $this->assertDatabaseCount(
            'attendance_sessions',
            0
        );
    }

    public function test_command_rejects_invalid_date_option(): void
    {
        $this->artisan(
            'attendance-sessions:generate-automatic',
            [
                '--date' => '2026-02-31',
            ]
        )
            ->expectsOutputToContain(
                'Opsi --date harus berupa tanggal valid dengan format YYYY-MM-DD.'
            )
            ->assertFailed();

        $this->assertDatabaseCount(
            'attendance_sessions',
            0
        );
    }

    public function test_scheduler_registers_command_every_five_minutes_with_mutex(): void
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
                'attendance-sessions:generate-automatic'
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

    private function createHrd(): User
    {
        return User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);
    }

    private function createBranch(): Branch
    {
        return Branch::factory()->create([
            'status' => 'active',
            'latitude' => 1.4748,
            'longitude' => 124.8421,
            'geofence_radius' => 50.0,
            'maximum_accuracy' => 25.0,
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
                'EMP-COMMAND-%03d',
                $this->employeeSequence
            ),

            'full_name' => sprintf(
                'Karyawan Command %03d',
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
                        'Pola Command %03d',
                        $this->workScheduleSequence
                    ),

                    'check_in_time' => '08:00:00',
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
    private function workItem(
        Employee $employee,
        WorkSchedule $workSchedule,
        string $scheduleDate
    ): array {
        return [
            'employee_id' => $employee->id,

            'work_schedule_id' => $workSchedule->id,

            'schedule_date' => $scheduleDate,

            'schedule_status' => 'work',

            'notes' => null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function createRoster(
        User $hrd,
        Branch $branch,
        string $weekStartDate,
        array $items,
        bool $publish
    ): WeeklySchedule {
        $weeklySchedule = $this->rosterService
            ->createDraft(
                [
                    'branch_id' => $branch->id,

                    'week_start_date' => $weekStartDate,

                    'items' => $items,
                ],
                $hrd
            );

        if (! $publish) {
            return $weeklySchedule;
        }

        return $this->rosterService->publish(
            $weeklySchedule,
            $hrd
        );
    }
}
