<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\TerminalDynamicQrPayloadService;
use App\Services\TotpService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use JsonException;
use Tests\TestCase;

final class AttendanceTransactionTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_guest_is_redirected_from_attendance_scanner(): void
    {
        $this->get(
            route('attendance.create')
        )->assertRedirect(route('login'));
    }

    public function test_hrd_and_admin_cannot_access_employee_attendance_routes(): void
    {
        $hrd = $this->createUser('hrd');
        $admin = $this->createUser('admin');

        foreach ([$hrd, $admin] as $user) {
            $this->actingAs($user)
                ->get(route('attendance.create'))
                ->assertForbidden();

            $this->actingAs($user)
                ->postJson(
                    route('attendance.store'),
                    []
                )
                ->assertForbidden();
        }
    }

    public function test_employee_can_open_scanner_when_schedule_is_valid(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-01 08:30:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $this->createEmployeeSchedule(
            employee: $employee,
            approver: $approver,
            scheduleDate: '2026-10-01'
        );

        $this->actingAs($user)
            ->get(route('attendance.create'))
            ->assertOk()
            ->assertViewIs('attendance.create')
            ->assertViewHas('canScan', true)
            ->assertViewHas(
                'employee',
                static fn (Employee $viewEmployee): bool => $viewEmployee->is($employee)
            )
            ->assertSee('Presensi Karyawan')
            ->assertSee('Mulai Kamera');
    }

    public function test_on_time_check_in_is_accepted(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-02 08:45:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-10-02'
            );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-02',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '10:00:00'
            );

        $response = $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession,
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    5.0
                )
            );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'code',
                'attendance_accepted'
            )
            ->assertJsonPath(
                'data.attendance_type',
                'check_in'
            )
            ->assertJsonPath(
                'data.punctuality_status',
                'on_time'
            );

        $this->assertDatabaseHas(
            'attendances',
            [
                'employee_id' => $employee->id,

                'attendance_session_id' => $attendanceSession->id,

                'employee_schedule_id' => $employeeSchedule->id,

                'branch_id' => $branch->id,

                'attendance_type' => 'check_in',

                'attendance_status' => 'present',

                'punctuality_status' => 'on_time',

                'validation_status' => 'accepted',
            ]
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'user_id' => $user->id,

                'attendance_session_id' => $attendanceSession->id,

                'validation_type' => 'attendance_accepted',

                'status' => 'accepted',
            ]
        );
    }

    public function test_late_check_in_is_accepted_with_late_status(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-03 08:50:01',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-10-03'
            );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-03',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '10:00:00'
            );

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession,
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    5.0
                )
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.punctuality_status',
                'late'
            )
            ->assertJsonPath(
                'message',
                'Presensi masuk diterima dengan status terlambat.'
            );

        $this->assertDatabaseHas(
            'attendances',
            [
                'employee_schedule_id' => $employeeSchedule->id,

                'attendance_type' => 'check_in',

                'punctuality_status' => 'late',
            ]
        );
    }

    public function test_check_in_after_final_limit_is_rejected_and_logged(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-04 09:15:01',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $this->createEmployeeSchedule(
            employee: $employee,
            approver: $approver,
            scheduleDate: '2026-10-04'
        );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-04',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '10:00:00'
            );

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession,
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    5.0
                )
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'code',
                'check_in_limit_passed'
            )
            ->assertJsonPath(
                'message',
                'Batas akhir presensi masuk telah lewat pada pukul 09:15 WIB.'
            );

        $this->assertDatabaseCount(
            'attendances',
            0
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'user_id' => $user->id,

                'attendance_session_id' => $attendanceSession->id,

                'validation_type' => 'check_in_limit_passed',

                'status' => 'rejected',
            ]
        );
    }

    public function test_auto_session_rejects_first_scan_after_final_check_in_limit(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-04 21:44:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $this->createEmployeeSchedule(
            employee: $employee,
            approver: $approver,
            scheduleDate: '2026-10-04'
        );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-04',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '22:30:00'
            );

        $attendanceSession->update([
            'attendance_type' => AttendanceSession::TYPE_AUTO,

            'session_source' => AttendanceSession::SOURCE_AUTOMATIC,

            'automation_key' => 'AUTO:'.$branch->id.':2026-10-04',

            'created_by' => null,
        ]);

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession->fresh(),
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    5.0
                )
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'code',
                'check_in_limit_passed'
            );

        $this->assertDatabaseCount(
            'attendances',
            0
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'user_id' => $user->id,

                'attendance_session_id' => $attendanceSession->id,

                'validation_type' => 'check_in_limit_passed',

                'status' => 'rejected',
            ]
        );
    }

    public function test_unknown_public_session_id_is_rejected_and_logged(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-04 08:45:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user] = $this->createEmployee($branch);

        $qrPayload = json_encode(
            [
                'session' => '7fbda890-9fba-4aca-9a9d-560231483c85',

                'token' => '123456',
            ],
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
        );

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                [
                    'qr_payload' => $qrPayload,

                    'latitude' => (float) $branch->latitude,

                    'longitude' => (float) $branch->longitude,

                    'accuracy' => 5.0,
                ]
            )
            ->assertNotFound()
            ->assertJsonPath(
                'code',
                'session_not_found'
            );

        $this->assertDatabaseCount(
            'attendances',
            0
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'user_id' => $user->id,

                'attendance_session_id' => null,

                'validation_type' => 'session_not_found',

                'status' => 'rejected',
            ]
        );
    }

    public function test_invalid_totp_token_is_rejected(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-05 08:45:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $this->createEmployeeSchedule(
            employee: $employee,
            approver: $approver,
            scheduleDate: '2026-10-05'
        );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-05',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '10:00:00'
            );

        $validToken = app(
            TotpService::class
        )->generateCode(
            $attendanceSession->encrypted_secret,
            CarbonImmutable::now(
                'Asia/Jakarta'
            )->getTimestamp()
        );

        $invalidLastDigit =
            ((int) substr($validToken, -1) + 1) % 10;

        $invalidToken =
            substr($validToken, 0, -1)
            .(string) $invalidLastDigit;

        $qrPayload = json_encode(
            [
                'session' => $attendanceSession->public_id,

                'token' => $invalidToken,
            ],
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
        );

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                [
                    'qr_payload' => $qrPayload,

                    'latitude' => (float) $branch->latitude,

                    'longitude' => (float) $branch->longitude,

                    'accuracy' => 5.0,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'code',
                'totp_invalid'
            );

        $this->assertDatabaseCount(
            'attendances',
            0
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'validation_type' => 'totp_invalid',
                'status' => 'rejected',
            ]
        );
    }

    public function test_location_with_low_accuracy_is_rejected(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-06 08:45:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch([
            'maximum_accuracy' => 20.0,
        ]);

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $this->createEmployeeSchedule(
            employee: $employee,
            approver: $approver,
            scheduleDate: '2026-10-06'
        );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-06',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '10:00:00'
            );

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession,
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    25.0
                )
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'code',
                'location_accuracy_too_low'
            );

        $this->assertDatabaseCount(
            'attendances',
            0
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'validation_type' => 'location_accuracy_too_low',

                'status' => 'rejected',
            ]
        );
    }

    public function test_location_outside_geofence_is_rejected(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-07 08:45:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch([
            'geofence_radius' => 30.0,
        ]);

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $this->createEmployeeSchedule(
            employee: $employee,
            approver: $approver,
            scheduleDate: '2026-10-07'
        );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-07',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '10:00:00'
            );

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession,
                    (float) $branch->latitude + 0.001,
                    (float) $branch->longitude,
                    5.0
                )
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'code',
                'outside_geofence'
            );

        $this->assertDatabaseCount(
            'attendances',
            0
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'validation_type' => 'outside_geofence',

                'status' => 'rejected',
            ]
        );
    }

    public function test_employee_cannot_use_session_from_another_branch(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-08 08:45:00',
                'Asia/Jakarta'
            )
        );

        $employeeBranch = $this->createBranch();

        $sessionBranch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee(
                $employeeBranch
            );

        $approver = $this->createUser('hrd');

        $this->createEmployeeSchedule(
            employee: $employee,
            approver: $approver,
            scheduleDate: '2026-10-08'
        );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $sessionBranch,
                creator: $approver,
                sessionDate: '2026-10-08',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '10:00:00'
            );

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession,
                    (float) $sessionBranch->latitude,
                    (float) $sessionBranch->longitude,
                    5.0
                )
            )
            ->assertForbidden()
            ->assertJsonPath(
                'code',
                'branch_mismatch'
            );

        $this->assertDatabaseCount(
            'attendances',
            0
        );
    }

    public function test_attendance_without_daily_schedule_is_rejected(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-09 08:45:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user] = $this->createEmployee($branch);

        $creator = $this->createUser('hrd');

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $creator,
                sessionDate: '2026-10-09',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '10:00:00'
            );

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession,
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    5.0
                )
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'code',
                'employee_schedule_not_found'
            );

        $this->assertDatabaseCount(
            'attendances',
            0
        );
    }

    public function test_duplicate_check_in_is_rejected(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-10 08:45:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-10-10'
            );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-10',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '10:00:00'
            );

        Attendance::query()->create([
            'employee_id' => $employee->id,

            'attendance_session_id' => $attendanceSession->id,

            'employee_schedule_id' => $employeeSchedule->id,

            'branch_id' => $branch->id,

            'attendance_type' => 'check_in',

            'attendance_date' => '2026-10-10',

            'attendance_time' => '2026-10-10 08:40:00',

            'latitude' => $branch->latitude,

            'longitude' => $branch->longitude,

            'accuracy' => 5.0,

            'distance' => 0.0,

            'geofence_radius' => $branch->geofence_radius,

            'attendance_status' => 'present',

            'punctuality_status' => 'on_time',

            'validation_status' => 'accepted',
        ]);

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession,
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    5.0
                )
            )
            ->assertConflict()
            ->assertJsonPath(
                'code',
                'duplicate_attendance'
            );

        $this->assertDatabaseCount(
            'attendances',
            1
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'validation_type' => 'duplicate_attendance',

                'status' => 'rejected',
            ]
        );
    }

    public function test_early_check_out_is_rejected_and_logged(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-11 16:59:59',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $this->createEmployeeSchedule(
            employee: $employee,
            approver: $approver,
            scheduleDate: '2026-10-11'
        );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-11',
                attendanceType: 'check_out',
                startTime: '16:30:00',
                endTime: '18:00:00'
            );

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession,
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    5.0
                )
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'code',
                'check_out_too_early'
            );

        $this->assertDatabaseCount(
            'attendances',
            0
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'user_id' => $user->id,

                'attendance_session_id' => $attendanceSession->id,

                'validation_type' => 'check_out_too_early',

                'status' => 'rejected',
            ]
        );
    }

    public function test_check_out_at_scheduled_time_is_accepted(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-12 17:00:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-10-12'
            );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-12',
                attendanceType: 'check_out',
                startTime: '16:30:00',
                endTime: '18:00:00'
            );

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession,
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    5.0
                )
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.attendance_type',
                'check_out'
            )
            ->assertJsonPath(
                'data.punctuality_status',
                'not_applicable'
            )
            ->assertJsonPath(
                'message',
                'Presensi pulang diterima.'
            );

        $this->assertDatabaseHas(
            'attendances',
            [
                'employee_schedule_id' => $employeeSchedule->id,

                'attendance_type' => 'check_out',

                'attendance_status' => 'present',

                'punctuality_status' => 'not_applicable',

                'validation_status' => 'accepted',
            ]
        );
    }

    public function test_auto_session_resolves_first_scan_as_check_in(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-13 08:45:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-10-13'
            );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-13',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '18:00:00'
            );

        $attendanceSession->update([
            'attendance_type' => AttendanceSession::TYPE_AUTO,

            'session_source' => AttendanceSession::SOURCE_AUTOMATIC,

            'automation_key' => 'AUTO:'.$branch->id.':2026-10-13',

            'created_by' => null,
        ]);

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession->fresh(),
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    5.0
                )
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.attendance_type',
                'check_in'
            );

        $this->assertDatabaseHas(
            'attendances',
            [
                'employee_schedule_id' => $employeeSchedule->id,

                'attendance_session_id' => $attendanceSession->id,

                'attendance_type' => 'check_in',
            ]
        );
    }

    public function test_auto_session_resolves_second_scan_as_check_out(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-14 17:00:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-10-14'
            );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-14',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '18:00:00'
            );

        $attendanceSession->update([
            'attendance_type' => AttendanceSession::TYPE_AUTO,

            'session_source' => AttendanceSession::SOURCE_AUTOMATIC,

            'automation_key' => 'AUTO:'.$branch->id.':2026-10-14',

            'created_by' => null,
        ]);

        Attendance::query()->create([
            'employee_id' => $employee->id,

            'attendance_session_id' => $attendanceSession->id,

            'employee_schedule_id' => $employeeSchedule->id,

            'branch_id' => $branch->id,
            'attendance_type' => 'check_in',
            'attendance_date' => '2026-10-14',

            'attendance_time' => '2026-10-14 08:45:00',

            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'accuracy' => 5.0,
            'distance' => 0.0,

            'geofence_radius' => $branch->geofence_radius,

            'attendance_status' => 'present',
            'punctuality_status' => 'on_time',
            'validation_status' => 'accepted',
        ]);

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession->fresh(),
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    5.0
                )
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.attendance_type',
                'check_out'
            )
            ->assertJsonPath(
                'data.punctuality_status',
                'not_applicable'
            );

        $this->assertDatabaseHas(
            'attendances',
            [
                'employee_schedule_id' => $employeeSchedule->id,

                'attendance_session_id' => $attendanceSession->id,

                'attendance_type' => 'check_out',
            ]
        );
    }

    public function test_auto_session_rejects_scan_after_attendance_is_complete(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-10-15 17:00:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();

        [$user, $employee] =
            $this->createEmployee($branch);

        $approver = $this->createUser('hrd');

        $employeeSchedule =
            $this->createEmployeeSchedule(
                employee: $employee,
                approver: $approver,
                scheduleDate: '2026-10-15'
            );

        $attendanceSession =
            $this->createAttendanceSession(
                branch: $branch,
                creator: $approver,
                sessionDate: '2026-10-15',
                attendanceType: 'check_in',
                startTime: '08:15:00',
                endTime: '18:00:00'
            );

        $attendanceSession->update([
            'attendance_type' => AttendanceSession::TYPE_AUTO,

            'session_source' => AttendanceSession::SOURCE_AUTOMATIC,

            'automation_key' => 'AUTO:'.$branch->id.':2026-10-15',

            'created_by' => null,
        ]);

        foreach (
            [
                'check_in' => 'on_time',
                'check_out' => 'not_applicable',
            ] as $attendanceType => $punctualityStatus
        ) {
            Attendance::query()->create([
                'employee_id' => $employee->id,

                'attendance_session_id' => $attendanceSession->id,

                'employee_schedule_id' => $employeeSchedule->id,

                'branch_id' => $branch->id,

                'attendance_type' => $attendanceType,

                'attendance_date' => '2026-10-15',

                'attendance_time' => '2026-10-15 17:00:00',

                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'accuracy' => 5.0,
                'distance' => 0.0,

                'geofence_radius' => $branch->geofence_radius,

                'attendance_status' => 'present',

                'punctuality_status' => $punctualityStatus,

                'validation_status' => 'accepted',
            ]);
        }

        $this->actingAs($user)
            ->postJson(
                route('attendance.store'),
                $this->attendancePayload(
                    $attendanceSession->fresh(),
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    5.0
                )
            )
            ->assertConflict()
            ->assertJsonPath(
                'code',
                'attendance_completed'
            );

        $this->assertDatabaseCount(
            'attendances',
            2
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'attendance_session_id' => $attendanceSession->id,

                'validation_type' => 'attendance_completed',

                'status' => 'rejected',
            ]
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
                        'BR-TX-%03d',
                        $this->sequence
                    ),

                    'name' => sprintf(
                        'Cabang Transaksi %03d',
                        $this->sequence
                    ),

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
                'EMP-TX-%03d',
                $this->sequence
            ),

            'full_name' => sprintf(
                'Karyawan Transaksi %03d',
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
                        'Pola Transaksi %03d',
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

    private function createAttendanceSession(
        Branch $branch,
        User $creator,
        string $sessionDate,
        string $attendanceType,
        string $startTime,
        string $endTime
    ): AttendanceSession {
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
     * @return array{
     *     qr_payload: string,
     *     latitude: float,
     *     longitude: float,
     *     accuracy: float
     * }
     *
     * @throws JsonException
     */
    private function attendancePayload(
        AttendanceSession $attendanceSession,
        float $latitude,
        float $longitude,
        float $accuracy
    ): array {
        $timestamp = CarbonImmutable::now(
            'Asia/Jakarta'
        )->getTimestamp();

        $token = app(
            TotpService::class
        )->generateCode(
            $attendanceSession
                ->encrypted_secret,
            $timestamp
        );

        if (
            $attendanceSession->isAutomatic()
            && $attendanceSession
                ->isAutoType()
        ) {
            $terminalCreator =
                User::factory()->create([
                    'role' => 'hrd',
                    'status' => 'active',
                ]);

            $branchTerminal =
                BranchTerminal::query()->create([
                    'branch_id' => $attendanceSession
                        ->branch_id,

                    'name' => 'Terminal Test Transaksi Otomatis',

                    'device_token_hash' => hash(
                        'sha256',
                        (string) Str::uuid()
                    ),

                    'activation_code_hash' => null,
                    'activation_expires_at' => null,
                    'activated_at' => now(),
                    'last_seen_at' => now(),

                    'status' => BranchTerminal::STATUS_ACTIVE,

                    'created_by' => $terminalCreator->id,

                    'revoked_by' => null,
                    'revoked_at' => null,
                ]);

            $terminalPayload = app(
                TerminalDynamicQrPayloadService::class
            )->payloadFor(
                $branchTerminal
            );

            $qrPayload = (string)
                $terminalPayload[
                    'qr_payload'
                ];
        } else {
            $qrPayload = json_encode(
                [
                    'session' => $attendanceSession
                        ->public_id,

                    'token' => $token,
                ],
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
            );
        }

        return [
            'qr_payload' => $qrPayload,

            'latitude' => $latitude,

            'longitude' => $longitude,

            'accuracy' => $accuracy,
        ];
    }
}
