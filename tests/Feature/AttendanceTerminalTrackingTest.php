<?php

declare(strict_types=1);

namespace Tests\Feature;

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

final class AttendanceTerminalTrackingTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    /**
     * @throws JsonException
     */
    public function test_signed_automatic_qr_tracks_terminal_on_attendance_and_accepted_log(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-01 08:45:00',
                'Asia/Jakarta'
            )
        );

        $context = $this->createContext(
            '2026-12-01'
        );

        $terminal = $this->createTerminal(
            branch: $context['branch'],
            creator: $context['approver']
        );

        $payload = $this->signedPayload(
            session: $context['session'],
            terminal: $terminal,
            branch: $context['branch']
        );

        $this->actingAs(
            $context['user']
        )
            ->postJson(
                route('attendance.store'),
                $payload
            )
            ->assertCreated()
            ->assertJsonPath(
                'code',
                'attendance_accepted'
            )
            ->assertJsonPath(
                'data.attendance_type',
                'check_in'
            );

        $this->assertDatabaseHas(
            'attendances',
            [
                'employee_id' => $context['employee']->id,

                'attendance_session_id' => $context['session']->id,

                'branch_terminal_id' => $terminal->id,

                'attendance_type' => 'check_in',

                'validation_status' => 'accepted',
            ]
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'user_id' => $context['user']->id,

                'attendance_session_id' => $context['session']->id,

                'branch_terminal_id' => $terminal->id,

                'validation_type' => 'attendance_accepted',

                'status' => 'accepted',
            ]
        );
    }

    /**
     * @throws JsonException
     */
    public function test_tampered_signature_is_rejected_and_tracks_resolved_terminal(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-02 08:45:00',
                'Asia/Jakarta'
            )
        );

        $context = $this->createContext(
            '2026-12-02'
        );

        $terminal = $this->createTerminal(
            branch: $context['branch'],
            creator: $context['approver']
        );

        $payload = $this->signedPayload(
            session: $context['session'],
            terminal: $terminal,
            branch: $context['branch']
        );

        $decoded = json_decode(
            $payload['qr_payload'],
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $signature = (string)
            $decoded['signature'];

        $decoded['signature'] =
            ($signature[0] === 'a'
                ? 'b'
                : 'a')
            .substr($signature, 1);

        $payload['qr_payload'] = json_encode(
            $decoded,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
        );

        $this->actingAs(
            $context['user']
        )
            ->postJson(
                route('attendance.store'),
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'code',
                'terminal_signature_invalid'
            );

        $this->assertDatabaseCount(
            'attendances',
            0
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'attendance_session_id' => $context['session']->id,

                'branch_terminal_id' => $terminal->id,

                'validation_type' => 'terminal_signature_invalid',

                'status' => 'rejected',
            ]
        );
    }

    /**
     * @throws JsonException
     */
    public function test_unsigned_automatic_qr_is_rejected_without_terminal_tracking(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-03 08:45:00',
                'Asia/Jakarta'
            )
        );

        $context = $this->createContext(
            '2026-12-03'
        );

        $payload = $this->legacyPayload(
            session: $context['session'],
            branch: $context['branch']
        );

        $this->actingAs(
            $context['user']
        )
            ->postJson(
                route('attendance.store'),
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'code',
                'terminal_payload_required'
            );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'attendance_session_id' => $context['session']->id,

                'branch_terminal_id' => null,

                'validation_type' => 'terminal_payload_required',

                'status' => 'rejected',
            ]
        );
    }

    /**
     * @throws JsonException
     */
    public function test_terminal_from_another_branch_is_rejected_and_tracked(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-04 08:45:00',
                'Asia/Jakarta'
            )
        );

        $context = $this->createContext(
            '2026-12-04'
        );

        $otherBranch = $this->createBranch();

        $otherTerminal = $this->createTerminal(
            branch: $otherBranch,
            creator: $context['approver']
        );

        $otherSession =
            $this->createAutomaticSession(
                branch: $otherBranch,
                sessionDate: '2026-12-04'
            );

        $payload = $this->signedPayload(
            session: $otherSession,
            terminal: $otherTerminal,
            branch: $otherBranch
        );

        $decoded = json_decode(
            $payload['qr_payload'],
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $decoded['session'] =
            $context['session']->public_id;

        $payload['qr_payload'] = json_encode(
            $decoded,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
        );

        $payload['latitude'] =
            (float) $context['branch']
                ->latitude;

        $payload['longitude'] =
            (float) $context['branch']
                ->longitude;

        $this->actingAs(
            $context['user']
        )
            ->postJson(
                route('attendance.store'),
                $payload
            )
            ->assertForbidden()
            ->assertJsonPath(
                'code',
                'terminal_branch_mismatch'
            );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'attendance_session_id' => $context['session']->id,

                'branch_terminal_id' => $otherTerminal->id,

                'validation_type' => 'terminal_branch_mismatch',

                'status' => 'rejected',
            ]
        );
    }

    /**
     * @throws JsonException
     */
    public function test_revoked_terminal_is_rejected_and_tracked(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-05 08:45:00',
                'Asia/Jakarta'
            )
        );

        $context = $this->createContext(
            '2026-12-05'
        );

        $terminal = $this->createTerminal(
            branch: $context['branch'],
            creator: $context['approver']
        );

        $payload = $this->signedPayload(
            session: $context['session'],
            terminal: $terminal,
            branch: $context['branch']
        );

        $terminal->update([
            'status' => BranchTerminal::STATUS_REVOKED,

            'device_token_hash' => null,

            'revoked_by' => $context['approver']->id,

            'revoked_at' => now(),
        ]);

        $this->actingAs(
            $context['user']
        )
            ->postJson(
                route('attendance.store'),
                $payload
            )
            ->assertForbidden()
            ->assertJsonPath(
                'code',
                'terminal_inactive'
            );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'attendance_session_id' => $context['session']->id,

                'branch_terminal_id' => $terminal->id,

                'validation_type' => 'terminal_inactive',

                'status' => 'rejected',
            ]
        );
    }

    /**
     * @throws JsonException
     */
    public function test_signed_manual_terminal_qr_is_accepted_and_tracks_terminal(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-06 08:45:00',
                'Asia/Jakarta'
            )
        );

        $context = $this->createContext(
            sessionDate: '2026-12-06',
            automatic: false
        );

        $terminal = $this->createTerminal(
            branch: $context['branch'],
            creator: $context['approver']
        );

        $payload = $this->signedPayload(
            session: $context['session'],
            terminal: $terminal,
            branch: $context['branch']
        );

        $this->actingAs(
            $context['user']
        )
            ->postJson(
                route('attendance.store'),
                $payload
            )
            ->assertCreated()
            ->assertJsonPath(
                'code',
                'attendance_accepted'
            )
            ->assertJsonPath(
                'data.attendance_type',
                'check_in'
            );

        $this->assertDatabaseHas(
            'attendances',
            [
                'attendance_session_id' => $context['session']->id,
                'branch_terminal_id' => $terminal->id,
                'attendance_type' => 'check_in',
                'validation_status' => 'accepted',
            ]
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'attendance_session_id' => $context['session']->id,
                'branch_terminal_id' => $terminal->id,
                'validation_type' => 'attendance_accepted',
                'status' => 'accepted',
            ]
        );
    }

    /**
     * @throws JsonException
     */
    public function test_legacy_manual_qr_remains_accepted_without_terminal_tracking(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-06 08:45:00',
                'Asia/Jakarta'
            )
        );

        $context = $this->createContext(
            sessionDate: '2026-12-06',
            automatic: false
        );

        $payload = $this->legacyPayload(
            session: $context['session'],
            branch: $context['branch']
        );

        $this->actingAs(
            $context['user']
        )
            ->postJson(
                route('attendance.store'),
                $payload
            )
            ->assertCreated()
            ->assertJsonPath(
                'code',
                'attendance_accepted'
            );

        $this->assertDatabaseHas(
            'attendances',
            [
                'attendance_session_id' => $context['session']->id,

                'branch_terminal_id' => null,

                'attendance_type' => 'check_in',

                'validation_status' => 'accepted',
            ]
        );

        $this->assertDatabaseHas(
            'validation_logs',
            [
                'attendance_session_id' => $context['session']->id,

                'branch_terminal_id' => null,

                'validation_type' => 'attendance_accepted',

                'status' => 'accepted',
            ]
        );
    }

    /**
     * @return array{
     *     branch: Branch,
     *     user: User,
     *     employee: Employee,
     *     approver: User,
     *     schedule: EmployeeSchedule,
     *     session: AttendanceSession
     * }
     */
    private function createContext(
        string $sessionDate,
        bool $automatic = true
    ): array {
        $branch = $this->createBranch();

        $user = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,

            'employee_number' => sprintf(
                'EMP-TERM-TRACK-%03d',
                ++$this->sequence
            ),

            'full_name' => 'Karyawan Tracking Terminal',

            'position' => 'Karyawan',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        $approver = User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $workSchedule =
            WorkSchedule::query()->create([
                'name' => sprintf(
                    'Pola Tracking Terminal %03d',
                    ++$this->sequence
                ),

                'check_in_time' => '08:45:00',
                'check_out_time' => '17:00:00',
                'check_in_open_minutes' => 30,
                'check_in_limit_minutes' => 30,
                'late_tolerance_minutes' => 5,
                'check_out_limit_minutes' => 60,
                'status' => 'active',
            ]);

        $schedule =
            EmployeeSchedule::query()->create([
                'employee_id' => $employee->id,

                'work_schedule_id' => $workSchedule->id,

                'schedule_date' => $sessionDate,
                'schedule_status' => 'work',
                'approved_by' => $approver->id,
                'notes' => null,
            ]);

        $session = $automatic
            ? $this->createAutomaticSession(
                branch: $branch,
                sessionDate: $sessionDate
            )
            : $this->createManualSession(
                branch: $branch,
                creator: $approver,
                sessionDate: $sessionDate
            );

        return [
            'branch' => $branch,
            'user' => $user,
            'employee' => $employee,
            'approver' => $approver,
            'schedule' => $schedule,
            'session' => $session,
        ];
    }

    private function createBranch(): Branch
    {
        return Branch::factory()->create([
            'latitude' => 3.5374963,
            'longitude' => 98.6844756,
            'geofence_radius' => 30.00,
            'maximum_accuracy' => 25.00,
            'status' => 'active',
        ]);
    }

    private function createAutomaticSession(
        Branch $branch,
        string $sessionDate
    ): AttendanceSession {
        return AttendanceSession::query()->create([
            'branch_id' => $branch->id,
            'weekly_schedule_id' => null,

            'attendance_type' => AttendanceSession::TYPE_AUTO,

            'session_source' => AttendanceSession::SOURCE_AUTOMATIC,

            'automation_key' => sprintf(
                'AUTO-TERM-TRACK:%d:%s:%03d',
                $branch->id,
                $sessionDate,
                ++$this->sequence
            ),

            'session_date' => $sessionDate,

            'start_time' => "{$sessionDate} 08:15:00",

            'end_time' => "{$sessionDate} 18:00:00",

            'encrypted_secret' => 'JBSWY3DPEHPK3PXP',

            'status' => 'active',
            'created_by' => null,
            'closed_at' => null,
        ]);
    }

    private function createManualSession(
        Branch $branch,
        User $creator,
        string $sessionDate
    ): AttendanceSession {
        return AttendanceSession::query()->create([
            'branch_id' => $branch->id,
            'weekly_schedule_id' => null,
            'attendance_type' => 'check_in',

            'session_source' => AttendanceSession::SOURCE_MANUAL,

            'automation_key' => null,
            'session_date' => $sessionDate,

            'start_time' => "{$sessionDate} 08:15:00",

            'end_time' => "{$sessionDate} 10:00:00",

            'encrypted_secret' => 'JBSWY3DPEHPK3PXP',

            'status' => 'active',
            'created_by' => $creator->id,
            'closed_at' => null,
        ]);
    }

    private function createTerminal(
        Branch $branch,
        User $creator
    ): BranchTerminal {
        return BranchTerminal::query()->create([
            'branch_id' => $branch->id,

            'name' => sprintf(
                'Terminal Tracking %03d',
                ++$this->sequence
            ),

            'device_token_hash' => hash(
                'sha256',
                (string) Str::uuid()
            ),

            'activation_code_hash' => null,
            'activation_expires_at' => null,
            'activated_at' => now(),
            'last_seen_at' => now(),

            'status' => BranchTerminal::STATUS_ACTIVE,

            'created_by' => $creator->id,
            'revoked_by' => null,
            'revoked_at' => null,
        ]);
    }

    /**
     * @return array{
     *     qr_payload: string,
     *     latitude: float,
     *     longitude: float,
     *     accuracy: float
     * }
     */
    private function signedPayload(
        AttendanceSession $session,
        BranchTerminal $terminal,
        Branch $branch
    ): array {
        $result = app(
            TerminalDynamicQrPayloadService::class
        )->payloadFor(
            $terminal
        );

        $this->assertTrue(
            $result['available']
        );

        $this->assertSame(
            $session->public_id,
            $result['session']['public_id']
        );

        return [
            'qr_payload' => (string) $result[
                    'qr_payload'
                ],

            'latitude' => (float) $branch->latitude,

            'longitude' => (float) $branch->longitude,

            'accuracy' => 5.0,
        ];
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
    private function legacyPayload(
        AttendanceSession $session,
        Branch $branch
    ): array {
        $timestamp = CarbonImmutable::now(
            'Asia/Jakarta'
        )->getTimestamp();

        $token = app(
            TotpService::class
        )->generateCode(
            $session->encrypted_secret,
            $timestamp
        );

        return [
            'qr_payload' => json_encode(
                [
                    'session' => $session->public_id,

                    'token' => $token,
                ],
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
            ),

            'latitude' => (float) $branch->latitude,

            'longitude' => (float) $branch->longitude,

            'accuracy' => 5.0,
        ];
    }
}
