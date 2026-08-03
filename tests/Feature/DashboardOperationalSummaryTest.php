<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardOperationalSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_hrd_dashboard_displays_operational_summary_and_charts(): void
    {
        $hrd = User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $firstBranch = Branch::factory()->create([
            'code' => 'DASH-01',
            'name' => 'Cabang Dashboard Satu',
            'status' => 'active',
        ]);

        $secondBranch = Branch::factory()->create([
            'code' => 'DASH-02',
            'name' => 'Cabang Dashboard Dua',
            'status' => 'active',
        ]);

        $this->createEmployee(
            $firstBranch,
            'DASH-EMP-001',
            'Karyawan Dashboard Satu'
        );

        $this->createEmployee(
            $secondBranch,
            'DASH-EMP-002',
            'Karyawan Dashboard Dua'
        );

        $this->actingAs($hrd)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHas(
                'dashboardStats',
                static fn (?array $stats): bool => $stats !== null
                    && $stats['active_branches'] === 2
                    && $stats['active_employees'] === 2
                    && $stats['active_terminals'] === 0
                    && $stats['today_attendances'] === 0
            )
            ->assertSee('Ringkasan operasional')
            ->assertSee('Komposisi presensi hari ini')
            ->assertSee('Transaksi per cabang')
            ->assertSee('Transaksi terbaru')
            ->assertSee('Seluruh cabang');
    }

    public function test_admin_dashboard_summary_is_scoped_to_assigned_branch(): void
    {
        $assignedBranch = Branch::factory()->create([
            'code' => 'DASH-ADMIN-01',
            'name' => 'Cabang Admin Dashboard',
            'status' => 'active',
        ]);

        $otherBranch = Branch::factory()->create([
            'code' => 'DASH-ADMIN-02',
            'name' => 'Cabang Lain Dashboard',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'branch_id' => $assignedBranch->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->createEmployee(
            $assignedBranch,
            'DASH-ADMIN-EMP-001',
            'Karyawan Cabang Admin'
        );

        $this->createEmployee(
            $otherBranch,
            'DASH-ADMIN-EMP-002',
            'Karyawan Cabang Lain'
        );

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas(
                'dashboardStats',
                static fn (?array $stats): bool => $stats !== null
                    && $stats['active_branches'] === 1
                    && $stats['active_employees'] === 1
            )
            ->assertViewHas(
                'branchAttendance',
                static fn ($branches): bool => $branches->count() === 1
                    && $branches->first()?->is($assignedBranch)
            )
            ->assertSee('Cabang Admin Dashboard')
            ->assertDontSee('Cabang Lain Dashboard');
    }

    private function createEmployee(
        Branch $branch,
        string $employeeNumber,
        string $fullName
    ): Employee {
        $user = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => $employeeNumber,
            'full_name' => $fullName,
            'position' => 'Karyawan',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);
    }
}
