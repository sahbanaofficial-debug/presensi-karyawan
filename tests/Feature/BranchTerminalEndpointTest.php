<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\User;
use App\Services\BranchTerminalLifecycleService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BranchTerminalEndpointTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_activation_endpoint_validates_public_id_and_activation_code(): void
    {
        $this->postJson(
            route('terminal.activate'),
            [
                'public_id' => 'bukan-uuid',
                'activation_code' => '123',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'public_id',
                'activation_code',
            ]);
    }

    public function test_valid_activation_returns_device_token_once(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-16 09:00:00',
                'Asia/Jakarta'
            )
        );

        $registered = $this->registerTerminal(
            'Terminal Endpoint Aktivasi'
        );

        $response = $this->postJson(
            route('terminal.activate'),
            [
                'public_id' => $registered['terminal']
                    ->public_id,

                'activation_code' => $registered[
                        'activation_code'
                    ],
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'code',
                'terminal_activated'
            )
            ->assertJsonPath(
                'data.terminal.status',
                BranchTerminal::STATUS_ACTIVE
            )
            ->assertJsonPath(
                'data.terminal.name',
                'Terminal Endpoint Aktivasi'
            );

        $deviceToken = (string)
            $response->json(
                'data.device_token'
            );

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $deviceToken
        );

        $terminal = $registered[
            'terminal'
        ]->fresh();

        $this->assertTrue(
            $terminal->isActive()
        );

        $this->assertSame(
            hash('sha256', $deviceToken),
            DB::table('branch_terminals')
                ->where('id', $terminal->id)
                ->value('device_token_hash')
        );

        $this->postJson(
            route('terminal.activate'),
            [
                'public_id' => $terminal->public_id,

                'activation_code' => $registered[
                        'activation_code'
                    ],
            ]
        )
            ->assertConflict()
            ->assertJsonPath(
                'code',
                'terminal_not_pending'
            )
            ->assertJsonMissingPath(
                'data.device_token'
            );
    }

    public function test_expired_activation_code_returns_gone(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-17 09:00:00',
                'Asia/Jakarta'
            )
        );

        $registered = $this->registerTerminal(
            'Terminal Aktivasi Kedaluwarsa'
        );

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-17 09:15:00',
                'Asia/Jakarta'
            )
        );

        $this->postJson(
            route('terminal.activate'),
            [
                'public_id' => $registered['terminal']
                    ->public_id,

                'activation_code' => $registered[
                        'activation_code'
                    ],
            ]
        )
            ->assertGone()
            ->assertJsonPath(
                'code',
                'activation_expired'
            );

        $this->assertTrue(
            $registered['terminal']
                ->fresh()
                ->isPending()
        );
    }

    public function test_identity_endpoint_requires_terminal_headers(): void
    {
        $this->getJson(
            route('terminal.identity')
        )
            ->assertUnauthorized()
            ->assertJsonPath(
                'code',
                'terminal_credentials_missing'
            );
    }

    public function test_identity_endpoint_rejects_invalid_device_token(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-18 09:00:00',
                'Asia/Jakarta'
            )
        );

        $activated = $this->activateTerminal(
            'Terminal Token Salah'
        );

        $this->withHeaders([
            'X-Terminal-ID' => $activated['terminal']
                ->public_id,

            'X-Terminal-Token' => str_repeat('0', 64),
        ])
            ->getJson(
                route('terminal.identity')
            )
            ->assertUnauthorized()
            ->assertJsonPath(
                'code',
                'device_token_invalid'
            );
    }

    public function test_active_terminal_can_access_identity_and_updates_last_seen(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-19 09:00:00',
                'Asia/Jakarta'
            )
        );

        $activated = $this->activateTerminal(
            'Terminal Identitas Aktif'
        );

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-19 09:05:00',
                'Asia/Jakarta'
            )
        );

        $this->withHeaders([
            'X-Terminal-ID' => $activated['terminal']
                ->public_id,

            'X-Terminal-Token' => $activated['device_token'],
        ])
            ->getJson(
                route('terminal.identity')
            )
            ->assertOk()
            ->assertJsonPath(
                'code',
                'terminal_authenticated'
            )
            ->assertJsonPath(
                'data.terminal.public_id',
                $activated['terminal']
                    ->public_id
            )
            ->assertJsonPath(
                'data.terminal.branch.code',
                $activated['terminal']
                    ->branch
                    ->code
            )
            ->assertJsonMissingPath(
                'data.device_token'
            );

        $this->assertSame(
            '2026-11-19 09:05:00',
            $activated['terminal']
                ->fresh()
                ->last_seen_at
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_revoked_terminal_cannot_access_identity(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-20 09:00:00',
                'Asia/Jakarta'
            )
        );

        $activated = $this->activateTerminal(
            'Terminal Endpoint Revoked'
        );

        $revoker = $this->createHrd();

        $this->service()->revoke(
            branchTerminal: $activated['terminal'],

            revoker: $revoker
        );

        $this->withHeaders([
            'X-Terminal-ID' => $activated['terminal']
                ->public_id,

            'X-Terminal-Token' => $activated['device_token'],
        ])
            ->getJson(
                route('terminal.identity')
            )
            ->assertForbidden()
            ->assertJsonPath(
                'code',
                'terminal_inactive'
            );
    }

    /**
     * @return array{
     *     terminal: BranchTerminal,
     *     activation_code: string
     * }
     */
    private function registerTerminal(
        string $name
    ): array {
        return $this->service()->register(
            branch: $this->createValidBranch(),
            creator: $this->createHrd(),
            name: $name
        );
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
            $this->registerTerminal($name);

        return $this->service()->activate(
            publicId: $registered['terminal']
                ->public_id,

            activationCode: $registered['activation_code']
        );
    }

    private function service(): BranchTerminalLifecycleService
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
