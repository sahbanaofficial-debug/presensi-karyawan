<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Services\TotpService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AttendanceSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeSequence = 0;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(
            route('attendance-sessions.index')
        )->assertRedirect(route('login'));

        $this->get(
            route('attendance-sessions.create')
        )->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_attendance_session_module(): void
    {
        $branch = $this->createValidBranch([
            'code' => 'SESSION-EMPLOYEE',
        ]);

        $employee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SESSION-ACCESS-403',

                'full_name' => 'Karyawan Pengujian Sesi',
            ]
        );

        $employeeUser = User::query()->findOrFail(
            $employee->user_id
        );

        $this->actingAs($employeeUser)
            ->get(route('attendance-sessions.index'))
            ->assertForbidden();

        $this->actingAs($employeeUser)
            ->get(route('attendance-sessions.create'))
            ->assertForbidden();
    }

    public function test_hrd_and_admin_can_open_index_and_create_pages(): void
    {
        $hrd = $this->createHrd();
        $admin = $this->createAdmin();

        $branch = $this->createValidBranch([
            'code' => 'SESSION-ACCESS',
            'name' => 'Cabang Akses Sesi',
        ]);

        foreach ([$hrd, $admin] as $user) {
            $this->actingAs($user)
                ->get(route('attendance-sessions.index'))
                ->assertOk()
                ->assertViewIs(
                    'attendance-sessions.index'
                )
                ->assertSee('Sesi Presensi');

            $this->actingAs($user)
                ->get(route('attendance-sessions.create'))
                ->assertOk()
                ->assertViewIs(
                    'attendance-sessions.create'
                )
                ->assertSee('Buka Sesi Presensi')
                ->assertSee($branch->code)
                ->assertSee($branch->name);
        }
    }

    public function test_create_page_only_displays_valid_active_branches(): void
    {
        $hrd = $this->createHrd();

        $validBranch = $this->createValidBranch([
            'code' => 'SESSION-VALID',
            'name' => 'Cabang Sesi Valid',
        ]);

        $inactiveBranch = $this->createValidBranch([
            'code' => 'SESSION-INACTIVE',
            'name' => 'Cabang Sesi Tidak Aktif',
            'status' => 'inactive',
        ]);

        $invalidGeofenceBranch =
            $this->createValidBranch([
                'code' => 'SESSION-GEOFENCE-INVALID',
                'name' => 'Cabang Geofence Tidak Lengkap',
                'geofence_radius' => 0,
            ]);

        $this->actingAs($hrd)
            ->get(route('attendance-sessions.create'))
            ->assertOk()
            ->assertSee($validBranch->code)
            ->assertSee($validBranch->name)
            ->assertDontSee($inactiveBranch->code)
            ->assertDontSee($inactiveBranch->name)
            ->assertDontSee(
                $invalidGeofenceBranch->code
            )
            ->assertDontSee(
                $invalidGeofenceBranch->name
            );
    }

    public function test_hrd_can_open_valid_attendance_session(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-01 08:30:00',
                'Asia/Jakarta'
            )
        );

        $hrd = $this->createHrd();

        $branch = $this->createValidBranch([
            'code' => 'SESSION-STORE-HRD',
        ]);

        $response = $this->actingAs($hrd)
            ->post(
                route('attendance-sessions.store'),
                [
                    'branch_id' => $branch->id,

                    'attendance_type' => ' CHECK_IN ',

                    'session_date' => '2026-08-01',

                    'start_time' => '08:15',

                    'end_time' => '09:30',
                ]
            );

        $attendanceSession =
            AttendanceSession::query()
                ->firstOrFail();

        $response
            ->assertRedirect(
                route(
                    'attendance-sessions.show',
                    $attendanceSession
                )
            )
            ->assertSessionHas(
                'success',
                'Sesi presensi berhasil dibuka.'
            );

        $this->assertSame(
            $branch->id,
            $attendanceSession->branch_id
        );

        $this->assertSame(
            'check_in',
            $attendanceSession->attendance_type
        );

        $this->assertSame(
            '2026-08-01',
            $attendanceSession
                ->session_date
                ->format('Y-m-d')
        );

        $this->assertSame(
            '2026-08-01 08:15:00',
            $attendanceSession
                ->start_time
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-08-01 09:30:00',
            $attendanceSession
                ->end_time
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            'active',
            $attendanceSession->status
        );

        $this->assertSame(
            $hrd->id,
            $attendanceSession->created_by
        );

        $this->assertNull(
            $attendanceSession->closed_at
        );

        $this->assertTrue(
            Str::isUuid(
                $attendanceSession->public_id
            )
        );

        $this->assertNotSame(
            '',
            $attendanceSession->encrypted_secret
        );
    }

    public function test_admin_can_open_valid_attendance_session(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-02 13:00:00',
                'Asia/Jakarta'
            )
        );

        $admin = $this->createAdmin();

        $branch = $this->createValidBranch();

        $response = $this->actingAs($admin)
            ->post(
                route('attendance-sessions.store'),
                [
                    'branch_id' => $branch->id,

                    'attendance_type' => 'check_out',

                    'session_date' => '2026-08-02',

                    'start_time' => '16:45',

                    'end_time' => '18:00',
                ]
            );

        $attendanceSession =
            AttendanceSession::query()
                ->firstOrFail();

        $response->assertRedirect(
            route(
                'attendance-sessions.show',
                $attendanceSession
            )
        );

        $this->assertSame(
            'check_out',
            $attendanceSession->attendance_type
        );

        $this->assertSame(
            $admin->id,
            $attendanceSession->created_by
        );
    }

    public function test_totp_secret_is_encrypted_in_database_and_hidden_from_array(): void
    {
        $creator = $this->createHrd();

        $branch = $this->createValidBranch();

        $attendanceSession =
            $this->createAttendanceSession(
                $branch,
                $creator,
                [
                    'encrypted_secret' => 'JBSWY3DPEHPK3PXP',
                ]
            );

        $rawSecret = DB::table(
            'attendance_sessions'
        )
            ->where(
                'id',
                $attendanceSession->id
            )
            ->value('encrypted_secret');

        $attendanceSession->refresh();

        $this->assertSame(
            'JBSWY3DPEHPK3PXP',
            $attendanceSession->encrypted_secret
        );

        $this->assertNotSame(
            'JBSWY3DPEHPK3PXP',
            $rawSecret
        );

        $this->assertArrayNotHasKey(
            'encrypted_secret',
            $attendanceSession->toArray()
        );
    }

    public function test_branch_without_complete_geofence_is_rejected(): void
    {
        $hrd = $this->createHrd();

        $branch = $this->createValidBranch([
            'geofence_radius' => 0,
        ]);

        $this->actingAs($hrd)
            ->from(
                route('attendance-sessions.create')
            )
            ->post(
                route('attendance-sessions.store'),
                [
                    'branch_id' => $branch->id,

                    'attendance_type' => 'check_in',

                    'session_date' => '2026-08-03',

                    'start_time' => '08:15',

                    'end_time' => '09:30',
                ]
            )
            ->assertRedirect(
                route('attendance-sessions.create')
            )
            ->assertSessionHasErrors([
                'branch_id',
            ]);

        $this->assertDatabaseCount(
            'attendance_sessions',
            0
        );
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $hrd = $this->createHrd();

        $branch = $this->createValidBranch();

        $this->actingAs($hrd)
            ->from(
                route('attendance-sessions.create')
            )
            ->post(
                route('attendance-sessions.store'),
                [
                    'branch_id' => $branch->id,

                    'attendance_type' => 'check_in',

                    'session_date' => '2026-08-04',

                    'start_time' => '09:30',

                    'end_time' => '08:15',
                ]
            )
            ->assertRedirect(
                route('attendance-sessions.create')
            )
            ->assertSessionHasErrors([
                'end_time',
            ]);

        $this->assertDatabaseCount(
            'attendance_sessions',
            0
        );
    }

    public function test_duplicate_active_session_is_rejected(): void
    {
        $hrd = $this->createHrd();

        $branch = $this->createValidBranch();

        $this->createAttendanceSession(
            $branch,
            $hrd,
            [
                'attendance_type' => 'check_in',

                'session_date' => '2026-08-05',

                'start_time' => '2026-08-05 08:15:00',

                'end_time' => '2026-08-05 09:30:00',

                'status' => 'active',
            ]
        );

        $this->actingAs($hrd)
            ->from(
                route('attendance-sessions.create')
            )
            ->post(
                route('attendance-sessions.store'),
                [
                    'branch_id' => $branch->id,

                    'attendance_type' => 'check_in',

                    'session_date' => '2026-08-05',

                    'start_time' => '08:20',

                    'end_time' => '09:20',
                ]
            )
            ->assertRedirect(
                route('attendance-sessions.create')
            )
            ->assertSessionHasErrors([
                'attendance_type',
            ]);

        $this->assertDatabaseCount(
            'attendance_sessions',
            1
        );
    }

    public function test_hrd_and_admin_can_open_session_detail_page(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-06 08:30:00',
                'Asia/Jakarta'
            )
        );

        $hrd = $this->createHrd();
        $admin = $this->createAdmin();

        $branch = $this->createValidBranch([
            'code' => 'SESSION-DETAIL',
            'name' => 'Cabang Detail Sesi',
        ]);

        $attendanceSession =
            $this->createAttendanceSession(
                $branch,
                $hrd,
                [
                    'session_date' => '2026-08-06',

                    'start_time' => '2026-08-06 08:15:00',

                    'end_time' => '2026-08-06 09:30:00',
                ]
            );

        foreach ([$hrd, $admin] as $user) {
            $this->actingAs($user)
                ->get(
                    route(
                        'attendance-sessions.show',
                        $attendanceSession
                    )
                )
                ->assertOk()
                ->assertViewIs(
                    'attendance-sessions.show'
                )
                ->assertSee('Detail Sesi Presensi')
                ->assertSee($branch->code)
                ->assertSee($branch->name)
                ->assertSee(
                    $attendanceSession->public_id
                )
                ->assertSee('QR Code Dinamis');
        }
    }

    public function test_payload_returns_valid_totp_without_exposing_secret_or_internal_id(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-07 08:30:05',
                'Asia/Jakarta'
            )
        );

        $hrd = $this->createHrd();

        $branch = $this->createValidBranch();

        $attendanceSession =
            $this->createAttendanceSession(
                $branch,
                $hrd,
                [
                    'session_date' => '2026-08-07',

                    'start_time' => '2026-08-07 08:00:00',

                    'end_time' => '2026-08-07 09:00:00',
                ]
            );

        $response = $this->actingAs($hrd)
            ->getJson(
                route(
                    'attendance-sessions.payload',
                    $attendanceSession
                )
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.public_id',
                $attendanceSession->public_id
            )
            ->assertJsonPath(
                'data.attendance_type',
                'check_in'
            )
            ->assertJsonPath(
                'data.session_date',
                '2026-08-07'
            );

        $data = $response->json('data');

        $this->assertIsArray($data);

        $this->assertMatchesRegularExpression(
            '/^\d{6}$/',
            (string) $data['token']
        );

        $qrPayload = json_decode(
            (string) $data['qr_payload'],
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            [
                'session' => $attendanceSession->public_id,

                'token' => $data['token'],
            ],
            $qrPayload
        );

        $this->assertArrayNotHasKey(
            'id',
            $data
        );

        $this->assertArrayNotHasKey(
            'encrypted_secret',
            $data
        );

        $this->assertArrayNotHasKey(
            'created_by',
            $data
        );

        $attendanceSession->refresh();

        $totpService = app(
            TotpService::class
        );

        $this->assertTrue(
            $totpService->verifyCode(
                $attendanceSession
                    ->encrypted_secret,

                (string) $data['token'],

                CarbonImmutable::now(
                    'Asia/Jakarta'
                )->getTimestamp(),

                0
            )
        );
    }

    public function test_payload_before_start_time_is_rejected(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-08 08:00:00',
                'Asia/Jakarta'
            )
        );

        $hrd = $this->createHrd();

        $branch = $this->createValidBranch();

        $attendanceSession =
            $this->createAttendanceSession(
                $branch,
                $hrd,
                [
                    'session_date' => '2026-08-08',

                    'start_time' => '2026-08-08 09:00:00',

                    'end_time' => '2026-08-08 10:00:00',
                ]
            );

        $this->actingAs($hrd)
            ->getJson(
                route(
                    'attendance-sessions.payload',
                    $attendanceSession
                )
            )
            ->assertStatus(422)
            ->assertJsonPath(
                'status',
                'not_started'
            )
            ->assertJsonPath(
                'message',
                'Sesi presensi belum dimulai.'
            );
    }

    public function test_payload_token_changes_after_totp_period(): void
    {
        $hrd = $this->createHrd();

        $branch = $this->createValidBranch();

        $attendanceSession =
            $this->createAttendanceSession(
                $branch,
                $hrd,
                [
                    'session_date' => '2026-08-09',

                    'start_time' => '2026-08-09 08:00:00',

                    'end_time' => '2026-08-09 09:30:00',
                ]
            );

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-09 08:30:01',
                'Asia/Jakarta'
            )
        );

        $firstToken = $this->actingAs($hrd)
            ->getJson(
                route(
                    'attendance-sessions.payload',
                    $attendanceSession
                )
            )
            ->assertOk()
            ->json('data.token');

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-09 08:30:31',
                'Asia/Jakarta'
            )
        );

        $secondToken = $this->actingAs($hrd)
            ->getJson(
                route(
                    'attendance-sessions.payload',
                    $attendanceSession
                )
            )
            ->assertOk()
            ->json('data.token');

        $this->assertNotSame(
            $firstToken,
            $secondToken
        );
    }

    public function test_closed_session_payload_is_rejected(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-10 08:30:00',
                'Asia/Jakarta'
            )
        );

        $hrd = $this->createHrd();

        $branch = $this->createValidBranch();

        $attendanceSession =
            $this->createAttendanceSession(
                $branch,
                $hrd,
                [
                    'session_date' => '2026-08-10',

                    'start_time' => '2026-08-10 08:00:00',

                    'end_time' => '2026-08-10 09:00:00',

                    'status' => 'closed',

                    'closed_at' => '2026-08-10 08:20:00',
                ]
            );

        $this->actingAs($hrd)
            ->getJson(
                route(
                    'attendance-sessions.payload',
                    $attendanceSession
                )
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'status',
                'closed'
            );
    }

    public function test_admin_can_close_active_session(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-11 08:30:00',
                'Asia/Jakarta'
            )
        );

        $admin = $this->createAdmin();

        $branch = $this->createValidBranch();

        $attendanceSession =
            $this->createAttendanceSession(
                $branch,
                $admin,
                [
                    'session_date' => '2026-08-11',

                    'start_time' => '2026-08-11 08:00:00',

                    'end_time' => '2026-08-11 09:30:00',
                ]
            );

        $response = $this->actingAs($admin)
            ->patch(
                route(
                    'attendance-sessions.close',
                    $attendanceSession
                )
            );

        $response
            ->assertRedirect(
                route(
                    'attendance-sessions.show',
                    $attendanceSession
                )
            )
            ->assertSessionHas(
                'success',
                'Sesi presensi berhasil ditutup.'
            );

        $attendanceSession->refresh();

        $this->assertSame(
            'closed',
            $attendanceSession->status
        );

        $this->assertNotNull(
            $attendanceSession->closed_at
        );

        $this->assertSame(
            '2026-08-11 08:30:00',
            $attendanceSession
                ->closed_at
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_closed_session_cannot_be_closed_again(): void
    {
        $hrd = $this->createHrd();

        $branch = $this->createValidBranch();

        $attendanceSession =
            $this->createAttendanceSession(
                $branch,
                $hrd,
                [
                    'status' => 'closed',
                    'closed_at' => now(),
                ]
            );

        $this->actingAs($hrd)
            ->from(
                route(
                    'attendance-sessions.show',
                    $attendanceSession
                )
            )
            ->patch(
                route(
                    'attendance-sessions.close',
                    $attendanceSession
                )
            )
            ->assertRedirect(
                route(
                    'attendance-sessions.show',
                    $attendanceSession
                )
            )
            ->assertSessionHasErrors([
                'attendance_session',
            ]);

        $attendanceSession->refresh();

        $this->assertSame(
            'closed',
            $attendanceSession->status
        );
    }

    public function test_elapsed_active_session_is_marked_expired_on_index(): void
    {
        $hrd = $this->createHrd();

        $branch = $this->createValidBranch();

        $attendanceSession =
            $this->createAttendanceSession(
                $branch,
                $hrd,
                [
                    'session_date' => '2026-08-12',

                    'start_time' => '2026-08-12 08:00:00',

                    'end_time' => '2026-08-12 08:29:00',

                    'status' => 'active',
                ]
            );

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-12 08:30:00',
                'Asia/Jakarta'
            )
        );

        $this->actingAs($hrd)
            ->get(route('attendance-sessions.index'))
            ->assertOk();

        $attendanceSession->refresh();

        $this->assertSame(
            'expired',
            $attendanceSession->status
        );
    }

    public function test_search_and_filters_return_matching_session(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-13 08:00:00',
                'Asia/Jakarta'
            )
        );

        $hrd = $this->createHrd();

        $alphaBranch = $this->createValidBranch([
            'code' => 'SESSION-ALPHA',
            'name' => 'Cabang Alpha Sesi',
        ]);

        $betaBranch = $this->createValidBranch([
            'code' => 'SESSION-BETA',
            'name' => 'Cabang Beta Sesi',
        ]);

        $this->createAttendanceSession(
            $alphaBranch,
            $hrd,
            [
                'attendance_type' => 'check_in',

                'session_date' => '2026-08-13',

                'start_time' => '2026-08-13 09:00:00',

                'end_time' => '2026-08-13 10:00:00',

                'status' => 'active',
            ]
        );

        $this->createAttendanceSession(
            $betaBranch,
            $hrd,
            [
                'attendance_type' => 'check_out',

                'session_date' => '2026-08-13',

                'start_time' => '2026-08-13 16:00:00',

                'end_time' => '2026-08-13 18:00:00',

                'status' => 'closed',

                'closed_at' => '2026-08-13 17:00:00',
            ]
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-sessions.index',
                    [
                        'search' => 'Alpha',

                        'attendance_type' => 'check_in',

                        'status' => 'active',

                        'session_date' => '2026-08-13',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'search',
                'Alpha'
            )
            ->assertViewHas(
                'selectedAttendanceType',
                'check_in'
            )
            ->assertViewHas(
                'selectedStatus',
                'active'
            )
            ->assertViewHas(
                'selectedSessionDate',
                '2026-08-13'
            )
            ->assertSee('SESSION-ALPHA')
            ->assertSee('Cabang Alpha Sesi')
            ->assertDontSee('SESSION-BETA')
            ->assertDontSee('Cabang Beta Sesi');
    }

    public function test_index_uses_fifteen_items_per_page(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-14 07:00:00',
                'Asia/Jakarta'
            )
        );

        $hrd = $this->createHrd();

        $branch = $this->createValidBranch();

        $startingDate = CarbonImmutable::parse(
            '2026-08-14',
            'Asia/Jakarta'
        );

        for ($index = 0; $index < 16; $index++) {
            $sessionDate = $startingDate
                ->addDays($index)
                ->format('Y-m-d');

            $this->createAttendanceSession(
                $branch,
                $hrd,
                [
                    'session_date' => $sessionDate,

                    'start_time' => "{$sessionDate} 08:00:00",

                    'end_time' => "{$sessionDate} 09:00:00",

                    'status' => 'active',
                ]
            );
        }

        $this->actingAs($hrd)
            ->get(route('attendance-sessions.index'))
            ->assertOk()
            ->assertViewHas(
                'attendanceSessions',
                static function (
                    $attendanceSessions
                ): bool {
                    return
                        $attendanceSessions
                            ->perPage() === 15
                        && $attendanceSessions
                            ->currentPage() === 1
                        && $attendanceSessions
                            ->total() === 16
                        && $attendanceSessions
                            ->count() === 15;
                }
            );

        $this->actingAs($hrd)
            ->get(
                route(
                    'attendance-sessions.index',
                    [
                        'page' => 2,
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'attendanceSessions',
                static function (
                    $attendanceSessions
                ): bool {
                    return
                        $attendanceSessions
                            ->perPage() === 15
                        && $attendanceSessions
                            ->currentPage() === 2
                        && $attendanceSessions
                            ->total() === 16
                        && $attendanceSessions
                            ->count() === 1;
                }
            );
    }

    private function createHrd(): User
    {
        return User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createValidBranch(
        array $overrides = []
    ): Branch {
        return Branch::factory()->create(
            array_replace(
                [
                    'latitude' => 3.59519600,

                    'longitude' => 98.67222600,

                    'geofence_radius' => 30.00,

                    'maximum_accuracy' => 20.00,

                    'status' => 'active',
                ],
                $overrides
            )
        );
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
                        'EMP-SESSION-%03d',
                        $this->employeeSequence
                    ),

                    'full_name' => sprintf(
                        'Karyawan Sesi %03d',
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
    private function createAttendanceSession(
        Branch $branch,
        User $creator,
        array $overrides = []
    ): AttendanceSession {
        return AttendanceSession::query()->create(
            array_replace(
                [
                    'branch_id' => $branch->id,

                    'attendance_type' => 'check_in',

                    'session_date' => '2026-08-20',

                    'start_time' => '2026-08-20 08:00:00',

                    'end_time' => '2026-08-20 09:00:00',

                    'encrypted_secret' => 'JBSWY3DPEHPK3PXP',

                    'status' => 'active',

                    'created_by' => $creator->id,

                    'closed_at' => null,
                ],
                $overrides
            )
        );
    }
}
