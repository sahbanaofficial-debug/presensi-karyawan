<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AttendanceValidationLogTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_guest_is_redirected_to_login_from_validation_logs(): void
    {
        $this->get(
            route('attendance-validation-logs.index')
        )->assertRedirect(route('login'));
    }

    public function test_admin_and_employee_cannot_access_validation_logs(): void
    {
        $admin = $this->createUser('admin');

        $branch = $this->createBranch();

        [$employeeUser] =
            $this->createEmployee($branch);

        foreach (
            [
                $admin,
                $employeeUser,
            ] as $user
        ) {
            $this->actingAs($user)
                ->get(
                    route(
                        'attendance-validation-logs.index'
                    )
                )
                ->assertForbidden();
        }
    }

    public function test_hrd_can_access_validation_log_page(): void
    {
        $hrd = $this->createUser('hrd');

        $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-validation-logs.index'
                )
            )
            ->assertOk()
            ->assertViewIs(
                'attendance-validation-logs.index'
            )
            ->assertSee('Log Validasi Presensi')
            ->assertSee('Filter Log Validasi')
            ->assertSee('Daftar Log Validasi')
            ->assertViewHas(
                'summary',
                static fn (array $summary): bool => $summary === [
                    'total' => 0,
                    'accepted' => 0,
                    'rejected' => 0,
                    'location' => 0,
                    'qr' => 0,
                    'schedule' => 0,
                ]
            );
    }

    public function test_hrd_can_see_logs_and_correct_summary(): void
    {
        $branch = $this->createBranch();

        [, $employee] =
            $this->createEmployee($branch);

        $hrd = $this->createUser('hrd');

        $session = $this->createAttendanceSession(
            branch: $branch,
            creator: $hrd,
            attendanceType: 'check_in',
            sessionDate: '2026-05-01'
        );

        $this->createValidationLog(
            user: $employee->user,
            session: $session,
            validationType: 'attendance_accepted',
            status: 'accepted',
            reason: 'Presensi berhasil diterima.',
            createdAt: '2026-05-01 08:45:00'
        );

        $this->createValidationLog(
            user: $employee->user,
            session: $session,
            validationType: 'outside_geofence',
            status: 'rejected',
            reason: 'Lokasi berada di luar radius.',
            createdAt: '2026-05-01 08:46:00',
            overrides: [
                'distance' => 120.5,
            ]
        );

        $this->createValidationLog(
            user: $employee->user,
            session: $session,
            validationType: 'totp_invalid',
            status: 'rejected',
            reason: 'Token TOTP tidak valid.',
            createdAt: '2026-05-01 08:47:00'
        );

        $this->createValidationLog(
            user: $employee->user,
            session: $session,
            validationType: 'check_out_too_early',
            status: 'rejected',
            reason: 'Presensi pulang terlalu awal.',
            createdAt: '2026-05-01 08:48:00'
        );

        $response = $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-validation-logs.index'
                )
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'summary',
                static fn (array $summary): bool => $summary === [
                    'total' => 4,
                    'accepted' => 1,
                    'rejected' => 3,
                    'location' => 1,
                    'qr' => 1,
                    'schedule' => 1,
                ]
            )
            ->assertViewHas(
                'logs',
                static fn (
                    LengthAwarePaginator $logs
                ): bool => $logs->total() === 4
                    && $logs->count() === 4
            )
            ->assertViewHas(
                'availableValidationTypes',
                static function (
                    Collection $types
                ): bool {
                    return $types->contains(
                        'attendance_accepted'
                    )
                        && $types->contains(
                            'outside_geofence'
                        )
                        && $types->contains(
                            'totp_invalid'
                        )
                        && $types->contains(
                            'check_out_too_early'
                        );
                }
            )
            ->assertSee($employee->full_name)
            ->assertSee('Lokasi berada di luar radius.')
            ->assertSee('Token TOTP tidak valid.')
            ->assertDontSee('123456');
    }

    public function test_validation_logs_support_combined_filters(): void
    {
        $firstBranch = $this->createBranch();
        $secondBranch = $this->createBranch();

        [, $firstEmployee] =
            $this->createEmployee($firstBranch);

        [, $secondEmployee] =
            $this->createEmployee($secondBranch);

        $hrd = $this->createUser('hrd');

        $firstSession =
            $this->createAttendanceSession(
                branch: $firstBranch,
                creator: $hrd,
                attendanceType: 'check_in',
                sessionDate: '2026-05-02'
            );

        $secondSession =
            $this->createAttendanceSession(
                branch: $secondBranch,
                creator: $hrd,
                attendanceType: 'check_in',
                sessionDate: '2026-05-02'
            );

        $targetLogId =
            $this->createValidationLog(
                user: $firstEmployee->user,
                session: $firstSession,
                validationType: 'outside_geofence',
                status: 'rejected',
                reason: 'Target pengujian filter.',
                createdAt: '2026-05-02 08:45:00'
            );

        $this->createValidationLog(
            user: $firstEmployee->user,
            session: $firstSession,
            validationType: 'attendance_accepted',
            status: 'accepted',
            reason: 'Status berbeda.',
            createdAt: '2026-05-02 08:46:00'
        );

        $this->createValidationLog(
            user: $secondEmployee->user,
            session: $secondSession,
            validationType: 'outside_geofence',
            status: 'rejected',
            reason: 'Cabang berbeda.',
            createdAt: '2026-05-02 08:47:00'
        );

        $this->createValidationLog(
            user: $firstEmployee->user,
            session: $firstSession,
            validationType: 'outside_geofence',
            status: 'rejected',
            reason: 'Tanggal berbeda.',
            createdAt: '2026-05-03 08:45:00'
        );

        $response = $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-validation-logs.index',
                    [
                        'validation_date' => '2026-05-02',

                        'branch_id' => $firstBranch->id,

                        'employee_id' => $firstEmployee->id,

                        'status' => 'rejected',

                        'validation_type' => 'outside_geofence',
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'selectedValidationDate',
                '2026-05-02'
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
                'selectedStatus',
                'rejected'
            )
            ->assertViewHas(
                'selectedValidationType',
                'outside_geofence'
            )
            ->assertViewHas(
                'summary',
                static fn (array $summary): bool => $summary === [
                    'total' => 1,
                    'accepted' => 0,
                    'rejected' => 1,
                    'location' => 1,
                    'qr' => 0,
                    'schedule' => 0,
                ]
            )
            ->assertViewHas(
                'logs',
                static function (
                    LengthAwarePaginator $logs
                ) use (
                    $targetLogId
                ): bool {
                    $items = collect(
                        $logs->items()
                    );

                    $firstLog = $items->first();

                    return $logs->total() === 1
                        && $items->count() === 1
                        && $firstLog !== null
                        && (int) $firstLog->id
                            === $targetLogId;
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
                    'attendance-validation-logs.index',
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

    public function test_invalid_validation_log_filters_are_ignored(): void
    {
        $branch = $this->createBranch();

        [, $employee] =
            $this->createEmployee($branch);

        $hrd = $this->createUser('hrd');

        $session = $this->createAttendanceSession(
            branch: $branch,
            creator: $hrd,
            attendanceType: 'check_in',
            sessionDate: '2026-05-04'
        );

        $this->createValidationLog(
            user: $employee->user,
            session: $session,
            validationType: 'attendance_accepted',
            status: 'accepted',
            reason: 'Presensi diterima.',
            createdAt: '2026-05-04 08:45:00'
        );

        $this->createValidationLog(
            user: $employee->user,
            session: $session,
            validationType: 'totp_invalid',
            status: 'rejected',
            reason: 'Token tidak valid.',
            createdAt: '2026-05-04 08:46:00'
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-validation-logs.index',
                    [
                        'validation_date' => '2026-02-31',

                        'branch_id' => 'invalid',

                        'employee_id' => '-10',

                        'status' => 'unknown',

                        'validation_type' => 'unknown_validation',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedValidationDate',
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
                'selectedStatus',
                ''
            )
            ->assertViewHas(
                'selectedValidationType',
                ''
            )
            ->assertViewHas(
                'summary',
                static fn (array $summary): bool => $summary['total'] === 2
                    && $summary['accepted'] === 1
                    && $summary['rejected'] === 1
            )
            ->assertViewHas(
                'logs',
                static fn (
                    LengthAwarePaginator $logs
                ): bool => $logs->total() === 2
            );
    }

    public function test_validation_logs_are_paginated_to_twenty_records_and_keep_filters(): void
    {
        $branch = $this->createBranch();

        [, $employee] =
            $this->createEmployee($branch);

        $hrd = $this->createUser('hrd');

        $session = $this->createAttendanceSession(
            branch: $branch,
            creator: $hrd,
            attendanceType: 'check_in',
            sessionDate: '2026-06-01'
        );

        $firstMoment = CarbonImmutable::parse(
            '2026-06-01 08:00:00',
            'Asia/Jakarta'
        );

        for ($index = 0; $index < 21; $index++) {
            $this->createValidationLog(
                user: $employee->user,
                session: $session,
                validationType: 'totp_invalid',
                status: 'rejected',
                reason: sprintf(
                    'Token tidak valid nomor %d.',
                    $index + 1
                ),
                createdAt: $firstMoment
                    ->addMinutes($index)
                    ->format('Y-m-d H:i:s')
            );
        }

        $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-validation-logs.index',
                    [
                        'branch_id' => $branch->id,

                        'status' => 'rejected',

                        'validation_type' => 'totp_invalid',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'logs',
                static function (
                    LengthAwarePaginator $logs
                ) use (
                    $branch
                ): bool {
                    $nextPageUrl =
                        (string) $logs->nextPageUrl();

                    return $logs->total() === 21
                        && $logs->count() === 20
                        && $logs->perPage() === 20
                        && $logs->currentPage() === 1
                        && $logs->lastPage() === 2
                        && str_contains(
                            $nextPageUrl,
                            'branch_id='.$branch->id
                        )
                        && str_contains(
                            $nextPageUrl,
                            'status=rejected'
                        )
                        && str_contains(
                            $nextPageUrl,
                            'validation_type=totp_invalid'
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
                        'BR-LOG-%03d',
                        $this->sequence
                    ),

                    'name' => sprintf(
                        'Cabang Log %03d',
                        $this->sequence
                    ),

                    'address' => 'Jalan Pengujian Log Validasi',

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
                'EMP-LOG-%03d',
                $this->sequence
            ),

            'full_name' => sprintf(
                'Karyawan Log %03d',
                $this->sequence
            ),

            'position' => 'Karyawan',

            'phone_number' => null,

            'employment_status' => 'active',
        ]);

        $employee->setRelation(
            'user',
            $user
        );

        return [
            $user,
            $employee,
        ];
    }

    private function createAttendanceSession(
        Branch $branch,
        User $creator,
        string $attendanceType,
        string $sessionDate
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

            'session_date' => $sessionDate,

            'start_time' => "{$sessionDate} {$startTime}",

            'end_time' => "{$sessionDate} {$endTime}",

            'encrypted_secret' => 'JBSWY3DPEHPK3PXP',

            'status' => 'active',

            'created_by' => $creator->id,

            'closed_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createValidationLog(
        User $user,
        ?AttendanceSession $session,
        string $validationType,
        string $status,
        string $reason,
        string $createdAt,
        array $overrides = []
    ): int {
        $this->sequence++;

        $attributes = array_replace(
            [
                'user_id' => $user->id,

                'attendance_session_id' => $session?->id,

                'validation_type' => $validationType,

                'status' => $status,

                'reason' => $reason,

                'payload_reference' => hash(
                    'sha256',
                    sprintf(
                        'payload-log-%d',
                        $this->sequence
                    )
                ),

                'latitude' => 3.595196,

                'longitude' => 98.672226,

                'accuracy' => 5.0,

                'distance' => 4.5,

                'created_at' => $createdAt,
            ],
            $overrides
        );

        return (int) DB::table(
            'validation_logs'
        )->insertGetId($attributes);
    }
}
