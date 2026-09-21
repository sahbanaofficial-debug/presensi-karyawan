<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BranchTerminalOperationalPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_operational_page_is_public_and_does_not_require_user_session(): void
    {
        $this->get(
            route('terminal.display')
        )
            ->assertOk()
            ->assertSee(
                'Terminal Presensi'
            )
            ->assertSee(
                'Aktivasi Perangkat'
            )
            ->assertSee(
                'Aktifkan Perangkat'
            )
            ->assertSee('terminal-countdown-progress')
            ->assertSee('Kode diperbarui otomatis')
            ->assertSee('logo-pt-gadai-ogan-baru.png');
    }

    public function test_page_exposes_terminal_api_endpoints_and_security_headers_contract(): void
    {
        $source = $this->operationalViewSource();

        foreach (
            [
                "route('terminal.activate')",
                "route('terminal.identity')",
                "route('terminal.qr-payload')",
                "'X-Terminal-ID'",
                "'X-Terminal-Token'",
                "credentials: 'omit'",
            ] as $contract
        ) {
            $this->assertStringContainsString(
                $contract,
                $source
            );
        }
    }

    public function test_page_persists_and_can_clear_device_credentials_without_rendering_token(): void
    {
        $source = $this->operationalViewSource();

        foreach (
            [
                'presensi.branch-terminal.credentials.v1',
                'window.localStorage.setItem',
                'window.localStorage.removeItem',
                'Lepas Perangkat',
            ] as $contract
        ) {
            $this->assertStringContainsString(
                $contract,
                $source
            );
        }

        foreach (
            [
                'device_token_hash',
                'activation_code_hash',
                'console.log(',
            ] as $forbidden
        ) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source
            );
        }
    }

    public function test_page_contains_dynamic_qr_rendering_and_automatic_refresh_contract(): void
    {
        $source = $this->operationalViewSource();

        foreach (
            [
                'qrcodejs@1.0.0/qrcode.min.js',
                'new window.QRCode',
                'data.available',
                'data.qr_payload',
                'data.refresh_after',
                'data.seconds_remaining',
                'fetchQrPayload',
                'Menunggu sesi presensi otomatis.',
                'stopForInvalidCredential',
            ] as $contract
        ) {
            $this->assertStringContainsString(
                $contract,
                $source
            );
        }
    }

    public function test_activated_device_can_complete_identity_and_dynamic_qr_flow(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-15 08:45:05',
                'Asia/Jakarta'
            )
        );

        $creator = $this->createHrd();
        $branch = $this->createBranch();

        $activationCode = '47382910';

        $terminal = $this->createPendingTerminal(
            branch: $branch,
            creator: $creator,
            activationCode: $activationCode
        );

        $activationResponse =
            $this->postJson(
                route('terminal.activate'),
                [
                    'public_id' => $terminal->public_id,

                    'activation_code' => $activationCode,
                ]
            )
                ->assertCreated()
                ->assertJsonFragment([
                    'public_id' => $terminal->public_id,
                ]);

        $deviceToken =
            $activationResponse->json(
                'data.device_token'
            )
            ?? $activationResponse->json(
                'device_token'
            );

        $this->assertIsString(
            $deviceToken
        );

        $this->assertNotSame(
            '',
            trim($deviceToken)
        );

        $headers = [
            'X-Terminal-ID' => $terminal->public_id,

            'X-Terminal-Token' => $deviceToken,
        ];

        $this->withHeaders($headers)
            ->getJson(
                route('terminal.identity')
            )
            ->assertOk()
            ->assertJsonFragment([
                'public_id' => $terminal->public_id,
            ]);

        $session =
            AttendanceSession::query()->create([
                'branch_id' => $branch->id,
                'weekly_schedule_id' => null,

                'attendance_type' => AttendanceSession::TYPE_AUTO,

                'session_source' => AttendanceSession::SOURCE_AUTOMATIC,

                'automation_key' => 'AUTO-OPERATIONAL-PAGE-2026-12-15',

                'session_date' => '2026-12-15',

                'start_time' => '2026-12-15 08:00:00',

                'end_time' => '2026-12-15 18:00:00',

                'encrypted_secret' => 'JBSWY3DPEHPK3PXP',

                'status' => 'active',
                'created_by' => null,
                'closed_at' => null,
            ]);

        $qrResponse = $this
            ->withHeaders($headers)
            ->getJson(
                route(
                    'terminal.qr-payload'
                )
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

        $qrPayload = $qrResponse->json(
            'data.qr_payload'
        );

        $this->assertIsString(
            $qrPayload
        );

        $this->assertStringContainsString(
            $session->public_id,
            $qrPayload
        );

        $this->assertStringContainsString(
            $terminal->public_id,
            $qrPayload
        );
    }

    public function test_device_without_current_session_receives_waiting_state_and_revoked_device_is_rejected(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-16 09:00:00',
                'Asia/Jakarta'
            )
        );

        $creator = $this->createHrd();
        $branch = $this->createBranch();

        $activationCode = '83726194';

        $terminal = $this->createPendingTerminal(
            branch: $branch,
            creator: $creator,
            activationCode: $activationCode
        );

        $activationResponse =
            $this->postJson(
                route('terminal.activate'),
                [
                    'public_id' => $terminal->public_id,

                    'activation_code' => $activationCode,
                ]
            )->assertCreated();

        $deviceToken =
            $activationResponse->json(
                'data.device_token'
            )
            ?? $activationResponse->json(
                'device_token'
            );

        $this->assertIsString(
            $deviceToken
        );

        $headers = [
            'X-Terminal-ID' => $terminal->public_id,

            'X-Terminal-Token' => $deviceToken,
        ];

        $this->withHeaders($headers)
            ->getJson(
                route(
                    'terminal.qr-payload'
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'code',
                'terminal_qr_unavailable'
            )
            ->assertJsonPath(
                'data.available',
                false
            )
            ->assertJsonPath(
                'data.qr_payload',
                null
            );

        $terminal->refresh();

        $terminal->update([
            'status' => BranchTerminal::STATUS_REVOKED,

            'device_token_hash' => null,
            'revoked_by' => $creator->id,
            'revoked_at' => now(),
        ]);

        $this->withHeaders($headers)
            ->getJson(
                route('terminal.identity')
            )
            ->assertForbidden();

        $this->withHeaders($headers)
            ->getJson(
                route(
                    'terminal.qr-payload'
                )
            )
            ->assertForbidden();
    }

    public function test_terminal_page_accepts_laravel_uuid_v7_public_identifier(): void
    {
        $source = $this->operationalViewSource();

        $uuidPattern =
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        $this->assertStringContainsString(
            $uuidPattern,
            $source
        );

        $this->assertStringNotContainsString(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $source
        );

        $this->assertMatchesRegularExpression(
            $uuidPattern,
            '019fae40-cbbd-702d-bc28-1c019381e372'
        );
    }

    private function operationalViewSource(): string
    {
        $source = file_get_contents(
            resource_path(
                'views/terminal/index.blade.php'
            )
        );

        $this->assertIsString(
            $source
        );

        return $source;
    }

    private function createHrd(): User
    {
        return User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);
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

    private function createPendingTerminal(
        Branch $branch,
        User $creator,
        string $activationCode
    ): BranchTerminal {
        return BranchTerminal::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Terminal Halaman Operasional',
            'device_token_hash' => null,

            'activation_code_hash' => hash(
                'sha256',
                $activationCode
            ),

            'activation_expires_at' => now()->addMinutes(15),

            'activated_at' => null,
            'last_seen_at' => null,

            'status' => BranchTerminal::STATUS_PENDING,

            'created_by' => $creator->id,
            'revoked_by' => null,
            'revoked_at' => null,
        ]);
    }
}
