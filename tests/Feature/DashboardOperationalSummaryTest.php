<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

final class DashboardOperationalSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_hrd_dashboard_defaults_to_current_week_and_all_branches(): void
    {
        $this->freezeDashboardTime();

        $hrd = $this->createHrd();

        $firstBranch = $this->createBranch(
            'DASH-01',
            'Cabang Dashboard Satu'
        );

        $secondBranch = $this->createBranch(
            'DASH-02',
            'Cabang Dashboard Dua'
        );

        $firstEmployee = $this->createEmployee(
            $firstBranch,
            'DASH-EMP-001',
            'Karyawan Dashboard Satu'
        );

        $secondEmployee = $this->createEmployee(
            $secondBranch,
            'DASH-EMP-002',
            'Karyawan Dashboard Dua'
        );

        $this->createAttendance(
            $firstEmployee,
            $firstBranch,
            '2026-08-03',
            '08:45:00',
            'check_in'
        );

        $this->createAttendance(
            $secondEmployee,
            $secondBranch,
            '2026-08-04',
            '17:00:00',
            'check_out'
        );

        $this->createAttendance(
            $firstEmployee,
            $firstBranch,
            '2026-07-31',
            '08:45:00',
            'check_in'
        );

        $this->actingAs($hrd)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHas('dashboardPeriodKey', 'week')
            ->assertViewHas(
                'selectedDashboardBranchId',
                null
            )
            ->assertViewHas(
                'dashboardStats',
                static fn (?array $stats): bool =>
                    $stats !== null
                    && $stats['active_branches'] === 2
                    && $stats['active_employees'] === 2
                    && $stats['active_terminals'] === 0
                    && $stats['period_attendances'] === 2
            )
            ->assertViewHas(
                'attendanceSummary',
                static fn (?array $summary): bool =>
                    $summary !== null
                    && $summary['on_time'] === 1
                    && $summary['late'] === 0
                    && $summary['check_out'] === 1
                    && $summary['total'] === 2
            )
            ->assertSee('Filter ringkasan')
            ->assertSee('Minggu ini')
            ->assertSee('Semua cabang')
            ->assertSee('Komposisi presensi minggu ini')
            ->assertSee('Presensi minggu ini')
            ->assertSee('Masuk tepat waktu')
            ->assertSee('Masuk terlambat')
            ->assertSee('Pulang')
            ->assertSee('Presensi per cabang')
            ->assertSee('Presensi terbaru')
            ->assertSee('03 Agustus 2026')
            ->assertSee('09 Agustus 2026')
            ->assertSee('Seluruh cabang');
    }

    public function test_hrd_can_switch_dashboard_to_today(): void
    {
        $this->freezeDashboardTime();

        $hrd = $this->createHrd();
        $branch = $this->createBranch(
            'DASH-TODAY',
            'Cabang Hari Ini'
        );

        $employee = $this->createEmployee(
            $branch,
            'DASH-TODAY-EMP',
            'Karyawan Hari Ini'
        );

        $this->createAttendance(
            $employee,
            $branch,
            '2026-08-03',
            '08:45:00',
            'check_in'
        );

        $this->createAttendance(
            $employee,
            $branch,
            '2026-08-05',
            '08:45:00',
            'check_in'
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'dashboard',
                    [
                        'period' => 'today',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas('dashboardPeriodKey', 'today')
            ->assertViewHas('dashboardPeriodLabel', 'Hari ini')
            ->assertViewHas(
                'dashboardStats',
                static fn (?array $stats): bool =>
                    $stats !== null
                    && $stats['period_attendances'] === 1
            )
            ->assertViewHas(
                'recentAttendances',
                static fn ($attendances): bool =>
                    $attendances->count() === 1
                    && $attendances
                        ->first()
                        ?->attendance_date
                        ?->toDateString() === '2026-08-05'
            )
            ->assertSee('Komposisi presensi hari ini')
            ->assertSee('Presensi hari ini');
    }

    public function test_hrd_branch_filter_scopes_all_dashboard_data(): void
    {
        $this->freezeDashboardTime();

        $hrd = $this->createHrd();

        $selectedBranch = $this->createBranch(
            'DASH-FILTER-01',
            'Cabang Terpilih'
        );

        $otherBranch = $this->createBranch(
            'DASH-FILTER-02',
            'Cabang Lain'
        );

        $selectedEmployee = $this->createEmployee(
            $selectedBranch,
            'DASH-FILTER-EMP-01',
            'Karyawan Terpilih'
        );

        $otherEmployee = $this->createEmployee(
            $otherBranch,
            'DASH-FILTER-EMP-02',
            'Karyawan Cabang Lain'
        );

        $selectedAttendance = $this->createAttendance(
            $selectedEmployee,
            $selectedBranch,
            '2026-08-03',
            '08:45:00',
            'check_in'
        );

        $this->createAttendance(
            $otherEmployee,
            $otherBranch,
            '2026-08-03',
            '08:50:00',
            'check_in'
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'dashboard',
                    [
                        'period' => 'week',
                        'branch_id' => $selectedBranch->id,
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedDashboardBranchId',
                $selectedBranch->id
            )
            ->assertViewHas(
                'dashboardStats',
                static fn (?array $stats): bool =>
                    $stats !== null
                    && $stats['active_branches'] === 1
                    && $stats['active_employees'] === 1
                    && $stats['period_attendances'] === 1
            )
            ->assertViewHas(
                'branchAttendance',
                static fn ($branches): bool =>
                    $branches->count() === 1
                    && $branches->first()?->is(
                        $selectedBranch
                    )
            )
            ->assertViewHas(
                'recentAttendances',
                static fn ($attendances): bool =>
                    $attendances->count() === 1
                    && $attendances->first()?->is(
                        $selectedAttendance
                    )
            )
            ->assertSee('DASH-FILTER-01 — Cabang Terpilih');
    }

    public function test_admin_dashboard_ignores_foreign_branch_filter(): void
    {
        $this->freezeDashboardTime();

        $assignedBranch = $this->createBranch(
            'DASH-ADMIN-01',
            'Cabang Admin Dashboard'
        );

        $otherBranch = $this->createBranch(
            'DASH-ADMIN-02',
            'Cabang Lain Dashboard'
        );

        $admin = User::factory()->create([
            'branch_id' => $assignedBranch->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $assignedEmployee = $this->createEmployee(
            $assignedBranch,
            'DASH-ADMIN-EMP-001',
            'Karyawan Cabang Admin'
        );

        $otherEmployee = $this->createEmployee(
            $otherBranch,
            'DASH-ADMIN-EMP-002',
            'Karyawan Cabang Lain'
        );

        $assignedAttendance = $this->createAttendance(
            $assignedEmployee,
            $assignedBranch,
            '2026-08-03',
            '08:45:00',
            'check_in'
        );

        $this->createAttendance(
            $otherEmployee,
            $otherBranch,
            '2026-08-03',
            '08:50:00',
            'check_in'
        );

        $this->actingAs($admin)
            ->get(
                route(
                    'dashboard',
                    [
                        'period' => 'week',
                        'branch_id' => $otherBranch->id,
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedDashboardBranchId',
                $assignedBranch->id
            )
            ->assertViewHas(
                'dashboardStats',
                static fn (?array $stats): bool =>
                    $stats !== null
                    && $stats['active_branches'] === 1
                    && $stats['active_employees'] === 1
                    && $stats['period_attendances'] === 1
            )
            ->assertViewHas(
                'recentAttendances',
                static fn ($attendances): bool =>
                    $attendances->count() === 1
                    && $attendances->first()?->is(
                        $assignedAttendance
                    )
            )
            ->assertSee('Cabang Admin Dashboard')
            ->assertDontSee('Cabang Lain Dashboard');
    }

    private function freezeDashboardTime(): void
    {
        Date::setTestNow(
            CarbonImmutable::parse(
                '2026-08-05 10:00:00',
                'Asia/Jakarta'
            )
        );
    }

    private function createHrd(): User
    {
        return User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);
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

    private function createEmployee(
        Branch $branch,
        string $employeeNumber,
        string $fullName
    ): Employee {
        $user = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => $employeeNumber,
            'full_name' => $fullName,
            'position' => 'Karyawan',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);
    }

    private function createAttendance(
        Employee $employee,
        Branch $branch,
        string $attendanceDate,
        string $attendanceTime,
        string $attendanceType
    ): Attendance {
        $employeeSchedule =
            EmployeeSchedule::query()->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'schedule_date' => $attendanceDate,
                ],
                [
                    'work_schedule_id' => null,
                    'schedule_status' => 'work',
                    'schedule_source' =>
                        EmployeeSchedule::SOURCE_MANUAL,
                    'approved_by' => null,
                    'notes' =>
                        'Fixture pengujian dashboard.',
                ]
            );

        return Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_session_id' => null,
            'employee_schedule_id' =>
                $employeeSchedule->id,
            'branch_id' => $branch->id,
            'attendance_type' => $attendanceType,
            'attendance_date' => $attendanceDate,
            'attendance_time' => sprintf(
                '%s %s',
                $attendanceDate,
                $attendanceTime
            ),
            'latitude' => null,
            'longitude' => null,
            'accuracy' => null,
            'distance' => null,
            'geofence_radius' => null,
            'attendance_status' => 'present',
            'punctuality_status' =>
                $attendanceType === 'check_out'
                    ? 'not_applicable'
                    : 'on_time',
            'late_minutes' => null,
            'validation_status' => 'accepted',
            'record_source' =>
                Attendance::RECORD_SOURCE_MANUAL,
        ]);
    }
}
