<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WorkSchedule;
use App\Services\TotpService;
use App\Services\WeeklyAttendanceSessionAutomationService;
use App\Services\WeeklyRosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class WeeklyAttendanceSessionAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    private WeeklyRosterService $rosterService;

    private WeeklyAttendanceSessionAutomationService $automationService;

    private TotpService $totpService;

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rosterService = $this->app->make(
            WeeklyRosterService::class
        );

        $this->automationService = $this->app->make(
            WeeklyAttendanceSessionAutomationService::class
        );

        $this->totpService = $this->app->make(
            TotpService::class
        );
    }

    public function test_published_roster_creates_one_auto_session_from_snapshots(): void
    {
        $hrd = $this->createHrd();
        $branch = $this->createBranch();

        $firstEmployee = $this->createEmployee(
            $branch
        );

        $secondEmployee = $this->createEmployee(
            $branch
        );

        $morningSchedule = $this->createWorkSchedule([
            'name' => 'Pola Otomatis Pagi',
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'check_out_limit_minutes' => 60,
        ]);

        $lateSchedule = $this->createWorkSchedule([
            'name' => 'Pola Otomatis Siang',
            'check_in_time' => '09:00:00',
            'check_out_time' => '21:30:00',
            'check_in_open_minutes' => 15,
            'check_out_limit_minutes' => 60,
        ]);

        $weeklySchedule = $this->createRoster(
            hrd: $hrd,
            branch: $branch,
            items: [
                $this->workItem(
                    employee: $firstEmployee,
                    workSchedule: $morningSchedule,
                    scheduleDate: '2026-08-03'
                ),
                $this->workItem(
                    employee: $secondEmployee,
                    workSchedule: $lateSchedule,
                    scheduleDate: '2026-08-03'
                ),
            ],
            publish: true
        );

        $morningSchedule->update([
            'check_in_time' => '10:00:00',
            'check_out_time' => '20:00:00',
        ]);

        $lateSchedule->update([
            'check_in_time' => '11:00:00',
            'check_out_time' => '22:00:00',
        ]);

        $sessions = $this->automationService
            ->createForPublishedRoster(
                $weeklySchedule
            );

        $this->assertCount(1, $sessions);

        $session = $sessions->firstOrFail();

        $this->assertSame(
            AttendanceSession::TYPE_AUTO,
            $session->attendance_type
        );

        $this->assertSame(
            AttendanceSession::SOURCE_AUTOMATIC,
            $session->session_source
        );

        $this->assertTrue(
            $session->isAutoType()
        );

        $this->assertTrue(
            $session->isAutomatic()
        );

        $this->assertSame(
            'AUTO:'.$branch->id.':2026-08-03',
            $session->automation_key
        );

        $this->assertSame(
            '2026-08-03 07:30:00',
            $session->start_time
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-08-03 22:30:00',
            $session->end_time
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            $weeklySchedule->id,
            $session->weekly_schedule_id
        );

        $this->assertNull(
            $session->created_by
        );

        $this->assertSame(
            'active',
            $session->status
        );

        $rawSecret = DB::table(
            'attendance_sessions'
        )
            ->where(
                'id',
                $session->id
            )
            ->value(
                'encrypted_secret'
            );

        $this->assertNotSame(
            $session->encrypted_secret,
            $rawSecret
        );
    }

    public function test_automation_is_idempotent_for_same_branch_and_date(): void
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
            items: [
                $this->workItem(
                    employee: $employee,
                    workSchedule: $workSchedule,
                    scheduleDate: '2026-08-10'
                ),
            ],
            publish: true
        );

        $firstRun = $this->automationService
            ->createForPublishedRoster(
                $weeklySchedule
            );

        $secondRun = $this->automationService
            ->createForPublishedRoster(
                $weeklySchedule
            );

        $this->assertSame(
            $firstRun->firstOrFail()->id,
            $secondRun->firstOrFail()->id
        );

        $this->assertDatabaseCount(
            'attendance_sessions',
            1
        );

        $this->assertDatabaseHas(
            'attendance_sessions',
            [
                'branch_id' => $branch->id,
                'weekly_schedule_id' => $weeklySchedule->id,

                'attendance_type' => 'auto',
                'session_source' => 'automatic',

                'automation_key' => 'AUTO:'.$branch->id.':2026-08-10',

                'created_by' => null,
            ]
        );
    }

    public function test_nonwork_items_do_not_create_session(): void
    {
        $hrd = $this->createHrd();
        $branch = $this->createBranch();
        $employee = $this->createEmployee(
            $branch
        );

        $weeklySchedule = $this->createRoster(
            hrd: $hrd,
            branch: $branch,
            items: [
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => null,
                    'schedule_date' => '2026-08-17',
                    'schedule_status' => 'off',
                    'notes' => 'Libur mingguan',
                ],
            ],
            publish: true
        );

        $sessions = $this->automationService
            ->createForPublishedRoster(
                $weeklySchedule
            );

        $this->assertCount(0, $sessions);

        $this->assertDatabaseCount(
            'attendance_sessions',
            0
        );
    }

    public function test_draft_roster_is_rejected(): void
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
            items: [
                $this->workItem(
                    employee: $employee,
                    workSchedule: $workSchedule,
                    scheduleDate: '2026-08-24'
                ),
            ],
            publish: false
        );

        try {
            $this->automationService
                ->createForPublishedRoster(
                    $weeklySchedule
                );

            $this->fail(
                'Roster draft seharusnya ditolak.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'weekly_schedule',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'attendance_sessions',
            0
        );
    }

    public function test_active_manual_session_causes_atomic_conflict(): void
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
            items: [
                $this->workItem(
                    employee: $employee,
                    workSchedule: $workSchedule,
                    scheduleDate: '2026-08-31'
                ),
            ],
            publish: true
        );

        AttendanceSession::query()->create([
            'branch_id' => $branch->id,
            'weekly_schedule_id' => null,
            'attendance_type' => 'check_in',
            'session_source' => 'manual',
            'automation_key' => null,
            'session_date' => '2026-08-31',
            'start_time' => '2026-08-31 07:30:00',
            'end_time' => '2026-08-31 09:30:00',

            'encrypted_secret' => $this->totpService
                ->generateSecret(),

            'status' => 'active',
            'created_by' => $hrd->id,
            'closed_at' => null,
        ]);

        try {
            $this->automationService
                ->createForPublishedRoster(
                    $weeklySchedule
                );

            $this->fail(
                'Konflik sesi aktif seharusnya ditolak.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'attendance_session',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'attendance_sessions',
            1
        );

        $this->assertDatabaseMissing(
            'attendance_sessions',
            [
                'weekly_schedule_id' => $weeklySchedule->id,

                'session_source' => 'automatic',
            ]
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
                'EMP-AUTO-%03d',
                $this->employeeSequence
            ),

            'full_name' => sprintf(
                'Karyawan Otomatis %03d',
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
                        'Pola Otomatis %03d',
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
        array $items,
        bool $publish
    ): WeeklySchedule {
        $weekStartDate = collect($items)
            ->pluck('schedule_date')
            ->sort()
            ->first();

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
