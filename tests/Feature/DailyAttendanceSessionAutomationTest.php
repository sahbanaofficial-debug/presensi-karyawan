<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\DailyAttendanceSessionAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DailyAttendanceSessionAutomationTest extends TestCase
{
    use RefreshDatabase;

    private DailyAttendanceSessionAutomationService $service;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(
            DailyAttendanceSessionAutomationService::class
        );
    }

    public function test_daily_schedules_create_one_branch_session_from_earliest_and_latest_windows(): void
    {
        $branch = $this->createBranch();

        $morning = $this->createWorkSchedule([
            'name' => 'Pola Harian Pagi',
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'check_out_limit_minutes' => 60,
        ]);

        $late = $this->createWorkSchedule([
            'name' => 'Pola Harian Malam',
            'check_in_time' => '09:00:00',
            'check_out_time' => '21:30:00',
            'check_in_open_minutes' => 15,
            'check_out_limit_minutes' => 60,
        ]);

        $this->createDailySchedule(
            $this->createEmployee($branch),
            $morning,
            '2026-12-01'
        );

        $this->createDailySchedule(
            $this->createEmployee($branch),
            $late,
            '2026-12-01'
        );

        $summary = $this->service->generate(
            '2026-12-01'
        );

        $this->assertSame(
            [
                'branches' => 1,
                'created' => 1,
                'reused' => 0,
                'failed' => 0,
                'failures' => [],
            ],
            $summary
        );

        $session = AttendanceSession::query()
            ->firstOrFail();

        $this->assertSame(
            AttendanceSession::TYPE_AUTO,
            $session->attendance_type
        );

        $this->assertSame(
            AttendanceSession::SOURCE_AUTOMATIC,
            $session->session_source
        );

        $this->assertSame(
            'AUTO:'.$branch->id.':2026-12-01',
            $session->automation_key
        );

        $this->assertSame(
            '2026-12-01 07:30:00',
            $session->start_time
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-12-01 22:30:00',
            $session->end_time
                ->format('Y-m-d H:i:s')
        );

        $this->assertNull(
            $session->weekly_schedule_id
        );

        $this->assertNull(
            $session->created_by
        );

        $rawSecret = DB::table(
            'attendance_sessions'
        )
            ->where('id', $session->id)
            ->value('encrypted_secret');

        $this->assertNotSame(
            $session->encrypted_secret,
            $rawSecret
        );
    }

    public function test_service_is_idempotent_for_same_branch_and_date(): void
    {
        $branch = $this->createBranch();
        $workSchedule = $this->createWorkSchedule();

        $this->createDailySchedule(
            $this->createEmployee($branch),
            $workSchedule,
            '2026-12-02'
        );

        $first = $this->service->generate(
            '2026-12-02'
        );

        $second = $this->service->generate(
            '2026-12-02'
        );

        $this->assertSame(1, $first['created']);
        $this->assertSame(0, $first['reused']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['reused']);

        $this->assertDatabaseCount(
            'attendance_sessions',
            1
        );
    }

    public function test_active_manual_session_blocks_automatic_session_atomically(): void
    {
        $branch = $this->createBranch();
        $workSchedule = $this->createWorkSchedule();

        $this->createDailySchedule(
            $this->createEmployee($branch),
            $workSchedule,
            '2026-12-03'
        );

        $hrd = User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);

        AttendanceSession::query()->create([
            'branch_id' => $branch->id,
            'weekly_schedule_id' => null,
            'attendance_type' => 'check_in',
            'session_source' => 'manual',
            'automation_key' => null,
            'session_date' => '2026-12-03',
            'start_time' => '2026-12-03 08:15:00',
            'end_time' => '2026-12-03 09:30:00',
            'encrypted_secret' => 'MANUALSECRET12345',
            'status' => 'active',
            'created_by' => $hrd->id,
            'closed_at' => null,
        ]);

        $summary = $this->service->generate(
            '2026-12-03'
        );

        $this->assertSame(1, $summary['failed']);
        $this->assertSame(0, $summary['created']);

        $this->assertDatabaseCount(
            'attendance_sessions',
            1
        );

        $this->assertDatabaseMissing(
            'attendance_sessions',
            [
                'automation_key' => 'AUTO:'.$branch->id.':2026-12-03',
            ]
        );
    }

    public function test_command_builds_daily_schedule_then_automatic_session_from_branch_default(): void
    {
        $workSchedule = $this->createWorkSchedule([
            'name' => 'Jadwal Default Integrasi',
            'check_in_time' => '08:45:00',
            'check_out_time' => '21:30:00',
        ]);

        $branch = $this->createBranch(
            $workSchedule
        );

        $employee = $this->createEmployee(
            $branch
        );

        $this->artisan(
            'attendance-sessions:generate-automatic',
            [
                '--date' => '2026-12-04',
            ]
        )
            ->expectsOutputToContain(
                'Pembentukan sesi otomatis selesai.'
            )
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'employee_schedules',
            [
                'employee_id' => $employee->id,

                'schedule_source' => EmployeeSchedule::SOURCE_BRANCH_DEFAULT,

                'approved_by' => null,
            ]
        );

        $session = AttendanceSession::query()
            ->where(
                'automation_key',
                'AUTO:'.$branch->id.':2026-12-04'
            )
            ->firstOrFail();

        $this->assertSame(
            '2026-12-04 08:15:00',
            $session->start_time
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-12-04 22:30:00',
            $session->end_time
                ->format('Y-m-d H:i:s')
        );

        $this->artisan(
            'attendance-sessions:generate-automatic',
            [
                '--date' => '2026-12-04',
            ]
        )->assertSuccessful();

        $this->assertDatabaseCount(
            'employee_schedules',
            1
        );

        $this->assertDatabaseCount(
            'attendance_sessions',
            1
        );
    }

    private function createBranch(
        ?WorkSchedule $defaultSchedule = null
    ): Branch {
        $this->sequence++;

        return Branch::query()->create([
            'default_work_schedule_id' => $defaultSchedule?->id,

            'code' => sprintf(
                'DAILY-%03d',
                $this->sequence
            ),

            'name' => sprintf(
                'Cabang Harian %03d',
                $this->sequence
            ),

            'address' => 'Alamat cabang pengujian',
            'latitude' => 3.53775250,
            'longitude' => 98.68468940,
            'geofence_radius' => 30.00,
            'maximum_accuracy' => 25.00,
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createWorkSchedule(
        array $overrides = []
    ): WorkSchedule {
        $this->sequence++;

        return WorkSchedule::query()->create(
            array_replace(
                [
                    'name' => sprintf(
                        'Pola Harian %03d',
                        $this->sequence
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

    private function createEmployee(
        Branch $branch
    ): Employee {
        $this->sequence++;

        $user = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,

            'employee_number' => sprintf(
                'EMP-DAILY-%03d',
                $this->sequence
            ),

            'full_name' => sprintf(
                'Karyawan Harian %03d',
                $this->sequence
            ),

            'position' => 'Karyawan',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);
    }

    private function createDailySchedule(
        Employee $employee,
        WorkSchedule $workSchedule,
        string $date
    ): EmployeeSchedule {
        return EmployeeSchedule::query()->create([
            'weekly_schedule_item_id' => null,
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => $date,
            'schedule_status' => 'work',

            'schedule_source' => EmployeeSchedule::SOURCE_BRANCH_DEFAULT,

            'work_schedule_name_snapshot' => $workSchedule->name,

            'check_in_time_snapshot' => $workSchedule->check_in_time,

            'check_out_time_snapshot' => $workSchedule->check_out_time,

            'check_in_open_minutes_snapshot' => $workSchedule
                ->check_in_open_minutes,

            'check_in_limit_minutes_snapshot' => $workSchedule
                ->check_in_limit_minutes,

            'late_tolerance_minutes_snapshot' => $workSchedule
                ->late_tolerance_minutes,

            'check_out_limit_minutes_snapshot' => $workSchedule
                ->check_out_limit_minutes,

            'approved_by' => null,
            'notes' => null,
        ]);
    }
}
