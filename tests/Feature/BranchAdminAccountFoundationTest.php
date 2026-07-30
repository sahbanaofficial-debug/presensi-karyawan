<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class BranchAdminAccountFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_supports_nullable_branch_assignment(): void
    {
        $this->assertTrue(
            Schema::hasColumn(
                'users',
                'branch_id'
            )
        );
    }

    public function test_admin_account_can_be_assigned_to_one_branch(): void
    {
        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'branch_id' => $branch->getKey(),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->assertTrue(
            $admin->branch
                ->is($branch)
        );

        $this->assertTrue(
            $branch->administrators()
                ->whereKey($admin->getKey())
                ->exists()
        );
    }

    public function test_hrd_account_can_remain_without_branch_assignment(): void
    {
        $hrd = User::factory()->create([
            'branch_id' => null,
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $this->assertNull($hrd->branch_id);
        $this->assertNull($hrd->branch);
    }
}
