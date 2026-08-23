<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\User;
use App\Services\BranchTerminalLifecycleService;
use App\Services\TerminalDynamicQrPayloadService;
use App\Services\TotpService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use JsonException;
use Tests\TestCase;

final class BranchTerminalDynamicQrEndpointTest extends TestCase
{
    use RefreshDatabase;

    private int $sessionSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_dynamic_qr_endpoint_requires_terminal_credentials(): void
    {
        $this->getJson(
            route('terminal.qr-payload')
        )
            ->assertUnauthorized()
            ->assertJsonPath(
                'code',
                'terminal_credentials_missing'
            );
    }

    /**
     * @throws JsonException
     */
    public function test_active_terminal_receives_signed_payload_for_current_automatic_session(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-23 09:00:05',
                'Asia/Jakarta'
            )
        );

        $activated = $this->activateTerminal(
            'Terminal Dynamic QR Utama'
        );

        $session = $this->createAutomaticSession(
            branch: $activated['terminal']
                ->branch,

            sessionDate: '2026-11-23',
            startTime: '08:00:00',
            endTime: '17:00:00'
        );

        $response = $this->withTerminalHeaders(
            $activated
        )
            ->getJson(
                route('terminal.qr-payload')
            )
            ->assertOk()
            ->assertJsonPath(
                'code',
                'terminal_qr_payload_ready'
            )
            ->assertJsonPath(
                'data.available',
                true
            )
            ->assertJsonPath(
                'data.session.public_id',
                $session->public_id
            )
            ->assertJsonPath(
                'data.terminal.public_id',
                $activated['terminal']
                    ->public_id
            )
            ->assertJsonMissingPath(
                'data.session.id'
            )
            ->assertJsonMissingPath(
                'data.session.encrypted_secret'
            );

        $qrPayload = json_decode(
            (string) $response->json(
                'data.qr_payload'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            TerminalDynamicQrPayloadService::PAYLOAD_VERSION,
            $qrPayload['version']
        );

        $this->assertSame(
            $session->public_id,
            $qrPayload['session']
        );

        $this->assertSame(
            $activated['terminal']
                ->public_id,
            $qrPayload['terminal']
        );

        $this->assertMatchesRegularExpression(
            '/^\d{6}$/',
            $qrPayload['token']
        );

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $qrPayload['signature']
        );

        $timestamp = CarbonImmutable::now(
            'Asia/Jakarta'
        )->getTimestamp();

        $this->assertTrue(
            app(TotpService::class)
                ->verifyCode(
                    $session
                        ->encrypted_secret,

                    $qrPayload['token'],
                    $timestamp,
                    0
                )
        );

        $this->assertTrue(
            app(
                TerminalDynamicQrPayloadService::class
            )->verifySignature(
                sessionPublicId: $qrPayload['session'],

                token: $qrPayload['token'],

                terminalPublicId: $qrPayload['terminal'],

                signature: $qrPayload['signature']
            )
        );
    }

    /**
     * @throws JsonException
     */
    public function test_current_manual_session_is_exposed_on_terminal_with_signed_payload(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-24 09:00:00',
                'Asia/Jakarta'
            )
        );

        $activated = $this->activateTerminal(
            'Terminal Sesi Manual'
        );

        $creator = $this->createHrd();

        $manualSession = $this->createManualSession(
            branch: $activated['terminal']
                ->branch,

            creator: $creator,
            sessionDate: '2026-11-24'
        );

        $otherBranch =
            $this->createValidBranch();

        $this->createAutomaticSession(
            branch: $otherBranch,
            sessionDate: '2026-11-24',
            startTime: '08:00:00',
            endTime: '17:00:00'
        );

        $response = $this->withTerminalHeaders(
            $activated
        )
            ->getJson(
                route('terminal.qr-payload')
            )
            ->assertOk()
            ->assertJsonPath(
                'code',
                'terminal_qr_payload_ready'
            )
            ->assertJsonPath(
                'data.available',
                true
            )
            ->assertJsonPath(
                'data.session.public_id',
                $manualSession->public_id
            )
            ->assertJsonPath(
                'data.session.attendance_type',
                'check_in'
            )
            ->assertJsonPath(
                'data.session.session_source',
                AttendanceSession::SOURCE_MANUAL
            );

        $qrPayload = json_decode(
            (string) $response->json(
                'data.qr_payload'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            TerminalDynamicQrPayloadService::PAYLOAD_VERSION,
            $qrPayload['version']
        );

        $this->assertSame(
            $manualSession->public_id,
            $qrPayload['session']
        );

        $this->assertSame(
            $activated['terminal']->public_id,
            $qrPayload['terminal']
        );

        $this->assertTrue(
            app(
                TerminalDynamicQrPayloadService::class
            )->verifySignature(
                sessionPublicId: $qrPayload['session'],
                token: $qrPayload['token'],
                terminalPublicId: $qrPayload['terminal'],
                signature: $qrPayload['signature']
            )
        );
    }

    public function test_manual_session_has_priority_over_automatic_session_for_terminal_demo(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-24 09:30:00',
                'Asia/Jakarta'
            )
        );

        $activated = $this->activateTerminal(
            'Terminal Prioritas Manual'
        );

        $branch = $activated['terminal']->branch;
        $creator = $this->createHrd();

        $automaticSession = $this->createAutomaticSession(
            branch: $branch,
            sessionDate: '2026-11-24',
            startTime: '08:00:00',
            endTime: '17:00:00'
        );

        $manualSession = $this->createManualSession(
            branch: $branch,
            creator: $creator,
            sessionDate: '2026-11-24'
        );

        $this->withTerminalHeaders(
            $activated
        )
            ->getJson(
                route('terminal.qr-payload')
            )
            ->assertOk()
            ->assertJsonPath(
                'code',
                'terminal_qr_payload_ready'
            )
            ->assertJsonPath(
                'data.session.public_id',
                $manualSession->public_id
            )
            ->assertJsonMissing([
                'public_id' => $automaticSession->public_id,
            ]);
    }

    public function test_session_before_start_time_is_unavailable(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-25 07:59:59',
                'Asia/Jakarta'
            )
        );

        $activated = $this->activateTerminal(
            'Terminal Sebelum Sesi'
        );

        $this->createAutomaticSession(
            branch: $activated['terminal']
                ->branch,

            sessionDate: '2026-11-25',
            startTime: '08:00:00',
            endTime: '17:00:00'
        );

        $this->withTerminalHeaders(
            $activated
        )
            ->getJson(
                route('terminal.qr-payload')
            )
            ->assertOk()
            ->assertJsonPath(
                'code',
                'terminal_qr_unavailable'
            )
            ->assertJsonPath(
                'data.refresh_after',
                15
            );
    }

    public function test_session_is_available_exactly_at_end_time(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-26 17:00:00',
                'Asia/Jakarta'
            )
        );

        $activated = $this->activateTerminal(
            'Terminal Boundary Akhir'
        );

        $this->createAutomaticSession(
            branch: $activated['terminal']
                ->branch,

            sessionDate: '2026-11-26',
            startTime: '08:00:00',
            endTime: '17:00:00'
        );

        $this->withTerminalHeaders(
            $activated
        )
            ->getJson(
                route('terminal.qr-payload')
            )
            ->assertOk()
            ->assertJsonPath(
                'code',
                'terminal_qr_payload_ready'
            )
            ->assertJsonPath(
                'data.available',
                true
            );
    }

    public function test_session_after_end_time_is_unavailable(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-27 17:00:01',
                'Asia/Jakarta'
            )
        );

        $activated = $this->activateTerminal(
            'Terminal Sesudah Sesi'
        );

        $this->createAutomaticSession(
            branch: $activated['terminal']
                ->branch,

            sessionDate: '2026-11-27',
            startTime: '08:00:00',
            endTime: '17:00:00'
        );

        $this->withTerminalHeaders(
            $activated
        )
            ->getJson(
                route('terminal.qr-payload')
            )
            ->assertOk()
            ->assertJsonPath(
                'code',
                'terminal_qr_unavailable'
            )
            ->assertJsonPath(
                'data.available',
                false
            );
    }

    /**
     * @throws JsonException
     */
    public function test_dynamic_qr_token_and_signature_change_after_totp_period(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-28 09:00:05',
                'Asia/Jakarta'
            )
        );

        $activated = $this->activateTerminal(
            'Terminal Rotasi Token'
        );

        $this->createAutomaticSession(
            branch: $activated['terminal']
                ->branch,

            sessionDate: '2026-11-28',
            startTime: '08:00:00',
            endTime: '17:00:00'
        );

        $firstResponse =
            $this->withTerminalHeaders(
                $activated
            )->getJson(
                route('terminal.qr-payload')
            );

        $firstResponse->assertOk();

        $firstPayload = json_decode(
            (string) $firstResponse->json(
                'data.qr_payload'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-28 09:00:35',
                'Asia/Jakarta'
            )
        );

        $secondResponse =
            $this->withTerminalHeaders(
                $activated
            )->getJson(
                route('terminal.qr-payload')
            );

        $secondResponse->assertOk();

        $secondPayload = json_decode(
            (string) $secondResponse->json(
                'data.qr_payload'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertNotSame(
            $firstPayload['token'],
            $secondPayload['token']
        );

        $this->assertNotSame(
            $firstPayload['signature'],
            $secondPayload['signature']
        );

        $this->assertSame(
            $firstPayload['session'],
            $secondPayload['session']
        );

        $this->assertSame(
            $firstPayload['terminal'],
            $secondPayload['terminal']
        );
    }

    public function test_inactive_terminal_branch_is_rejected(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-29 09:00:00',
                'Asia/Jakarta'
            )
        );

        $activated = $this->activateTerminal(
            'Terminal Cabang Nonaktif'
        );

        $activated['terminal']
            ->branch
            ->update([
                'status' => 'inactive',
            ]);

        $this->withTerminalHeaders(
            $activated
        )
            ->getJson(
                route('terminal.qr-payload')
            )
            ->assertConflict()
            ->assertJsonPath(
                'code',
                'terminal_branch_unavailable'
            );
    }

    /**
     * @param array{
     *     terminal: BranchTerminal,
     *     device_token: string
     * } $activated
     */
    private function withTerminalHeaders(
        array $activated
    ): self {
        return $this->withHeaders([
            'X-Terminal-ID' => $activated['terminal']
                ->public_id,

            'X-Terminal-Token' => $activated['device_token'],
        ]);
    }

    /**
     * @return array{
     *     terminal: BranchTerminal,
     *     device_token: string
     * }
     */
    private function activateTerminal(
        string $name
    ): array {
        $registered =
            $this->lifecycleService()
                ->register(
                    branch: $this->createValidBranch(),

                    creator: $this->createHrd(),

                    name: $name
                );

        return $this->lifecycleService()
            ->activate(
                publicId: $registered['terminal']
                    ->public_id,

                activationCode: $registered[
                        'activation_code'
                    ]
            );
    }

    private function createAutomaticSession(
        Branch $branch,
        string $sessionDate,
        string $startTime,
        string $endTime
    ): AttendanceSession {
        $this->sessionSequence++;

        return AttendanceSession::query()->create([
            'branch_id' => $branch->id,
            'weekly_schedule_id' => null,

            'attendance_type' => AttendanceSession::TYPE_AUTO,

            'session_source' => AttendanceSession::SOURCE_AUTOMATIC,

            'automation_key' => sprintf(
                'AUTO-TERMINAL-QR:%d:%s:%03d',
                $branch->id,
                $sessionDate,
                $this->sessionSequence
            ),

            'session_date' => $sessionDate,

            'start_time' => "{$sessionDate} {$startTime}",

            'end_time' => "{$sessionDate} {$endTime}",

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
            'session_source' => 'manual',
            'automation_key' => null,
            'session_date' => $sessionDate,

            'start_time' => "{$sessionDate} 08:00:00",

            'end_time' => "{$sessionDate} 17:00:00",

            'encrypted_secret' => 'JBSWY3DPEHPK3PXP',

            'status' => 'active',
            'created_by' => $creator->id,
            'closed_at' => null,
        ]);
    }

    private function lifecycleService(): BranchTerminalLifecycleService
    {
        return $this->app->make(
            BranchTerminalLifecycleService::class
        );
    }

    private function createValidBranch(): Branch
    {
        return Branch::factory()->create([
            'latitude' => 3.5374963,
            'longitude' => 98.6844756,
            'geofence_radius' => 30.00,
            'maximum_accuracy' => 25.00,
            'status' => 'active',
        ]);
    }

    private function createHrd(): User
    {
        return User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);
    }
}
