<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EmployeeScheduleListUxTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    public function test_index_orders_schedules_chronologically_and_renders_compact_list(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'code' => 'CB-UX',
            'name' => 'Cabang UX',
        ]);

        $employee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-UX-001',
                'full_name' => 'Karyawan UX',
                'position' => 'Kasir',
            ]
        );

        $workSchedule = $this->createWorkSchedule([
            'name' => 'Pola Jadwal UX',
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
        ]);

        foreach (
            [
                '2026-08-09',
                '2026-08-03',
                '2026-08-06',
            ]
            as $scheduleDate
        ) {
            $this->createEmployeeSchedule(
                $employee,
                $workSchedule,
                $hrd,
                [
                    'schedule_date' => $scheduleDate,
                ]
            );
        }

        $response = $this->actingAs($hrd)
            ->get(route('employee-schedules.index'));

        $response
            ->assertOk()
            ->assertViewHas(
                'employeeSchedules',
                static function (
                    $employeeSchedules
                ): bool {
                    return $employeeSchedules
                        ->getCollection()
                        ->pluck('schedule_date')
                        ->map(
                            static fn ($date): string =>
                                $date->toDateString()
                        )
                        ->values()
                        ->all()
                        === [
                            '2026-08-03',
                            '2026-08-06',
                            '2026-08-09',
                        ];
                }
            )
            ->assertSee('Urutan kronologis')
            ->assertSee('Jadwal Kerja')
            ->assertSee('Pola Jadwal UX')
            ->assertSee('08:45–17:00 WIB')
            ->assertSeeInOrder([
                'Senin',
                '03 Agt 2026',
                'Kamis',
                '06 Agt 2026',
                'Minggu',
                '09 Agt 2026',
            ]);
    }

    public function test_leave_schedule_can_be_filtered_and_is_labeled_as_cuti(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();

        $workEmployee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-WORK-UX',
                'full_name' => 'Karyawan Kerja UX',
            ]
        );

        $leaveEmployee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-LEAVE-UX',
                'full_name' => 'Karyawan Cuti UX',
            ]
        );

        $workSchedule = $this->createWorkSchedule();

        $this->createEmployeeSchedule(
            $workEmployee,
            $workSchedule,
            $hrd,
            [
                'schedule_date' => '2026-08-03',
                'schedule_status' => 'work',
            ]
        );

        $leaveSchedule = $this->createEmployeeSchedule(
            $leaveEmployee,
            null,
            $hrd,
            [
                'schedule_date' => '2026-08-03',
                'schedule_status' => 'leave',
            ]
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'employee-schedules.index',
                    [
                        'schedule_status' => 'leave',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas('selectedStatus', 'leave')
            ->assertViewHas(
                'employeeSchedules',
                static function (
                    $employeeSchedules
                ) use ($leaveSchedule): bool {
                    return $employeeSchedules->count() === 1
                        && $employeeSchedules
                            ->first()
                            ?->is($leaveSchedule);
                }
            )
            ->assertSee('EMP-LEAVE-UX')
            ->assertSee('Karyawan Cuti UX')
            ->assertSee('Cuti')
            ->assertDontSee('EMP-WORK-UX')
            ->assertDontSee('Karyawan Kerja UX');
    }

    public function test_schedule_list_prefers_historical_snapshot_over_changed_master_pattern(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $employee = $this->createEmployee($branch);

        $workSchedule = $this->createWorkSchedule([
            'name' => 'Pola Master Berubah',
            'check_in_time' => '09:00:00',
            'check_out_time' => '18:00:00',
        ]);

        $this->createEmployeeSchedule(
            $employee,
            $workSchedule,
            $hrd,
            [
                'schedule_date' => '2026-08-04',
                'schedule_source' =>
                    EmployeeSchedule::SOURCE_BRANCH_DEFAULT,
                'work_schedule_name_snapshot' =>
                    'Pola Snapshot Historis',
                'check_in_time_snapshot' => '08:45:00',
                'check_out_time_snapshot' => '17:00:00',
                'check_in_open_minutes_snapshot' => 30,
                'check_in_limit_minutes_snapshot' => 30,
                'late_tolerance_minutes_snapshot' => 5,
                'check_out_limit_minutes_snapshot' => 60,
            ]
        );

        $this->actingAs($hrd)
            ->get(route('employee-schedules.index'))
            ->assertOk()
            ->assertSee('Pola Snapshot Historis')
            ->assertSee('08:45–17:00 WIB')
            ->assertDontSee('Pola Master Berubah')
            ->assertDontSee('09:00–18:00 WIB');
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
                        'EMP-UX-%03d',
                        $this->employeeSequence
                    ),
                    'full_name' => sprintf(
                        'Karyawan UX %03d',
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
                        'Pola Jadwal UX %03d',
                        $this->workScheduleSequence
                    ),
                    'check_in_time' => '08:45:00',
                    'check_out_time' => '17:00:00',
                    'check_in_open_minutes' => 30,
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
    private function createEmployeeSchedule(
        Employee $employee,
        ?WorkSchedule $workSchedule,
        User $approver,
        array $overrides = []
    ): EmployeeSchedule {
        return EmployeeSchedule::query()->create(
            array_replace(
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $workSchedule?->id,
                    'schedule_date' => '2026-08-20',
                    'schedule_status' =>
                        $workSchedule === null
                            ? 'off'
                            : 'work',
                    'schedule_source' =>
                        EmployeeSchedule::SOURCE_MANUAL,
                    'approved_by' => $approver->id,
                    'notes' => null,
                ],
                $overrides
            )
        );
    }
}
