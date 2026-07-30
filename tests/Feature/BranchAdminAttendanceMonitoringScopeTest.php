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

final class BranchAdminAttendanceMonitoringScopeTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_assigned_admin_monitoring_is_scoped_to_own_branch_and_ignores_foreign_filters(): void
    {
        $ownBranch = $this->createBranch();
        $otherBranch = $this->createBranch();

        [, $ownEmployee] = $this->createEmployee(
            $ownBranch
        );

        [, $otherEmployee] = $this->createEmployee(
            $otherBranch
        );

        $hrd = $this->createUser('hrd');

        $ownSchedule = $this->createEmployeeSchedule(
            employee: $ownEmployee,
            approver: $hrd,
            scheduleDate: '2026-07-01'
        );

        $otherSchedule = $this->createEmployeeSchedule(
            employee: $otherEmployee,
            approver: $hrd,
            scheduleDate: '2026-07-01'
        );

        $ownAttendance = $this->createAttendance(
            employee: $ownEmployee,
            employeeSchedule: $ownSchedule,
            creator: $hrd,
            attendanceType: 'check_in',
            attendanceDate: '2026-07-01',
            attendanceTime: '08:45:00',
            punctualityStatus: 'on_time'
        );

        $this->createAttendance(
            employee: $otherEmployee,
            employeeSchedule: $otherSchedule,
            creator: $hrd,
            attendanceType: 'check_in',
            attendanceDate: '2026-07-01',
            attendanceTime: '08:55:00',
            punctualityStatus: 'late'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->get(
                route(
                    'attendance-monitoring.index',
                    [
                        'branch_id' => $otherBranch->id,
                        'employee_id' => $otherEmployee->id,
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedBranchId',
                $ownBranch->id
            )
            ->assertViewHas(
                'selectedEmployeeId',
                null
            )
            ->assertViewHas(
                'summary',
                static fn (array $summary): bool => $summary === [
                    'total' => 1,
                    'employees' => 1,
                    'check_in' => 1,
                    'check_out' => 0,
                    'on_time' => 1,
                    'late' => 0,
                ]
            )
            ->assertViewHas(
                'attendances',
                static function (
                    LengthAwarePaginator $attendances
                ) use (
                    $ownAttendance
                ): bool {
                    return $attendances->total() === 1
                        && $attendances->count() === 1
                        && (int) $attendances
                            ->first()
                            ?->id
                            === (int) $ownAttendance->id;
                }
            )
            ->assertViewHas(
                'branches',
                static function (
                    $branches
                ) use (
                    $ownBranch,
                    $otherBranch
                ): bool {
                    return $branches->count() === 1
                        && (int) $branches
                            ->first()
                            ?->id
                            === (int) $ownBranch->id
                        && ! $branches->contains(
                            'id',
                            $otherBranch->id
                        );
                }
            )
            ->assertViewHas(
                'employees',
                static function (
                    $employees
                ) use (
                    $ownEmployee,
                    $otherEmployee
                ): bool {
                    return $employees->count() === 1
                        && (int) $employees
                            ->first()
                            ?->id
                            === (int) $ownEmployee->id
                        && ! $employees->contains(
                            'id',
                            $otherEmployee->id
                        );
                }
            )
            ->assertSeeText($ownEmployee->full_name)
            ->assertDontSeeText($otherEmployee->full_name)
            ->assertSeeText($ownBranch->name)
            ->assertDontSeeText($otherBranch->name);
    }

    public function test_unassigned_admin_is_forbidden_from_attendance_monitoring(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get(
                route('attendance-monitoring.index')
            )
            ->assertForbidden();
    }

    public function test_admin_assigned_to_inactive_branch_is_forbidden_from_attendance_monitoring(): void
    {
        $inactiveBranch = $this->createBranch([
            'status' => 'inactive',
        ]);

        $admin = $this->createUser(
            role: 'admin',
            branch: $inactiveBranch
        );

        $this->actingAs($admin)
            ->get(
                route('attendance-monitoring.index')
            )
            ->assertForbidden();
    }

    public function test_hrd_retains_cross_branch_monitoring_and_filtering(): void
    {
        $firstBranch = $this->createBranch();
        $secondBranch = $this->createBranch();

        [, $firstEmployee] = $this->createEmployee(
            $firstBranch
        );

        [, $secondEmployee] = $this->createEmployee(
            $secondBranch
        );

        $hrd = $this->createUser('hrd');

        $firstSchedule = $this->createEmployeeSchedule(
            employee: $firstEmployee,
            approver: $hrd,
            scheduleDate: '2026-07-02'
        );

        $secondSchedule = $this->createEmployeeSchedule(
            employee: $secondEmployee,
            approver: $hrd,
            scheduleDate: '2026-07-02'
        );

        $this->createAttendance(
            employee: $firstEmployee,
            employeeSchedule: $firstSchedule,
            creator: $hrd,
            attendanceType: 'check_in',
            attendanceDate: '2026-07-02',
            attendanceTime: '08:45:00',
            punctualityStatus: 'on_time'
        );

        $secondAttendance = $this->createAttendance(
            employee: $secondEmployee,
            employeeSchedule: $secondSchedule,
            creator: $hrd,
            attendanceType: 'check_in',
            attendanceDate: '2026-07-02',
            attendanceTime: '08:55:00',
            punctualityStatus: 'late'
        );

        $this->actingAs($hrd)
            ->get(
                route('attendance-monitoring.index')
            )
            ->assertOk()
            ->assertViewHas(
                'summary',
                static fn (array $summary): bool => $summary['total'] === 2
                    && $summary['employees'] === 2
            )
            ->assertViewHas(
                'branches',
                static fn ($branches): bool => $branches->count() === 2
            )
            ->assertViewHas(
                'employees',
                static fn ($employees): bool => $employees->count() === 2
            );

        $this->get(
            route(
                'attendance-monitoring.index',
                [
                    'branch_id' => $secondBranch->id,
                ]
            )
        )
            ->assertOk()
            ->assertViewHas(
                'selectedBranchId',
                $secondBranch->id
            )
            ->assertViewHas(
                'attendances',
                static function (
                    LengthAwarePaginator $attendances
                ) use (
                    $secondAttendance
                ): bool {
                    return $attendances->total() === 1
                        && (int) $attendances
                            ->first()
                            ?->id
                            === (int) $secondAttendance->id;
                }
            )
            ->assertViewHas(
                'employees',
                static function (
                    $employees
                ) use (
                    $secondEmployee
                ): bool {
                    return $employees->count() === 1
                        && (int) $employees
                            ->first()
                            ?->id
                            === (int) $secondEmployee->id;
                }
            );
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
        $this->sequence++;

        return Branch::factory()->create(
            array_replace(
                [
                    'code' => sprintf(
                        'BR-AMS-%03d',
                        $this->sequence
                    ),
                    'name' => sprintf(
                        'Cabang Scope Monitoring %03d',
                        $this->sequence
                    ),
                    'address' => 'Jalan Scope Monitoring',
                    'latitude' => 3.595196,
                    'longitude' => 98.672226,
                    'geofence_radius' => 30.0,
                    'maximum_accuracy' => 20.0,
                    'status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @return array{0: User, 1: Employee}
     */
    private function createEmployee(
        Branch $branch
    ): array {
        $this->sequence++;

        $user = $this->createUser('employee');

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => sprintf(
                'EMP-AMS-%03d',
                $this->sequence
            ),
            'full_name' => sprintf(
                'Karyawan Scope Monitoring %03d',
                $this->sequence
            ),
            'position' => 'Karyawan',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        return [
            $user,
            $employee,
        ];
    }

    private function createWorkSchedule(): WorkSchedule
    {
        $this->sequence++;

        return WorkSchedule::query()->create([
            'name' => sprintf(
                'Pola Scope Monitoring %03d',
                $this->sequence
            ),
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);
    }

    private function createEmployeeSchedule(
        Employee $employee,
        User $approver,
        string $scheduleDate
    ): EmployeeSchedule {
        $workSchedule = $this->createWorkSchedule();

        return EmployeeSchedule::query()->create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => $scheduleDate,
            'schedule_status' => 'work',
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
        $branch = $employee->branch()
            ->firstOrFail();

        $attendanceSession = $this->createAttendanceSession(
            branch: $branch,
            creator: $creator,
            attendanceType: $attendanceType,
            attendanceDate: $attendanceDate
        );

        return Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_session_id' => $attendanceSession->id,
            'employee_schedule_id' => $employeeSchedule->id,
            'branch_id' => $branch->id,
            'attendance_type' => $attendanceType,
            'attendance_date' => $attendanceDate,
            'attendance_time' => "{$attendanceDate} {$attendanceTime}",
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'accuracy' => 5.0,
            'distance' => 4.5,
            'geofence_radius' => $branch->geofence_radius,
            'attendance_status' => 'present',
            'punctuality_status' => $punctualityStatus,
            'validation_status' => 'accepted',
        ]);
    }

    private function createAttendanceSession(
        Branch $branch,
        User $creator,
        string $attendanceType,
        string $attendanceDate
    ): AttendanceSession {
        $startTime = $attendanceType === 'check_in'
            ? '08:15:00'
            : '16:30:00';

        $endTime = $attendanceType === 'check_in'
            ? '10:00:00'
            : '18:00:00';

        return AttendanceSession::query()->create([
            'branch_id' => $branch->id,
            'attendance_type' => $attendanceType,
            'session_date' => $attendanceDate,
            'start_time' => "{$attendanceDate} {$startTime}",
            'end_time' => "{$attendanceDate} {$endTime}",
            'encrypted_secret' => 'JBSWY3DPEHPK3PXP',
            'status' => 'active',
            'created_by' => $creator->id,
            'closed_at' => null,
        ]);
    }
}
