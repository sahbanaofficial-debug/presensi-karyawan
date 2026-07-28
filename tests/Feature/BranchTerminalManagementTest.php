<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BranchTerminalManagementTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_guest_is_redirected_from_terminal_management(): void
    {
        $this->get(
            route('branch-terminals.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_and_employee_cannot_manage_terminals(): void
    {
        $admin = $this->createUser(
            'admin'
        );

        $employee =
            $this->createActiveEmployeeUser();

        foreach (
            [
                $admin,
                $employee,
            ] as $user
        ) {
            $this->actingAs($user)
                ->get(
                    route(
                        'branch-terminals.index'
                    )
                )
                ->assertForbidden();

            $this->actingAs($user)
                ->post(
                    route(
                        'branch-terminals.store'
                    ),
                    [
                        'branch_id' => 1,
                        'name' => 'Terminal Ditolak',
                    ]
                )
                ->assertForbidden();
        }
    }

    public function test_hrd_can_open_pages_and_only_valid_branches_are_registrable(): void
    {
        $hrd = $this->createUser('hrd');

        $validBranch = $this->createBranch();

        $invalidBranch = $this->createBranch([
            'status' => 'inactive',
        ]);

        $this->actingAs($hrd)
            ->get(
                route(
                    'branch-terminals.index'
                )
            )
            ->assertOk()
            ->assertSee('Terminal Cabang')
            ->assertSee('Daftarkan Terminal');

        $this->actingAs($hrd)
            ->get(
                route(
                    'branch-terminals.create'
                )
            )
            ->assertOk()
            ->assertSee($validBranch->name)
            ->assertDontSee(
                $invalidBranch->name
            );
    }

    public function test_index_supports_filters_and_fifteen_items_per_page(): void
    {
        $hrd = $this->createUser('hrd');

        $firstBranch = $this->createBranch();
        $secondBranch = $this->createBranch();

        for ($index = 1; $index <= 16; $index++) {
            $this->createTerminal(
                branch: $firstBranch,
                creator: $hrd,
                status: BranchTerminal::STATUS_PENDING,
                name: sprintf(
                    'Terminal Daftar %02d',
                    $index
                )
            );
        }

        $filteredTerminal =
            $this->createTerminal(
                branch: $secondBranch,
                creator: $hrd,
                status: BranchTerminal::STATUS_ACTIVE,
                name: 'Terminal Khusus Filter'
            );

        $this->actingAs($hrd)
            ->get(
                route(
                    'branch-terminals.index'
                )
            )
            ->assertOk()
            ->assertViewHas(
                'terminals',
                function (
                    LengthAwarePaginator $paginator
                ): bool {
                    return $paginator->count() === 15
                        && $paginator->total() === 17;
                }
            );

        $this->actingAs($hrd)
            ->get(
                route(
                    'branch-terminals.index',
                    [
                        'search' => 'Khusus',
                        'status' => 'active',

                        'branch_id' => $secondBranch->id,
                    ]
                )
            )
            ->assertOk()
            ->assertSee(
                $filteredTerminal->name
            )
            ->assertDontSee(
                'Terminal Daftar 01'
            )
            ->assertViewHas(
                'terminals',
                function (
                    LengthAwarePaginator $paginator
                ): bool {
                    return $paginator->total() === 1;
                }
            );
    }

    public function test_hrd_can_register_pending_terminal_and_receive_one_time_code(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-10 09:00:00',
                'Asia/Jakarta'
            )
        );

        $hrd = $this->createUser('hrd');
        $branch = $this->createBranch();

        $response = $this->actingAs($hrd)
            ->post(
                route(
                    'branch-terminals.store'
                ),
                [
                    'branch_id' => $branch->id,

                    'name' => '  Terminal   Lobby Utama  ',
                ]
            );

        $terminal =
            BranchTerminal::query()->firstOrFail();

        $response
            ->assertRedirect(
                route(
                    'branch-terminals.show',
                    $terminal
                )
            )
            ->assertSessionHas('success')
            ->assertSessionHas(
                'activation_code',
                function (mixed $value): bool {
                    return is_string($value)
                        && preg_match(
                            '/^\d{8}$/',
                            $value
                        ) === 1;
                }
            );

        $activationCode = session(
            'activation_code'
        );

        $this->assertIsString(
            $activationCode
        );

        $this->assertSame(
            'Terminal Lobby Utama',
            $terminal->name
        );

        $this->assertTrue(
            $terminal->isPending()
        );

        $this->assertSame(
            hash(
                'sha256',
                $activationCode
            ),
            DB::table('branch_terminals')
                ->where(
                    'id',
                    $terminal->id
                )
                ->value(
                    'activation_code_hash'
                )
        );

        $this->assertNull(
            $terminal->device_token_hash
        );

        $this->assertSame(
            '2026-12-10 09:15:00',
            $terminal
                ->activation_expires_at
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_registration_validates_name_and_rejects_unavailable_branch(): void
    {
        $hrd = $this->createUser('hrd');
        $validBranch = $this->createBranch();

        $this->actingAs($hrd)
            ->post(
                route(
                    'branch-terminals.store'
                ),
                [
                    'branch_id' => $validBranch->id,

                    'name' => 'A',
                ]
            )
            ->assertSessionHasErrors(
                'name'
            );

        $inactiveBranch =
            $this->createBranch([
                'status' => 'inactive',
            ]);

        $this->actingAs($hrd)
            ->post(
                route(
                    'branch-terminals.store'
                ),
                [
                    'branch_id' => $inactiveBranch->id,

                    'name' => 'Terminal Cabang Nonaktif',
                ]
            )
            ->assertSessionHasErrors(
                'terminal'
            );

        $this->assertDatabaseCount(
            'branch_terminals',
            0
        );
    }

    public function test_show_displays_audit_details_without_exposing_hashes(): void
    {
        $hrd = $this->createUser('hrd');
        $branch = $this->createBranch();

        $terminal = $this->createTerminal(
            branch: $branch,
            creator: $hrd,
            status: BranchTerminal::STATUS_PENDING,
            name: 'Terminal Detail Aman'
        );

        $deviceHash = hash(
            'sha256',
            'device-secret'
        );

        $activationHash = hash(
            'sha256',
            '12345678'
        );

        $terminal->update([
            'device_token_hash' => $deviceHash,

            'activation_code_hash' => $activationHash,
        ]);

        $this->actingAs($hrd)
            ->get(
                route(
                    'branch-terminals.show',
                    $terminal
                )
            )
            ->assertOk()
            ->assertSee($terminal->name)
            ->assertSee($terminal->public_id)
            ->assertSee($branch->name)
            ->assertSee($hrd->name)
            ->assertDontSee($deviceHash)
            ->assertDontSee($activationHash);
    }

    public function test_pending_terminal_can_renew_activation_but_active_terminal_cannot(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-11 10:00:00',
                'Asia/Jakarta'
            )
        );

        $hrd = $this->createUser('hrd');
        $branch = $this->createBranch();

        $terminal = $this->createTerminal(
            branch: $branch,
            creator: $hrd,
            status: BranchTerminal::STATUS_PENDING
        );

        $oldHash =
            $terminal->activation_code_hash;

        $response = $this->actingAs($hrd)
            ->patch(
                route(
                    'branch-terminals.renew-activation',
                    $terminal
                )
            );

        $terminal->refresh();

        $response
            ->assertRedirect(
                route(
                    'branch-terminals.show',
                    $terminal
                )
            )
            ->assertSessionHas('success')
            ->assertSessionHas(
                'activation_code',
                function (mixed $value): bool {
                    return is_string($value)
                        && preg_match(
                            '/^\d{8}$/',
                            $value
                        ) === 1;
                }
            );

        $this->assertNotSame(
            $oldHash,
            $terminal->activation_code_hash
        );

        $this->assertSame(
            '2026-12-11 10:15:00',
            $terminal
                ->activation_expires_at
                ->format('Y-m-d H:i:s')
        );

        $terminal->update([
            'status' => BranchTerminal::STATUS_ACTIVE,

            'activation_code_hash' => null,
            'activation_expires_at' => null,

            'device_token_hash' => hash(
                'sha256',
                'active-device'
            ),

            'activated_at' => now(),
        ]);

        $this->actingAs($hrd)
            ->patch(
                route(
                    'branch-terminals.renew-activation',
                    $terminal
                )
            )
            ->assertRedirect(
                route(
                    'branch-terminals.show',
                    $terminal
                )
            )
            ->assertSessionHas('error');
    }

    public function test_hrd_can_revoke_terminal_and_repeat_revoke_is_rejected(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-12-12 11:00:00',
                'Asia/Jakarta'
            )
        );

        $hrd = $this->createUser('hrd');
        $branch = $this->createBranch();

        $terminal = $this->createTerminal(
            branch: $branch,
            creator: $hrd,
            status: BranchTerminal::STATUS_ACTIVE
        );

        $terminal->update([
            'device_token_hash' => hash(
                'sha256',
                'device-token'
            ),

            'activated_at' => now()->subHour(),

            'last_seen_at' => now(),
        ]);

        $this->actingAs($hrd)
            ->patch(
                route(
                    'branch-terminals.revoke',
                    $terminal
                )
            )
            ->assertRedirect(
                route(
                    'branch-terminals.show',
                    $terminal
                )
            )
            ->assertSessionHas('success');

        $terminal->refresh();

        $this->assertTrue(
            $terminal->isRevoked()
        );

        $this->assertSame(
            $hrd->id,
            $terminal->revoked_by
        );

        $this->assertSame(
            '2026-12-12 11:00:00',
            $terminal
                ->revoked_at
                ->format('Y-m-d H:i:s')
        );

        $this->assertNull(
            $terminal->device_token_hash
        );

        $this->assertNull(
            $terminal->activation_code_hash
        );

        $this->actingAs($hrd)
            ->patch(
                route(
                    'branch-terminals.revoke',
                    $terminal
                )
            )
            ->assertRedirect(
                route(
                    'branch-terminals.show',
                    $terminal
                )
            )
            ->assertSessionHas('error');
    }

    private function createUser(
        string $role
    ): User {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function createActiveEmployeeUser(): User
    {
        $user = $this->createUser(
            'employee'
        );

        $branch = $this->createBranch();

        $this->sequence++;

        Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,

            'employee_number' => sprintf(
                'EMP-TERM-MGMT-%03d',
                $this->sequence
            ),

            'full_name' => 'Karyawan Otorisasi Terminal',

            'position' => 'Karyawan',
            'phone_number' => null,

            'employment_status' => 'active',
        ]);

        $this->assertTrue(
            $user->fresh()
                ->hasActiveEmployeeProfile()
        );

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBranch(
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

    private function createTerminal(
        Branch $branch,
        User $creator,
        string $status,
        ?string $name = null
    ): BranchTerminal {
        $this->sequence++;

        $isPending =
            $status ===
                BranchTerminal::STATUS_PENDING;

        $isActive =
            $status ===
                BranchTerminal::STATUS_ACTIVE;

        return BranchTerminal::query()->create([
            'branch_id' => $branch->id,

            'name' => $name ?? sprintf(
                'Terminal Management %03d',
                $this->sequence
            ),

            'device_token_hash' => $isActive
                    ? hash(
                        'sha256',
                        "device-{$this->sequence}"
                    )
                    : null,

            'activation_code_hash' => $isPending
                    ? hash(
                        'sha256',
                        sprintf(
                            '%08d',
                            (10000000 + $this->sequence)
                        )
                    )
                    : null,

            'activation_expires_at' => $isPending
                    ? now()->addMinutes(15)
                    : null,

            'activated_at' => $isActive
                    ? now()
                    : null,

            'last_seen_at' => $isActive
                    ? now()
                    : null,

            'status' => $status,
            'created_by' => $creator->id,

            'revoked_by' => null,
            'revoked_at' => null,
        ]);
    }
}
