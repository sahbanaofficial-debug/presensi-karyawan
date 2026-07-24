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

final class AttendanceHistoryTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_guest_is_redirected_to_login_from_attendance_history(): void
    {
        $this->get(
            route('attendance.history')
        )->assertRedirect(route('login'));
    }

    public function test_hrd_and_admin_cannot_open_employee_attendance_history(): void
    {
        $hrd = $this->createUser('hrd');
        $admin = $this->createUser('admin');

        foreach ([$hrd, $admin] as $user) {
            $this->actingAs($user)
                ->get(route('attendance.history'))
                ->assertForbidden();
        }
    }

    public function test_employee_only_sees_their_own_attendance_history(): void
    {
        $this->withoutExceptionHandling();
        $branch = $this->createBranch();

        [$employeeUser, $employee] =
            $this->createEmployee($branch);

        [$otherUser, $otherEmployee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-01-05'
            );

        $otherEmployeeSchedule =
            $this->createEmployeeSchedule(
                employee: $otherEmployee,
                approver: $approver,
                scheduleDate: '2026-01-05'
            );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $employeeSchedule,
            creator: $approver,
            attendanceType: 'check_in',
            attendanceDate: '2026-01-05',
            attendanceTime: '08:45:00',
            punctualityStatus: 'on_time'
        );

        $this->createAttendance(
            employee: $otherEmployee,
            employeeSchedule: $otherEmployeeSchedule,
            creator: $approver,
            attendanceType: 'check_in',
            attendanceDate: '2026-01-05',
            attendanceTime: '08:46:00',
            punctualityStatus: 'on_time'
        );

        $response = $this->actingAs($employeeUser)
            ->get(route('attendance.history'));

        $response
            ->assertOk()
            ->assertViewIs('attendance.history')
            ->assertSee('Riwayat Presensi')
            ->assertViewHas(
                'employee',
                static fn (Employee $viewEmployee): bool =>
                    $viewEmployee->is($employee)
            )
            ->assertViewHas(
                'attendances',
                static function (
                    LengthAwarePaginator $attendances
                ) use (
                    $employee,
                    $otherEmployee
                ): bool {
                    $items = collect(
                        $attendances->items()
                    );

                    return $attendances->total() === 1
                        && $items->count() === 1
                        && $items->every(
                            static fn (
                                Attendance $attendance
                            ): bool =>
                                (int) $attendance->employee_id
                                === (int) $employee->id
                        )
                        && $items->doesntContain(
                            static fn (
                                Attendance $attendance
                            ): bool =>
                                (int) $attendance->employee_id
                                === (int) $otherEmployee->id
                        );
                }
            );

        $this->assertAuthenticatedAs(
            $employeeUser
        );

        $this->assertNotSame(
            $employeeUser->id,
            $otherUser->id
        );
    }

    public function test_history_summary_counts_all_employee_attendances(): void
    {
        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $firstSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-01-06'
            );

        $secondSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-01-07'
            );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $firstSchedule,
            creator: $approver,
            attendanceType: 'check_in',
            attendanceDate: '2026-01-06',
            attendanceTime: '08:45:00',
            punctualityStatus: 'on_time'
        );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $firstSchedule,
            creator: $approver,
            attendanceType: 'check_out',
            attendanceDate: '2026-01-06',
            attendanceTime: '17:00:00',
            punctualityStatus: 'not_applicable'
        );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $secondSchedule,
            creator: $approver,
            attendanceType: 'check_in',
            attendanceDate: '2026-01-07',
            attendanceTime: '08:52:00',
            punctualityStatus: 'late'
        );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $secondSchedule,
            creator: $approver,
            attendanceType: 'check_out',
            attendanceDate: '2026-01-07',
            attendanceTime: '17:02:00',
            punctualityStatus: 'not_applicable'
        );

        $this->actingAs($user)
            ->get(route('attendance.history'))
            ->assertOk()
            ->assertViewHas(
                'summary',
                static fn (array $summary): bool =>
                    $summary === [
                        'total' => 4,
                        'check_in' => 2,
                        'check_out' => 2,
                        'late' => 1,
                    ]
            );
    }

    public function test_history_can_be_filtered_by_attendance_type(): void
    {
        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-01-08'
            );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $employeeSchedule,
            creator: $approver,
            attendanceType: 'check_in',
            attendanceDate: '2026-01-08',
            attendanceTime: '08:45:00',
            punctualityStatus: 'on_time'
        );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $employeeSchedule,
            creator: $approver,
            attendanceType: 'check_out',
            attendanceDate: '2026-01-08',
            attendanceTime: '17:00:00',
            punctualityStatus: 'not_applicable'
        );

        $this->actingAs($user)
            ->get(
                route(
                    'attendance.history',
                    [
                        'attendance_type' =>
                            'check_in',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedAttendanceType',
                'check_in'
            )
            ->assertViewHas(
                'attendances',
                static function (
                    LengthAwarePaginator $attendances
                ): bool {
                    $items = collect(
                        $attendances->items()
                    );

                    return $attendances->total() === 1
                        && $items->count() === 1
                        && $items->every(
                            static fn (
                                Attendance $attendance
                            ): bool =>
                                $attendance
                                    ->attendance_type
                                === 'check_in'
                        );
                }
            )
            ->assertViewHas(
                'summary',
                static fn (array $summary): bool =>
                    $summary['total'] === 2
                    && $summary['check_in'] === 1
                    && $summary['check_out'] === 1
            );
    }

    public function test_history_can_be_filtered_by_attendance_date(): void
    {
        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $firstSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-01-09'
            );

        $secondSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-01-10'
            );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $firstSchedule,
            creator: $approver,
            attendanceType: 'check_in',
            attendanceDate: '2026-01-09',
            attendanceTime: '08:45:00',
            punctualityStatus: 'on_time'
        );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $secondSchedule,
            creator: $approver,
            attendanceType: 'check_in',
            attendanceDate: '2026-01-10',
            attendanceTime: '08:46:00',
            punctualityStatus: 'on_time'
        );

        $this->actingAs($user)
            ->get(
                route(
                    'attendance.history',
                    [
                        'attendance_date' =>
                            '2026-01-10',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedAttendanceDate',
                '2026-01-10'
            )
            ->assertViewHas(
                'attendances',
                static function (
                    LengthAwarePaginator $attendances
                ): bool {
                    $items = collect(
                        $attendances->items()
                    );

                    return $attendances->total() === 1
                        && $items->count() === 1
                        && $items->every(
                            static fn (
                                Attendance $attendance
                            ): bool =>
                                $attendance
                                    ->attendance_date
                                    ->format('Y-m-d')
                                === '2026-01-10'
                        );
                }
            );
    }

    public function test_invalid_history_filters_are_ignored(): void
    {
        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $firstSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-01-11'
            );

        $secondSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-01-12'
            );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $firstSchedule,
            creator: $approver,
            attendanceType: 'check_in',
            attendanceDate: '2026-01-11',
            attendanceTime: '08:45:00',
            punctualityStatus: 'on_time'
        );

        $this->createAttendance(
            employee: $employee,
            employeeSchedule: $secondSchedule,
            creator: $approver,
            attendanceType: 'check_out',
            attendanceDate: '2026-01-12',
            attendanceTime: '17:00:00',
            punctualityStatus: 'not_applicable'
        );

        $this->actingAs($user)
            ->get(
                route(
                    'attendance.history',
                    [
                        'attendance_type' =>
                            'unknown_type',

                        'attendance_date' =>
                            '2026-02-31',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedAttendanceType',
                ''
            )
            ->assertViewHas(
                'selectedAttendanceDate',
                null
            )
            ->assertViewHas(
                'attendances',
                static fn (
                    LengthAwarePaginator $attendances
                ): bool =>
                    $attendances->total() === 2
            );
    }

    public function test_history_is_paginated_to_fifteen_records_and_keeps_filters(): void
    {
        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $firstDate = CarbonImmutable::parse(
            '2026-02-01',
            'Asia/Jakarta'
        );

        for ($index = 0; $index < 16; $index++) {
            $attendanceDate = $firstDate
                ->addDays($index)
                ->format('Y-m-d');

            $employeeSchedule =
                $this->createEmployeeSchedule(
                    employee: $employee,
                    approver: $approver,
                    scheduleDate: $attendanceDate
                );

            $this->createAttendance(
                employee: $employee,
                employeeSchedule: $employeeSchedule,
                creator: $approver,
                attendanceType: 'check_in',
                attendanceDate: $attendanceDate,
                attendanceTime: '08:45:00',
                punctualityStatus: 'on_time'
            );
        }

        $this->actingAs($user)
            ->get(
                route(
                    'attendance.history',
                    [
                        'attendance_type' =>
                            'check_in',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'attendances',
                static function (
                    LengthAwarePaginator $attendances
                ): bool {
                    $nextPageUrl =
                        (string) $attendances
                            ->nextPageUrl();

                    return $attendances->total() === 16
                        && $attendances->count() === 15
                        && $attendances->perPage() === 15
                        && $attendances->currentPage() === 1
                        && $attendances->lastPage() === 2
                        && str_contains(
                            $nextPageUrl,
                            'attendance_type=check_in'
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
     * @param array<string, mixed> $overrides
     */
    private function createBranch(
        array $overrides = []
    ): Branch {
        $this->sequence++;

        return Branch::factory()->create(
            array_replace(
                [
                    'code' => sprintf(
                        'BR-HIS-%03d',
                        $this->sequence
                    ),

                    'name' => sprintf(
                        'Cabang Riwayat %03d',
                        $this->sequence
                    ),

                    'address' =>
                        'Jalan Pengujian Riwayat',

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
                'EMP-HIS-%03d',
                $this->sequence
            ),

            'full_name' => sprintf(
                'Karyawan Riwayat %03d',
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

    private function createEmployeeSchedule(
        Employee $employee,
        User $approver,
        string $scheduleDate
    ): EmployeeSchedule {
        $workSchedule =
            $this->createWorkSchedule();

        return EmployeeSchedule::query()->create([
            'employee_id' => $employee->id,

            'work_schedule_id' =>
                $workSchedule->id,

            'schedule_date' => $scheduleDate,

            'schedule_status' => 'work',

            'approved_by' => $approver->id,

            'notes' => null,
        ]);
    }

    private function createWorkSchedule(): WorkSchedule
    {
        $this->sequence++;

        return WorkSchedule::query()->create([
            'name' => sprintf(
                'Pola Riwayat %03d',
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

            'attendance_session_id' =>
                $attendanceSession->id,

            'employee_schedule_id' =>
                $employeeSchedule->id,

            'branch_id' => $branch->id,

            'attendance_type' =>
                $attendanceType,

            'attendance_date' =>
                $attendanceDate,

            'attendance_time' =>
                "{$attendanceDate} {$attendanceTime}",

            'latitude' => $branch->latitude,

            'longitude' => $branch->longitude,

            'accuracy' => 5.0,

            'distance' => 4.5,

            'geofence_radius' =>
                $branch->geofence_radius,

            'attendance_status' => 'present',

            'punctuality_status' =>
                $punctualityStatus,

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

            'attendance_type' =>
                $attendanceType,

            'session_date' =>
                $attendanceDate,

            'start_time' =>
                "{$attendanceDate} {$startTime}",

            'end_time' =>
                "{$attendanceDate} {$endTime}",

            'encrypted_secret' =>
                'JBSWY3DPEHPK3PXP',

            'status' => 'active',

            'created_by' => $creator->id,

            'closed_at' => null,
        ]);
    }
}