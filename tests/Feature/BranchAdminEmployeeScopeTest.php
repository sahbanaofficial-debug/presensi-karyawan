<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class BranchAdminEmployeeScopeTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Presensi123!';

    public function test_assigned_admin_index_is_scoped_to_own_branch_and_ignores_foreign_filter(): void
    {
        $ownBranch = $this->createBranch('CB01');
        $otherBranch = $this->createBranch('CB02');

        $this->createEmployee(
            branch: $ownBranch,
            employeeNumber: 'KRY-OWN',
            email: 'own@employee.test',
            fullName: 'Karyawan Cabang Sendiri'
        );

        $this->createEmployee(
            branch: $otherBranch,
            employeeNumber: 'KRY-OTHER',
            email: 'other@employee.test',
            fullName: 'Karyawan Cabang Lain'
        );

        $admin = $this->createUser(
            email: 'admin.scope@employee.test',
            name: 'Admin Cabang',
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->get(route('employees.index', [
                'branch_id' => $otherBranch->id,
            ]))
            ->assertOk()
            ->assertSeeText('Karyawan Cabang Sendiri')
            ->assertDontSeeText('Karyawan Cabang Lain')
            ->assertSeeText('Kantor CB01')
            ->assertDontSeeText('Kantor CB02');
    }

    public function test_assigned_admin_can_open_own_employee_but_not_another_branch_employee(): void
    {
        $ownBranch = $this->createBranch('CB01');
        $otherBranch = $this->createBranch('CB02');

        $ownEmployee = $this->createEmployee(
            branch: $ownBranch,
            employeeNumber: 'KRY-OWN',
            email: 'own@employee.test',
            fullName: 'Karyawan Cabang Sendiri'
        );

        $otherEmployee = $this->createEmployee(
            branch: $otherBranch,
            employeeNumber: 'KRY-OTHER',
            email: 'other@employee.test',
            fullName: 'Karyawan Cabang Lain'
        );

        $admin = $this->createUser(
            email: 'admin.detail@employee.test',
            name: 'Admin Cabang',
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->get(route('employees.show', $ownEmployee))
            ->assertOk()
            ->assertSeeText('KRY-OWN');

        $this->get(route('employees.show', $otherEmployee))
            ->assertForbidden();
    }

    public function test_unassigned_admin_is_forbidden_from_employee_pages(): void
    {
        $branch = $this->createBranch('CB01');

        $employee = $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-001',
            email: 'employee01@employee.test',
            fullName: 'Karyawan Satu'
        );

        $admin = $this->createUser(
            email: 'admin.unassigned@employee.test',
            name: 'Admin Tanpa Cabang',
            role: 'admin'
        );

        $this->actingAs($admin)
            ->get(route('employees.index'))
            ->assertForbidden();

        $this->get(route('employees.show', $employee))
            ->assertForbidden();
    }

    public function test_admin_assigned_to_inactive_branch_is_forbidden_from_employee_pages(): void
    {
        $branch = $this->createBranch(
            code: 'CB01',
            status: 'inactive'
        );

        $employee = $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-001',
            email: 'employee01@employee.test',
            fullName: 'Karyawan Satu'
        );

        $admin = $this->createUser(
            email: 'admin.inactive@employee.test',
            name: 'Admin Cabang Nonaktif',
            role: 'admin',
            branch: $branch
        );

        $this->actingAs($admin)
            ->get(route('employees.index'))
            ->assertForbidden();

        $this->get(route('employees.show', $employee))
            ->assertForbidden();
    }

    public function test_hrd_retains_cross_branch_visibility_and_filtering(): void
    {
        $firstBranch = $this->createBranch('CB01');
        $secondBranch = $this->createBranch('CB02');

        $firstEmployee = $this->createEmployee(
            branch: $firstBranch,
            employeeNumber: 'KRY-FIRST',
            email: 'first@employee.test',
            fullName: 'Karyawan Pertama'
        );

        $this->createEmployee(
            branch: $secondBranch,
            employeeNumber: 'KRY-SECOND',
            email: 'second@employee.test',
            fullName: 'Karyawan Kedua'
        );

        $hrd = $this->createUser(
            email: 'hrd.scope@employee.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $this->actingAs($hrd)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSeeText('Karyawan Pertama')
            ->assertSeeText('Karyawan Kedua')
            ->assertSeeText('Kantor CB01')
            ->assertSeeText('Kantor CB02');

        $this->get(route('employees.index', [
            'branch_id' => $firstBranch->id,
        ]))
            ->assertOk()
            ->assertSeeText('Karyawan Pertama')
            ->assertDontSeeText('Karyawan Kedua');

        $this->get(route('employees.show', $firstEmployee))
            ->assertOk()
            ->assertSeeText('KRY-FIRST');
    }

    private function createUser(
        string $email,
        string $name,
        string $role,
        ?Branch $branch = null,
        string $status = 'active'
    ): User {
        return User::query()->create([
            'branch_id' => $branch?->id,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
            'role' => $role,
            'status' => $status,
            'last_login_at' => null,
        ]);
    }

    private function createBranch(
        string $code,
        string $status = 'active'
    ): Branch {
        return Branch::query()->create([
            'code' => $code,
            'name' => "Kantor {$code}",
            'address' => 'Jalan Pengujian No. 1',
            'latitude' => null,
            'longitude' => null,
            'geofence_radius' => 30,
            'maximum_accuracy' => 25,
            'status' => $status,
        ]);
    }

    private function createEmployee(
        Branch $branch,
        string $employeeNumber,
        string $email,
        string $fullName
    ): Employee {
        $user = $this->createUser(
            email: $email,
            name: $fullName,
            role: 'employee'
        );

        return Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => $employeeNumber,
            'full_name' => $fullName,
            'position' => 'Karyawan Cabang',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);
    }
}
