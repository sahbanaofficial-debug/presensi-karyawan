<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

final class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_guest_is_redirected_and_employee_is_forbidden(): void
    {
        $this->get(route('attendance-reports.index'))
            ->assertRedirect(route('login'));

        $branch = $this->createBranch();
        [$employeeUser] = $this->createEmployee($branch);

        $this->actingAs($employeeUser)
            ->get(route('attendance-reports.index'))
            ->assertForbidden();
    }

    public function test_hrd_sees_combined_report_and_correct_summary(): void
    {
        $branch = $this->createBranch();
        [, $firstEmployee] = $this->createEmployee($branch);
        [, $secondEmployee] = $this->createEmployee($branch);
        $hrd = $this->createUser('hrd');

        $firstSchedule = $this->createEmployeeSchedule(
            $firstEmployee,
            $hrd,
            '2026-08-13'
        );
        $secondSchedule = $this->createEmployeeSchedule(
            $secondEmployee,
            $hrd,
            '2026-08-13'
        );

        $this->createAttendance(
            $firstEmployee,
            $firstSchedule,
            $hrd,
            'check_in',
            '2026-08-13',
            '08:43:21',
            'on_time'
        );
        $this->createAttendance(
            $firstEmployee,
            $firstSchedule,
            $hrd,
            'check_out',
            '2026-08-13',
            '17:01:45',
            'not_applicable'
        );
        $this->createAttendance(
            $secondEmployee,
            $secondSchedule,
            $hrd,
            'check_in',
            '2026-08-13',
            '08:51:13',
            'late'
        );

        $response = $this->actingAs($hrd)->get(route(
            'attendance-reports.index',
            [
                'date_from' => '2026-08-13',
                'date_to' => '2026-08-13',
            ]
        ));

        $response
            ->assertOk()
            ->assertViewIs('attendance-reports.index')
            ->assertSee('Laporan Presensi')
            ->assertSee('Rekap Presensi Karyawan')
            ->assertSee($firstEmployee->full_name)
            ->assertSee($secondEmployee->full_name);

        $this->assertSame(
            [
                'scheduled' => 2,
                'present' => 2,
                'on_time' => 1,
                'late' => 1,
                'complete' => 1,
                'not_recorded' => 0,
            ],
            $response->viewData('summary')
        );

        $reports = $response->viewData('reports');
        $this->assertInstanceOf(LengthAwarePaginator::class, $reports);
        $this->assertSame(2, $reports->total());
        $this->assertSame(2, $reports->count());
    }

    public function test_report_filters_not_recorded_schedules(): void
    {
        $branch = $this->createBranch();
        [, $presentEmployee] = $this->createEmployee($branch);
        [, $missingEmployee] = $this->createEmployee($branch);
        $hrd = $this->createUser('hrd');

        $presentSchedule = $this->createEmployeeSchedule(
            $presentEmployee,
            $hrd,
            '2026-08-14'
        );
        $this->createEmployeeSchedule(
            $missingEmployee,
            $hrd,
            '2026-08-14'
        );
        $this->createAttendance(
            $presentEmployee,
            $presentSchedule,
            $hrd,
            'check_in',
            '2026-08-14',
            '08:45:00',
            'on_time'
        );

        $response = $this->actingAs($hrd)
            ->get(route('attendance-reports.index', [
                'date_from' => '2026-08-14',
                'date_to' => '2026-08-14',
                'report_status' => 'not_recorded',
            ]))
            ->assertOk()
            ->assertViewHas('selectedReportStatus', 'not_recorded')
            ->assertSee($missingEmployee->full_name);

        $reports = $response->viewData('reports');
        $this->assertInstanceOf(LengthAwarePaginator::class, $reports);
        $this->assertSame(1, $reports->total());
        $this->assertSame(
            (int) $missingEmployee->id,
            (int) ($reports->items()[0]?->employee_id ?? 0)
        );
        $this->assertNotSame(
            (int) $presentEmployee->id,
            (int) ($reports->items()[0]?->employee_id ?? 0)
        );
    }

    public function test_admin_only_sees_their_assigned_branch(): void
    {
        $adminBranch = $this->createBranch();
        $otherBranch = $this->createBranch();
        [, $adminEmployee] = $this->createEmployee($adminBranch);
        [, $otherEmployee] = $this->createEmployee($otherBranch);
        $hrd = $this->createUser('hrd');
        $admin = $this->createUser('admin');
        $admin->update(['branch_id' => $adminBranch->id]);

        $this->createEmployeeSchedule(
            $adminEmployee,
            $hrd,
            '2026-08-15'
        );
        $this->createEmployeeSchedule(
            $otherEmployee,
            $hrd,
            '2026-08-15'
        );

        $response = $this->actingAs($admin)
            ->get(route('attendance-reports.index', [
                'date_from' => '2026-08-15',
                'date_to' => '2026-08-15',
                'branch_id' => $otherBranch->id,
            ]))
            ->assertOk()
            ->assertViewHas('selectedBranchId', $adminBranch->id)
            ->assertSee($adminEmployee->full_name)
            ->assertDontSee($otherEmployee->full_name);

        $reports = $response->viewData('reports');
        $this->assertInstanceOf(LengthAwarePaginator::class, $reports);
        $this->assertSame(1, $reports->total());
        $this->assertSame(
            (int) $adminEmployee->id,
            (int) ($reports->items()[0]?->employee_id ?? 0)
        );
    }

    private function createUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function createBranch(): Branch
    {
        $this->sequence++;

        return Branch::factory()->create([
            'code' => sprintf('BR-REP-%03d', $this->sequence),
            'name' => sprintf('Cabang Laporan %03d', $this->sequence),
            'maximum_accuracy' => 25.0,
            'status' => 'active',
        ]);
    }

    /**
     * @return array{0: User, 1: Employee}
     */
    private function createEmployee(Branch $branch): array
    {
        $this->sequence++;
        $user = $this->createUser('employee');
        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => sprintf('EMP-REP-%03d', $this->sequence),
            'full_name' => sprintf(
                'Karyawan Laporan %03d',
                $this->sequence
            ),
            'position' => 'Karyawan',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        return [$user, $employee];
    }

    private function createEmployeeSchedule(
        Employee $employee,
        User $approver,
        string $scheduleDate
    ): EmployeeSchedule {
        $this->sequence++;
        $workSchedule = WorkSchedule::query()->create([
            'name' => sprintf('Pola Laporan %03d', $this->sequence),
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'check_in_limit_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);

        return EmployeeSchedule::query()->create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => $scheduleDate,
            'schedule_status' => 'work',
            'schedule_source' => 'manual',
            'approved_by' => $approver->id,
            'notes' => null,
        ]);
    }

    private function createAttendance(
        Employee $employee,
        EmployeeSchedule $employeeSchedule,
        User $creator,
        string $attendanceType,
        string $attendanceDate,
        string $attendanceTime,
        string $punctualityStatus
    ): Attendance {
        $branch = $employee->branch()->firstOrFail();
        $session = AttendanceSession::query()->create([
            'branch_id' => $branch->id,
            'attendance_type' => $attendanceType,
            'session_date' => $attendanceDate,
            'start_time' => "{$attendanceDate} 08:15:00",
            'end_time' => "{$attendanceDate} 18:00:00",
            'encrypted_secret' => 'JBSWY3DPEHPK3PXP',
            'status' => 'active',
            'created_by' => $creator->id,
        ]);

        return Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_session_id' => $session->id,
            'employee_schedule_id' => $employeeSchedule->id,
            'branch_id' => $branch->id,
            'attendance_type' => $attendanceType,
            'attendance_date' => $attendanceDate,
            'attendance_time' => "{$attendanceDate} {$attendanceTime}",
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'accuracy' => 9.07,
            'distance' => 9.64,
            'geofence_radius' => $branch->geofence_radius,
            'attendance_status' => 'present',
            'punctuality_status' => $punctualityStatus,
            'validation_status' => 'accepted',
            'record_source' => 'scanner',
        ]);
    }
}
