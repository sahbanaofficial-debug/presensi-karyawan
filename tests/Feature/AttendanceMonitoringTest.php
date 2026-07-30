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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

final class AttendanceMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_guest_is_redirected_to_login_from_attendance_monitoring(): void
    {
        $this->get(
            route('attendance-monitoring.index')
        )->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_attendance_monitoring(): void
    {
        $branch = $this->createBranch();

        [$user] = $this->createEmployee(
            $branch
        );

        $this->actingAs($user)
            ->get(
                route('attendance-monitoring.index')
            )
            ->assertForbidden();
    }

    public function test_hrd_can_access_attendance_monitoring(): void
    {
        $hrd = $this->createUser('hrd');

        $this->actingAs($hrd)
            ->get(
                route('attendance-monitoring.index')
            )
            ->assertOk()
            ->assertViewIs(
                'attendance-monitoring.index'
            )
            ->assertSee('Monitoring Presensi')
            ->assertSee('Filter Monitoring')
            ->assertSee('Daftar Transaksi Presensi');
    }

    public function test_admin_can_access_attendance_monitoring(): void
    {
        $branch = $this->createBranch();

        $admin = $this->createUser('admin');

        $admin->update([
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($admin)
            ->get(
                route('attendance-monitoring.index')
            )
            ->assertOk()
            ->assertViewIs(
                'attendance-monitoring.index'
            )
            ->assertSee('Monitoring Presensi');
    }

    public function test_monitoring_displays_all_attendances_and_correct_summary(): void
    {
        $branch = $this->createBranch();

        [$firstUser, $firstEmployee] =
            $this->createEmployee($branch);

        [$secondUser, $secondEmployee] =
            $this->createEmployee($branch);

        $hrd = $this->createUser('hrd');

        $firstSchedule =
            $this->createEmployeeSchedule(
                employee: $firstEmployee,
                approver: $hrd,
                scheduleDate: '2026-03-01'
            );

        $secondSchedule =
            $this->createEmployeeSchedule(
                employee: $secondEmployee,
                approver: $hrd,
                scheduleDate: '2026-03-01'
            );

        $this->createAttendance(
            employee: $firstEmployee,
            employeeSchedule: $firstSchedule,
            creator: $hrd,
            attendanceType: 'check_in',
            attendanceDate: '2026-03-01',
            attendanceTime: '08:45:00',
            punctualityStatus: 'on_time'
        );

        $this->createAttendance(
            employee: $firstEmployee,
            employeeSchedule: $firstSchedule,
            creator: $hrd,
            attendanceType: 'check_out',
            attendanceDate: '2026-03-01',
            attendanceTime: '17:00:00',
            punctualityStatus: 'not_applicable'
        );

        $this->createAttendance(
            employee: $secondEmployee,
            employeeSchedule: $secondSchedule,
            creator: $hrd,
            attendanceType: 'check_in',
            attendanceDate: '2026-03-01',
            attendanceTime: '08:52:00',
            punctualityStatus: 'late'
        );

        $response = $this->actingAs($hrd)
            ->get(
                route('attendance-monitoring.index')
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'summary',
                static fn (array $summary): bool => $summary === [
                    'total' => 3,
                    'employees' => 2,
                    'check_in' => 2,
                    'check_out' => 1,
                    'on_time' => 1,
                    'late' => 1,
                ]
            )
            ->assertViewHas(
                'attendances',
                static function (
                    LengthAwarePaginator $attendances
                ) use (
                    $firstEmployee,
                    $secondEmployee
                ): bool {
                    $employeeIds = collect(
                        $attendances->items()
                    )
                        ->pluck('employee_id')
                        ->map(
                            static fn (mixed $id): int => (int) $id
                        )
                        ->unique()
                        ->sort()
                        ->values();

                    return $attendances->total() === 3
                        && $employeeIds->all() === collect([
                            (int) $firstEmployee->id,
                            (int) $secondEmployee->id,
                        ])->sort()->values()->all();
                }
            )
            ->assertSee($firstEmployee->full_name)
            ->assertSee($secondEmployee->full_name);

        $this->assertNotSame(
            $firstUser->id,
            $secondUser->id
        );
    }

    public function test_monitoring_supports_combined_filters(): void
    {
        $firstBranch = $this->createBranch();
        $secondBranch = $this->createBranch();

        [, $firstEmployee] =
            $this->createEmployee($firstBranch);

        [, $secondEmployee] =
            $this->createEmployee($firstBranch);

        [, $thirdEmployee] =
            $this->createEmployee($secondBranch);

        $hrd = $this->createUser('hrd');

        $firstSchedule =
            $this->createEmployeeSchedule(
                employee: $firstEmployee,
                approver: $hrd,
                scheduleDate: '2026-03-02'
            );

        $secondSchedule =
            $this->createEmployeeSchedule(
                employee: $secondEmployee,
                approver: $hrd,
                scheduleDate: '2026-03-02'
            );

        $thirdSchedule =
            $this->createEmployeeSchedule(
                employee: $thirdEmployee,
                approver: $hrd,
                scheduleDate: '2026-03-02'
            );

        $targetAttendance = $this->createAttendance(
            employee: $firstEmployee,
            employeeSchedule: $firstSchedule,
            creator: $hrd,
            attendanceType: 'check_in',
            attendanceDate: '2026-03-02',
            attendanceTime: '08:52:00',
            punctualityStatus: 'late'
        );

        $this->createAttendance(
            employee: $firstEmployee,
            employeeSchedule: $firstSchedule,
            creator: $hrd,
            attendanceType: 'check_out',
            attendanceDate: '2026-03-02',
            attendanceTime: '17:00:00',
            punctualityStatus: 'not_applicable'
        );

        $this->createAttendance(
            employee: $secondEmployee,
            employeeSchedule: $secondSchedule,
            creator: $hrd,
            attendanceType: 'check_in',
            attendanceDate: '2026-03-02',
            attendanceTime: '08:45:00',
            punctualityStatus: 'on_time'
        );

        $this->createAttendance(
            employee: $thirdEmployee,
            employeeSchedule: $thirdSchedule,
            creator: $hrd,
            attendanceType: 'check_in',
            attendanceDate: '2026-03-02',
            attendanceTime: '08:53:00',
            punctualityStatus: 'late'
        );

        $response = $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-monitoring.index',
                    [
                        'attendance_date' => '2026-03-02',

                        'branch_id' => $firstBranch->id,

                        'employee_id' => $firstEmployee->id,

                        'attendance_type' => 'check_in',

                        'punctuality_status' => 'late',
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'selectedAttendanceDate',
                '2026-03-02'
            )
            ->assertViewHas(
                'selectedBranchId',
                $firstBranch->id
            )
            ->assertViewHas(
                'selectedEmployeeId',
                $firstEmployee->id
            )
            ->assertViewHas(
                'selectedAttendanceType',
                'check_in'
            )
            ->assertViewHas(
                'selectedPunctualityStatus',
                'late'
            )
            ->assertViewHas(
                'summary',
                static fn (array $summary): bool => $summary === [
                    'total' => 1,
                    'employees' => 1,
                    'check_in' => 1,
                    'check_out' => 0,
                    'on_time' => 0,
                    'late' => 1,
                ]
            )
            ->assertViewHas(
                'attendances',
                static function (
                    LengthAwarePaginator $attendances
                ) use (
                    $targetAttendance
                ): bool {
                    $items = collect(
                        $attendances->items()
                    );

                    return $attendances->total() === 1
                        && $items->count() === 1
                        && (int) $items
                            ->first()
                            ?->id
                            === (int) $targetAttendance->id;
                }
            );
    }

    public function test_branch_filter_limits_employee_options(): void
    {
        $firstBranch = $this->createBranch();
        $secondBranch = $this->createBranch();

        [, $firstEmployee] =
            $this->createEmployee($firstBranch);

        [, $secondEmployee] =
            $this->createEmployee($secondBranch);

        $hrd = $this->createUser('hrd');

        $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-monitoring.index',
                    [
                        'branch_id' => $firstBranch->id,
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedBranchId',
                $firstBranch->id
            )
            ->assertViewHas(
                'employees',
                static function (
                    $employees
                ) use (
                    $firstBranch,
                    $firstEmployee,
                    $secondEmployee
                ): bool {
                    return $employees->count() === 1
                        && (int) $employees
                            ->first()
                            ?->id
                            === (int) $firstEmployee->id
                        && $employees->every(
                            static fn (
                                Employee $employee
                            ): bool => (int) $employee->branch_id
                                === (int) $firstBranch->id
                        )
                        && ! $employees->contains(
                            'id',
                            $secondEmployee->id
                        );
                }
            );
    }

    public function test_invalid_monitoring_filters_are_ignored(): void
    {
        $branch = $this->createBranch();

        [, $employee] =
            $this->createEmployee($branch);

        $hrd = $this->createUser('hrd');

        $firstSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $hrd,
                scheduleDate: '2026-03-03'
            );

        $secondSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $hrd,
                scheduleDate: '2026-03-04'
            );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $firstSchedule,
            creator: $hrd,
            attendanceType: 'check_in',
            attendanceDate: '2026-03-03',
            attendanceTime: '08:45:00',
            punctualityStatus: 'on_time'
        );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $secondSchedule,
            creator: $hrd,
            attendanceType: 'check_out',
            attendanceDate: '2026-03-04',
            attendanceTime: '17:00:00',
            punctualityStatus: 'not_applicable'
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-monitoring.index',
                    [
                        'attendance_date' => '2026-02-31',

                        'branch_id' => 'invalid',

                        'employee_id' => '-10',

                        'attendance_type' => 'unknown',

                        'punctuality_status' => 'unknown',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedAttendanceDate',
                null
            )
            ->assertViewHas(
                'selectedBranchId',
                null
            )
            ->assertViewHas(
                'selectedEmployeeId',
                null
            )
            ->assertViewHas(
                'selectedAttendanceType',
                ''
            )
            ->assertViewHas(
                'selectedPunctualityStatus',
                ''
            )
            ->assertViewHas(
                'summary',
                static fn (array $summary): bool => $summary['total'] === 2
            )
            ->assertViewHas(
                'attendances',
                static fn (
                    LengthAwarePaginator $attendances
                ): bool => $attendances->total() === 2
            );
    }

    public function test_monitoring_is_paginated_to_twenty_records_and_keeps_filters(): void
    {
        $branch = $this->createBranch();

        [, $employee] =
            $this->createEmployee($branch);

        $hrd = $this->createUser('hrd');

        $firstDate = CarbonImmutable::parse(
            '2026-04-01',
            'Asia/Jakarta'
        );

        for ($index = 0; $index < 21; $index++) {
            $attendanceDate = $firstDate
                ->addDays($index)
                ->format('Y-m-d');

            $employeeSchedule =
                $this->createEmployeeSchedule(
                    employee: $employee,
                    approver: $hrd,
                    scheduleDate: $attendanceDate
                );

            $this->createAttendance(
                employee: $employee,
                employeeSchedule: $employeeSchedule,
                creator: $hrd,
                attendanceType: 'check_in',
                attendanceDate: $attendanceDate,
                attendanceTime: '08:45:00',
                punctualityStatus: 'on_time'
            );
        }

        $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-monitoring.index',
                    [
                        'branch_id' => $branch->id,

                        'attendance_type' => 'check_in',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'attendances',
                static function (
                    LengthAwarePaginator $attendances
                ) use (
                    $branch
                ): bool {
                    $nextPageUrl =
                        (string) $attendances
                            ->nextPageUrl();

                    return $attendances->total() === 21
                        && $attendances->count() === 20
                        && $attendances->perPage() === 20
                        && $attendances->currentPage() === 1
                        && $attendances->lastPage() === 2
                        && str_contains(
                            $nextPageUrl,
                            'attendance_type=check_in'
                        )
                        && str_contains(
                            $nextPageUrl,
                            'branch_id='
                            .$branch->id
                        )
                        && str_contains(
                            $nextPageUrl,
                            'page=2'
                        );
                }
            );
    }

    private function createUser(
        string $role
    ): User {
        return User::factory()->create([
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
                        'BR-MON-%03d',
                        $this->sequence
                    ),

                    'name' => sprintf(
                        'Cabang Monitoring %03d',
                        $this->sequence
                    ),

                    'address' => 'Jalan Pengujian Monitoring',

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

        $user = $this->createUser(
            'employee'
        );

        $employee = Employee::query()->create([
            'user_id' => $user->id,

            'branch_id' => $branch->id,

            'employee_number' => sprintf(
                'EMP-MON-%03d',
                $this->sequence
            ),

            'full_name' => sprintf(
                'Karyawan Monitoring %03d',
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
                'Pola Monitoring %03d',
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
        $workSchedule =
            $this->createWorkSchedule();

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

        $attendanceSession =
            $this->createAttendanceSession(
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
