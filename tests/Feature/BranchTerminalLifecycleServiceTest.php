<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\BranchTerminalLifecycleException;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchTerminalLifecycleService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class BranchTerminalLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_registration_creates_pending_terminal_and_returns_one_time_activation_code(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-09 09:00:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createValidBranch();
        $creator = $this->createHrd();

        $result = $this->service()->register(
            branch: $branch,
            creator: $creator,
            name: '  Terminal   Lobby Utama  '
        );

        $terminal = $result['terminal'];
        $activationCode =
            $result['activation_code'];

        $this->assertMatchesRegularExpression(
            '/^\d{8}$/',
            $activationCode
        );

        $this->assertTrue(
            Str::isUuid($terminal->public_id)
        );

        $this->assertTrue(
            $terminal->isPending()
        );

        $this->assertSame(
            'Terminal Lobby Utama',
            $terminal->name
        );

        $this->assertSame(
            '2026-11-09 09:15:00',
            $terminal
                ->activation_expires_at
                ->format('Y-m-d H:i:s')
        );

        $storedHash = DB::table(
            'branch_terminals'
        )
            ->where('id', $terminal->id)
            ->value('activation_code_hash');

        $this->assertSame(
            hash('sha256', $activationCode),
            $storedHash
        );

        $this->assertNotSame(
            $activationCode,
            $storedHash
        );

        $this->assertNull(
            $terminal->device_token_hash
        );
    }

    public function test_registration_rejects_inactive_or_invalid_branch(): void
    {
        $branch = $this->createValidBranch([
            'status' => 'inactive',
        ]);

        $creator = $this->createHrd();

        try {
            $this->service()->register(
                branch: $branch,
                creator: $creator,
                name: 'Terminal Cabang Tidak Aktif'
            );

            $this->fail(
                'BranchTerminalLifecycleException tidak dilempar.'
            );
        } catch (
            BranchTerminalLifecycleException $exception
        ) {
            $this->assertSame(
                'branch_unavailable',
                $exception->reason
            );
        }

        $this->assertDatabaseCount(
            'branch_terminals',
            0
        );
    }

    public function test_pending_terminal_can_receive_new_activation_code(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-10 09:00:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createValidBranch();
        $creator = $this->createHrd();

        $registered = $this->service()->register(
            branch: $branch,
            creator: $creator,
            name: 'Terminal Perpanjangan'
        );

        $firstCode =
            $registered['activation_code'];

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-10 09:10:00',
                'Asia/Jakarta'
            )
        );

        $renewed =
            $this->service()->renewActivation(
                $registered['terminal']
            );

        $secondCode =
            $renewed['activation_code'];

        $this->assertNotSame(
            $firstCode,
            $secondCode
        );

        $this->assertSame(
            '2026-11-10 09:25:00',
            $renewed['terminal']
                ->activation_expires_at
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            hash('sha256', $secondCode),
            DB::table('branch_terminals')
                ->where(
                    'id',
                    $renewed['terminal']->id
                )
                ->value(
                    'activation_code_hash'
                )
        );
    }

    public function test_activation_rejects_invalid_and_expired_codes_without_changing_state(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-11 09:00:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createValidBranch();
        $creator = $this->createHrd();

        $registered = $this->service()->register(
            branch: $branch,
            creator: $creator,
            name: 'Terminal Validasi Aktivasi'
        );

        try {
            $this->service()->activate(
                publicId: $registered['terminal']
                    ->public_id,

                activationCode: '00000000'
            );

            $this->fail(
                'Kode aktivasi salah seharusnya ditolak.'
            );
        } catch (
            BranchTerminalLifecycleException $exception
        ) {
            $this->assertSame(
                'activation_code_invalid',
                $exception->reason
            );
        }

        $this->assertTrue(
            $registered['terminal']
                ->fresh()
                ->isPending()
        );

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-11 09:15:00',
                'Asia/Jakarta'
            )
        );

        try {
            $this->service()->activate(
                publicId: $registered['terminal']
                    ->public_id,

                activationCode: $registered[
                        'activation_code'
                    ]
            );

            $this->fail(
                'Kode aktivasi kedaluwarsa seharusnya ditolak.'
            );
        } catch (
            BranchTerminalLifecycleException $exception
        ) {
            $this->assertSame(
                'activation_expired',
                $exception->reason
            );
        }

        $terminal = $registered[
            'terminal'
        ]->fresh();

        $this->assertTrue(
            $terminal->isPending()
        );

        $this->assertNull(
            $terminal->device_token_hash
        );
    }

    public function test_valid_activation_returns_device_token_and_clears_activation_credential(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-12 09:00:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createValidBranch();
        $creator = $this->createHrd();

        $registered = $this->service()->register(
            branch: $branch,
            creator: $creator,
            name: 'Terminal Aktivasi Berhasil'
        );

        $activated = $this->service()->activate(
            publicId: $registered['terminal']
                ->public_id,

            activationCode: $registered['activation_code']
        );

        $terminal = $activated['terminal'];
        $deviceToken =
            $activated['device_token'];

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $deviceToken
        );

        $this->assertTrue(
            $terminal->isActive()
        );

        $this->assertSame(
            hash('sha256', $deviceToken),
            DB::table('branch_terminals')
                ->where('id', $terminal->id)
                ->value('device_token_hash')
        );

        $this->assertNull(
            $terminal->activation_code_hash
        );

        $this->assertNull(
            $terminal
                ->activation_expires_at
        );

        $this->assertSame(
            '2026-11-12 09:00:00',
            $terminal
                ->activated_at
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-11-12 09:00:00',
            $terminal
                ->last_seen_at
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_authentication_validates_device_token_and_updates_last_seen(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-13 09:00:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createValidBranch();
        $creator = $this->createHrd();

        $registered = $this->service()->register(
            branch: $branch,
            creator: $creator,
            name: 'Terminal Autentikasi'
        );

        $activated = $this->service()->activate(
            publicId: $registered['terminal']
                ->public_id,

            activationCode: $registered['activation_code']
        );

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-13 09:05:00',
                'Asia/Jakarta'
            )
        );

        $authenticated =
            $this->service()->authenticate(
                publicId: $activated['terminal']
                    ->public_id,

                deviceToken: $activated['device_token']
            );

        $this->assertSame(
            '2026-11-13 09:05:00',
            $authenticated
                ->last_seen_at
                ->format('Y-m-d H:i:s')
        );

        try {
            $this->service()->authenticate(
                publicId: $activated['terminal']
                    ->public_id,

                deviceToken: str_repeat(
                    '0',
                    64
                )
            );

            $this->fail(
                'Token perangkat salah seharusnya ditolak.'
            );
        } catch (
            BranchTerminalLifecycleException $exception
        ) {
            $this->assertSame(
                'device_token_invalid',
                $exception->reason
            );
        }
    }

    public function test_revoke_clears_credentials_and_blocks_future_authentication(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-14 09:00:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createValidBranch();
        $creator = $this->createHrd();
        $revoker = $this->createHrd();

        $registered = $this->service()->register(
            branch: $branch,
            creator: $creator,
            name: 'Terminal Pencabutan'
        );

        $activated = $this->service()->activate(
            publicId: $registered['terminal']
                ->public_id,

            activationCode: $registered['activation_code']
        );

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-11-14 09:10:00',
                'Asia/Jakarta'
            )
        );

        $revoked = $this->service()->revoke(
            branchTerminal: $activated['terminal'],

            revoker: $revoker
        );

        $this->assertTrue(
            $revoked->isRevoked()
        );

        $this->assertNull(
            $revoked->device_token_hash
        );

        $this->assertSame(
            $revoker->id,
            $revoked->revoked_by
        );

        $this->assertSame(
            '2026-11-14 09:10:00',
            $revoked
                ->revoked_at
                ->format('Y-m-d H:i:s')
        );

        try {
            $this->service()->authenticate(
                publicId: $revoked->public_id,

                deviceToken: $activated['device_token']
            );

            $this->fail(
                'Terminal revoked seharusnya tidak dapat diautentikasi.'
            );
        } catch (
            BranchTerminalLifecycleException $exception
        ) {
            $this->assertSame(
                'terminal_inactive',
                $exception->reason
            );
        }
    }

    private function service(): BranchTerminalLifecycleService
    {
        return $this->app->make(
            BranchTerminalLifecycleService::class
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createValidBranch(
        array $overrides = []
    ): Branch {
        return Branch::factory()->create(
            array_merge(
                [
                    'latitude' => 3.5374963,
                    'longitude' => 98.6844756,
                    'geofence_radius' => 30.00,
                    'maximum_accuracy' => 25.00,
                    'status' => 'active',
                ],
                $overrides
            )
        );
    }

    private function createHrd(): User
    {
        return User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);
    }
}
