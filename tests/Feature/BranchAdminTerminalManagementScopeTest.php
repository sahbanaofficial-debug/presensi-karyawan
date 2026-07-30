<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

final class BranchAdminTerminalManagementScopeTest extends TestCase
{
    use RefreshDatabase;

    private int $branchSequence = 0;

    private int $terminalSequence = 0;

    public function test_assigned_admin_index_and_create_only_expose_own_branch(): void
    {
        $hrd = $this->createUser('hrd');

        $ownBranch = $this->createBranch();
        $foreignBranch = $this->createBranch();

        $ownTerminal = $this->createTerminal(
            branch: $ownBranch,
            creator: $hrd,
            status: BranchTerminal::STATUS_PENDING,
            name: 'Terminal Cabang Admin'
        );

        $foreignTerminal = $this->createTerminal(
            branch: $foreignBranch,
            creator: $hrd,
            status: BranchTerminal::STATUS_ACTIVE,
            name: 'Terminal Cabang Asing'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->get(route('branch-terminals.index'))
            ->assertOk()
            ->assertViewHas(
                'terminals',
                static function (
                    LengthAwarePaginator $terminals
                ) use ($ownTerminal): bool {
                    return $terminals->total() === 1
                        && $terminals->count() === 1
                        && (int) $terminals
                            ->first()
                            ?->id
                            === (int) $ownTerminal->id;
                }
            )
            ->assertSeeText($ownTerminal->name)
            ->assertDontSeeText($foreignTerminal->name);

        $this->get(route('branch-terminals.create'))
            ->assertOk()
            ->assertViewHas(
                'branches',
                static function (
                    $branches
                ) use (
                    $ownBranch,
                    $foreignBranch
                ): bool {
                    return $branches->count() === 1
                        && $branches->contains(
                            'id',
                            $ownBranch->id
                        )
                        && ! $branches->contains(
                            'id',
                            $foreignBranch->id
                        );
                }
            )
            ->assertSeeText($ownBranch->name)
            ->assertDontSeeText($foreignBranch->name);
    }

    public function test_assigned_admin_can_register_and_open_own_terminal(): void
    {
        $branch = $this->createBranch();

        $admin = $this->createUser(
            role: 'admin',
            branch: $branch
        );

        $response = $this->actingAs($admin)
            ->post(
                route('branch-terminals.store'),
                [
                    'branch_id' => $branch->id,
                    'name' => 'Terminal Operasional Admin',
                ]
            );

        $terminal = BranchTerminal::query()
            ->where(
                'name',
                'Terminal Operasional Admin'
            )
            ->firstOrFail();

        $response
            ->assertRedirect(
                route(
                    'branch-terminals.show',
                    $terminal
                )
            )
            ->assertSessionHas('success')
            ->assertSessionHas('activation_code');

        $this->assertDatabaseHas(
            'branch_terminals',
            [
                'id' => $terminal->id,
                'branch_id' => $branch->id,
                'created_by' => $admin->id,
                'status' => BranchTerminal::STATUS_PENDING,
            ]
        );

        $this->get(
            route(
                'branch-terminals.show',
                $terminal
            )
        )
            ->assertOk()
            ->assertSeeText($terminal->name)
            ->assertSeeText($branch->name);
    }

    public function test_assigned_admin_can_renew_and_revoke_own_terminals(): void
    {
        $branch = $this->createBranch();

        $admin = $this->createUser(
            role: 'admin',
            branch: $branch
        );

        $pendingTerminal = $this->createTerminal(
            branch: $branch,
            creator: $admin,
            status: BranchTerminal::STATUS_PENDING,
            name: 'Terminal Admin Pending'
        );

        $oldActivationHash =
            $pendingTerminal->activation_code_hash;

        $this->actingAs($admin)
            ->patch(
                route(
                    'branch-terminals.renew-activation',
                    $pendingTerminal
                )
            )
            ->assertRedirect(
                route(
                    'branch-terminals.show',
                    $pendingTerminal
                )
            )
            ->assertSessionHas('success')
            ->assertSessionHas('activation_code');

        $pendingTerminal->refresh();

        $this->assertNotSame(
            $oldActivationHash,
            $pendingTerminal->activation_code_hash
        );

        $activeTerminal = $this->createTerminal(
            branch: $branch,
            creator: $admin,
            status: BranchTerminal::STATUS_ACTIVE,
            name: 'Terminal Admin Aktif'
        );

        $this->patch(
            route(
                'branch-terminals.revoke',
                $activeTerminal
            )
        )
            ->assertRedirect(
                route(
                    'branch-terminals.show',
                    $activeTerminal
                )
            )
            ->assertSessionHas('success');

        $activeTerminal->refresh();

        $this->assertSame(
            BranchTerminal::STATUS_REVOKED,
            $activeTerminal->status
        );

        $this->assertSame(
            $admin->id,
            $activeTerminal->revoked_by
        );

        $this->assertNotNull(
            $activeTerminal->revoked_at
        );
    }

    public function test_assigned_admin_cannot_access_or_operate_foreign_terminal(): void
    {
        $hrd = $this->createUser('hrd');

        $ownBranch = $this->createBranch();
        $foreignBranch = $this->createBranch();

        $foreignPending = $this->createTerminal(
            branch: $foreignBranch,
            creator: $hrd,
            status: BranchTerminal::STATUS_PENDING,
            name: 'Terminal Asing Pending'
        );

        $foreignActive = $this->createTerminal(
            branch: $foreignBranch,
            creator: $hrd,
            status: BranchTerminal::STATUS_ACTIVE,
            name: 'Terminal Asing Aktif'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $ownBranch
        );

        $oldActivationHash =
            $foreignPending->activation_code_hash;

        $this->actingAs($admin)
            ->get(
                route(
                    'branch-terminals.show',
                    $foreignPending
                )
            )
            ->assertForbidden();

        $this->patch(
            route(
                'branch-terminals.renew-activation',
                $foreignPending
            )
        )
            ->assertForbidden();

        $this->patch(
            route(
                'branch-terminals.revoke',
                $foreignActive
            )
        )
            ->assertForbidden();

        $foreignPending->refresh();
        $foreignActive->refresh();

        $this->assertSame(
            $oldActivationHash,
            $foreignPending->activation_code_hash
        );

        $this->assertSame(
            BranchTerminal::STATUS_ACTIVE,
            $foreignActive->status
        );

        $this->assertNull(
            $foreignActive->revoked_by
        );
    }

    public function test_assigned_admin_cannot_register_terminal_for_foreign_branch(): void
    {
        $ownBranch = $this->createBranch();
        $foreignBranch = $this->createBranch();

        $admin = $this->createUser(
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->post(
                route('branch-terminals.store'),
                [
                    'branch_id' => $foreignBranch->id,
                    'name' => 'Terminal Percobaan Asing',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'branch_terminals',
            [
                'branch_id' => $foreignBranch->id,
                'name' => 'Terminal Percobaan Asing',
            ]
        );
    }

    public function test_unassigned_admin_is_forbidden_from_terminal_management(): void
    {
        $hrd = $this->createUser('hrd');
        $branch = $this->createBranch();

        $pendingTerminal = $this->createTerminal(
            branch: $branch,
            creator: $hrd,
            status: BranchTerminal::STATUS_PENDING,
            name: 'Terminal Admin Tanpa Cabang'
        );

        $activeTerminal = $this->createTerminal(
            branch: $branch,
            creator: $hrd,
            status: BranchTerminal::STATUS_ACTIVE,
            name: 'Terminal Aktif Tanpa Cabang'
        );

        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get(route('branch-terminals.index'))
            ->assertForbidden();

        $this->get(route('branch-terminals.create'))
            ->assertForbidden();

        $this->post(
            route('branch-terminals.store'),
            [
                'branch_id' => $branch->id,
                'name' => 'Terminal Ditolak Tanpa Cabang',
            ]
        )
            ->assertForbidden();

        $this->get(
            route(
                'branch-terminals.show',
                $pendingTerminal
            )
        )
            ->assertForbidden();

        $this->patch(
            route(
                'branch-terminals.renew-activation',
                $pendingTerminal
            )
        )
            ->assertForbidden();

        $this->patch(
            route(
                'branch-terminals.revoke',
                $activeTerminal
            )
        )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'branch_terminals',
            2
        );
    }

    public function test_admin_assigned_to_inactive_branch_is_forbidden_from_terminal_management(): void
    {
        $hrd = $this->createUser('hrd');

        $inactiveBranch = $this->createBranch([
            'status' => 'inactive',
        ]);

        $activeBranch = $this->createBranch();

        $pendingTerminal = $this->createTerminal(
            branch: $activeBranch,
            creator: $hrd,
            status: BranchTerminal::STATUS_PENDING,
            name: 'Terminal untuk Admin Nonaktif'
        );

        $activeTerminal = $this->createTerminal(
            branch: $activeBranch,
            creator: $hrd,
            status: BranchTerminal::STATUS_ACTIVE,
            name: 'Terminal Aktif Admin Nonaktif'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $inactiveBranch
        );

        $this->actingAs($admin)
            ->get(route('branch-terminals.index'))
            ->assertForbidden();

        $this->get(route('branch-terminals.create'))
            ->assertForbidden();

        $this->post(
            route('branch-terminals.store'),
            [
                'branch_id' => $activeBranch->id,
                'name' => 'Terminal Ditolak Cabang Nonaktif',
            ]
        )
            ->assertForbidden();

        $this->get(
            route(
                'branch-terminals.show',
                $pendingTerminal
            )
        )
            ->assertForbidden();

        $this->patch(
            route(
                'branch-terminals.renew-activation',
                $pendingTerminal
            )
        )
            ->assertForbidden();

        $this->patch(
            route(
                'branch-terminals.revoke',
                $activeTerminal
            )
        )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'branch_terminals',
            2
        );
    }

    public function test_hrd_retains_cross_branch_terminal_management(): void
    {
        $hrd = $this->createUser('hrd');

        $firstBranch = $this->createBranch();
        $secondBranch = $this->createBranch();

        $firstTerminal = $this->createTerminal(
            branch: $firstBranch,
            creator: $hrd,
            status: BranchTerminal::STATUS_PENDING,
            name: 'Terminal HRD Pertama'
        );

        $secondTerminal = $this->createTerminal(
            branch: $secondBranch,
            creator: $hrd,
            status: BranchTerminal::STATUS_ACTIVE,
            name: 'Terminal HRD Kedua'
        );

        $this->actingAs($hrd)
            ->get(route('branch-terminals.index'))
            ->assertOk()
            ->assertViewHas(
                'terminals',
                static fn (
                    LengthAwarePaginator $terminals
                ): bool => $terminals->total() === 2
            )
            ->assertSeeText($firstTerminal->name)
            ->assertSeeText($secondTerminal->name);

        $this->get(route('branch-terminals.create'))
            ->assertOk()
            ->assertViewHas(
                'branches',
                static fn (
                    $branches
                ): bool => $branches->count() === 2
            )
            ->assertSeeText($firstBranch->name)
            ->assertSeeText($secondBranch->name);

        $this->get(
            route(
                'branch-terminals.show',
                $firstTerminal
            )
        )
            ->assertOk();

        $this->get(
            route(
                'branch-terminals.show',
                $secondTerminal
            )
        )
            ->assertOk();

        $this->patch(
            route(
                'branch-terminals.renew-activation',
                $firstTerminal
            )
        )
            ->assertRedirect(
                route(
                    'branch-terminals.show',
                    $firstTerminal
                )
            );

        $this->patch(
            route(
                'branch-terminals.revoke',
                $secondTerminal
            )
        )
            ->assertRedirect(
                route(
                    'branch-terminals.show',
                    $secondTerminal
                )
            );

        $this->post(
            route('branch-terminals.store'),
            [
                'branch_id' => $secondBranch->id,
                'name' => 'Terminal Baru HRD',
            ]
        )
            ->assertRedirect();
    }

    private function createUser(
        string $role,
        ?Branch $branch = null
    ): User {
        return User::factory()->create([
            'branch_id' => $branch?->id,
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
        $this->branchSequence++;

        return Branch::factory()->create(
            array_replace(
                [
                    'code' => sprintf(
                        'TRM-SCP-%03d',
                        $this->branchSequence
                    ),
                    'name' => sprintf(
                        'Cabang Terminal Scope %03d',
                        $this->branchSequence
                    ),
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
        string $name
    ): BranchTerminal {
        $this->terminalSequence++;

        $isPending =
            $status === BranchTerminal::STATUS_PENDING;

        $isActive =
            $status === BranchTerminal::STATUS_ACTIVE;

        return BranchTerminal::query()->create([
            'branch_id' => $branch->id,
            'name' => $name,
            'device_token_hash' => $isActive
                ? hash(
                    'sha256',
                    sprintf(
                        'device-scope-%03d',
                        $this->terminalSequence
                    )
                )
                : null,
            'activation_code_hash' => $isPending
                ? hash(
                    'sha256',
                    sprintf(
                        '%08d',
                        $this->terminalSequence
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
