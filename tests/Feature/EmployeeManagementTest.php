<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Presensi123!';

    public function test_guest_is_redirected_from_employee_pages(): void
    {
        $this->get(route('employees.index'))
            ->assertRedirect(route('login'));

        $this->get(route('employees.create'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_hrd_and_admin_can_view_employee_list_and_detail(): void
    {
        $branch = $this->createBranch('CB02');

        $employee = $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-001',
            email: 'employee01@presensi.test',
            fullName: 'Karyawan Satu'
        );

        $hrd = $this->createUser(
            email: 'hrd@employee.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $admin = $this->createUser(
            email: 'admin@employee.test',
            name: 'Admin Pengujian',
            role: 'admin'
        );

        $admin->update([
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($hrd)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSeeText('Data Karyawan')
            ->assertSeeText('Karyawan Satu');

        $this->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSeeText('Detail Karyawan')
            ->assertSeeText('KRY-001');

        $this->actingAs($admin)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSeeText('Karyawan Satu');

        $this->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSeeText('KRY-001');
    }

    public function test_employee_cannot_access_employee_management(): void
    {
        $branch = $this->createBranch('CB02');

        $employee = $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-001',
            email: 'employee01@presensi.test',
            fullName: 'Karyawan Satu'
        );

        $this->actingAs($employee->user)
            ->get(route('employees.index'))
            ->assertForbidden();

        $this->get(route('employees.show', $employee))
            ->assertForbidden();

        $this->get(route('employees.create'))
            ->assertForbidden();

        $this->get(route('employees.edit', $employee))
            ->assertForbidden();
    }

    public function test_admin_cannot_create_or_update_employee(): void
    {
        $branch = $this->createBranch('CB02');

        $employee = $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-001',
            email: 'employee01@presensi.test',
            fullName: 'Karyawan Satu'
        );

        $admin = $this->createUser(
            email: 'admin@employee.test',
            name: 'Admin Pengujian',
            role: 'admin'
        );

        $this->actingAs($admin)
            ->get(route('employees.create'))
            ->assertForbidden();

        $this->get(route('employees.edit', $employee))
            ->assertForbidden();

        $this->post(
            route('employees.store'),
            $this->validEmployeePayload($branch)
        )->assertForbidden();

        $this->put(
            route('employees.update', $employee),
            $this->validEmployeePayload($branch, [
                'employee_number' => 'KRY-001',
                'email' => 'employee01@presensi.test',
                'full_name' => 'Karyawan Satu',
            ])
        )->assertForbidden();

        $this->assertDatabaseMissing('employees', [
            'employee_number' => 'KRY-TEST01',
        ]);
    }

    public function test_hrd_can_store_employee_account_and_profile(): void
    {
        $branch = $this->createBranch('CB02');

        $hrd = $this->createUser(
            email: 'hrd@employee.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->post(
                route('employees.store'),
                $this->validEmployeePayload($branch, [
                    'employee_number' => ' kry-test01 ',
                    'full_name' => ' Karyawan Pengujian ',
                    'position' => ' Karyawan Cabang ',
                    'phone_number' => ' 081234567890 ',
                    'email' => ' KARYAWAN.TEST01@PRESENSI.TEST ',
                ])
            );

        $user = User::query()
            ->where(
                'email',
                'karyawan.test01@presensi.test'
            )
            ->firstOrFail();

        $employee = Employee::query()
            ->where('employee_number', 'KRY-TEST01')
            ->firstOrFail();

        $response->assertRedirect(
            route('employees.show', $employee)
        );

        $response->assertSessionHas(
            'success',
            'Data karyawan dan akun berhasil ditambahkan.'
        );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Karyawan Pengujian',
            'email' => 'karyawan.test01@presensi.test',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'KRY-TEST01',
            'full_name' => 'Karyawan Pengujian',
            'position' => 'Karyawan Cabang',
            'phone_number' => '081234567890',
            'employment_status' => 'active',
        ]);

        $this->assertTrue(
            Hash::check(
                self::PASSWORD,
                $user->password
            )
        );
    }

    public function test_employee_index_can_search_and_filter_data(): void
    {
        $branchOne = $this->createBranch('CB01');
        $branchTwo = $this->createBranch('CB02');

        $this->createEmployee(
            branch: $branchOne,
            employeeNumber: 'KRY-ALPHA',
            email: 'alpha@presensi.test',
            fullName: 'Alpha Karyawan',
            employmentStatus: 'active'
        );

        $this->createEmployee(
            branch: $branchTwo,
            employeeNumber: 'KRY-BETA',
            email: 'beta@presensi.test',
            fullName: 'Beta Karyawan',
            employmentStatus: 'inactive'
        );

        $hrd = $this->createUser(
            email: 'hrd@employee.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->get(route('employees.index', [
                'search' => 'Alpha',
                'branch_id' => $branchOne->id,
                'status' => 'active',
            ]));

        $response
            ->assertOk()
            ->assertSeeText('Alpha Karyawan')
            ->assertDontSeeText('Beta Karyawan')
            ->assertSeeText('Filter aktif');
    }

    public function test_duplicate_email_and_employee_number_are_rejected(): void
    {
        $branch = $this->createBranch('CB02');

        $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-001',
            email: 'employee01@presensi.test',
            fullName: 'Karyawan Satu'
        );

        $hrd = $this->createUser(
            email: 'hrd@employee.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->from(route('employees.create'))
            ->post(
                route('employees.store'),
                $this->validEmployeePayload($branch, [
                    'employee_number' => ' kry-001 ',
                    'email' => ' EMPLOYEE01@PRESENSI.TEST ',
                ])
            );

        $response->assertRedirect(
            route('employees.create')
        );

        $response->assertSessionHasErrors([
            'employee_number',
            'email',
        ]);

        $this->assertSame(
            1,
            Employee::query()->count()
        );

        $this->assertDatabaseMissing('employees', [
            'employee_number' => 'KRY-TEST01',
        ]);
    }

    public function test_inactive_branch_cannot_be_used_for_new_employee(): void
    {
        $inactiveBranch = $this->createBranch(
            code: 'CB99',
            status: 'inactive'
        );

        $hrd = $this->createUser(
            email: 'hrd@employee.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->from(route('employees.create'))
            ->post(
                route('employees.store'),
                $this->validEmployeePayload(
                    $inactiveBranch
                )
            );

        $response->assertRedirect(
            route('employees.create')
        );

        $response->assertSessionHasErrors(
            'branch_id'
        );

        $this->assertDatabaseMissing('users', [
            'email' => 'karyawan.test01@presensi.test',
        ]);

        $this->assertDatabaseMissing('employees', [
            'employee_number' => 'KRY-TEST01',
        ]);
    }

    public function test_hrd_can_update_employee_without_changing_password(): void
    {
        $branch = $this->createBranch('CB02');

        $employee = $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-001',
            email: 'employee01@presensi.test',
            fullName: 'Karyawan Satu'
        );

        $oldPasswordHash = $employee->user->password;

        $hrd = $this->createUser(
            email: 'hrd@employee.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->put(
                route('employees.update', $employee),
                [
                    'employee_number' => ' kry-001 ',
                    'full_name' => ' Karyawan Satu Diperbarui ',
                    'position' => ' Supervisor Cabang ',
                    'phone_number' => ' 081111111111 ',
                    'branch_id' => $branch->id,
                    'employment_status' => 'inactive',
                    'email' => ' EMPLOYEE01@PRESENSI.TEST ',
                    'account_status' => 'inactive',
                    'password' => '',
                    'password_confirmation' => '',
                ]
            );

        $response->assertRedirect(
            route('employees.show', $employee)
        );

        $response->assertSessionHas(
            'success',
            'Data karyawan dan akun berhasil diperbarui.'
        );

        $employee->refresh();
        $employee->user->refresh();

        $this->assertSame(
            'KRY-001',
            $employee->employee_number
        );

        $this->assertSame(
            'Karyawan Satu Diperbarui',
            $employee->full_name
        );

        $this->assertSame(
            'Supervisor Cabang',
            $employee->position
        );

        $this->assertSame(
            '081111111111',
            $employee->phone_number
        );

        $this->assertSame(
            'inactive',
            $employee->employment_status
        );

        $this->assertSame(
            'Karyawan Satu Diperbarui',
            $employee->user->name
        );

        $this->assertSame(
            'employee01@presensi.test',
            $employee->user->email
        );

        $this->assertSame(
            'inactive',
            $employee->user->status
        );

        $this->assertSame(
            $oldPasswordHash,
            $employee->user->password
        );

        $this->assertTrue(
            Hash::check(
                self::PASSWORD,
                $employee->user->password
            )
        );
    }

    public function test_hrd_can_change_employee_password(): void
    {
        $branch = $this->createBranch('CB02');

        $employee = $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-001',
            email: 'employee01@presensi.test',
            fullName: 'Karyawan Satu'
        );

        $oldPasswordHash = $employee->user->password;

        $hrd = $this->createUser(
            email: 'hrd@employee.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->put(
                route('employees.update', $employee),
                [
                    'employee_number' => 'KRY-001',
                    'full_name' => 'Karyawan Satu',
                    'position' => 'Karyawan Cabang',
                    'phone_number' => null,
                    'branch_id' => $branch->id,
                    'employment_status' => 'active',
                    'email' => 'employee01@presensi.test',
                    'account_status' => 'active',
                    'password' => 'PasswordBaru123!',
                    'password_confirmation' => 'PasswordBaru123!',
                ]
            );

        $response->assertRedirect(
            route('employees.show', $employee)
        );

        $employee->user->refresh();

        $this->assertNotSame(
            $oldPasswordHash,
            $employee->user->password
        );

        $this->assertTrue(
            Hash::check(
                'PasswordBaru123!',
                $employee->user->password
            )
        );

        $this->assertFalse(
            Hash::check(
                self::PASSWORD,
                $employee->user->password
            )
        );
    }

    public function test_employee_cannot_use_email_or_number_owned_by_another_employee(): void
    {
        $branch = $this->createBranch('CB02');

        $firstEmployee = $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-001',
            email: 'employee01@presensi.test',
            fullName: 'Karyawan Satu'
        );

        $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-002',
            email: 'employee02@presensi.test',
            fullName: 'Karyawan Dua'
        );

        $hrd = $this->createUser(
            email: 'hrd@employee.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->from(
                route(
                    'employees.edit',
                    $firstEmployee
                )
            )
            ->put(
                route(
                    'employees.update',
                    $firstEmployee
                ),
                [
                    'employee_number' => 'kry-002',
                    'full_name' => 'Karyawan Satu',
                    'position' => 'Karyawan Cabang',
                    'phone_number' => null,
                    'branch_id' => $branch->id,
                    'employment_status' => 'active',
                    'email' => 'EMPLOYEE02@PRESENSI.TEST',
                    'account_status' => 'active',
                    'password' => '',
                    'password_confirmation' => '',
                ]
            );

        $response->assertRedirect(
            route(
                'employees.edit',
                $firstEmployee
            )
        );

        $response->assertSessionHasErrors([
            'employee_number',
            'email',
        ]);

        $firstEmployee->refresh();
        $firstEmployee->user->refresh();

        $this->assertSame(
            'KRY-001',
            $firstEmployee->employee_number
        );

        $this->assertSame(
            'employee01@presensi.test',
            $firstEmployee->user->email
        );
    }

    public function test_employee_destroy_route_is_not_available(): void
    {
        $this->assertFalse(
            Route::has('employees.destroy')
        );
    }

    /**
     * Membuat akun pengguna untuk pengujian.
     */
    private function createUser(
        string $email,
        string $name,
        string $role,
        string $status = 'active'
    ): User {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(
                self::PASSWORD
            ),
            'role' => $role,
            'status' => $status,
            'last_login_at' => null,
        ]);
    }

    /**
     * Membuat cabang pengujian.
     */
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

    /**
     * Membuat akun dan profil karyawan pengujian.
     */
    private function createEmployee(
        Branch $branch,
        string $employeeNumber,
        string $email,
        string $fullName,
        string $employmentStatus = 'active',
        string $accountStatus = 'active'
    ): Employee {
        $user = $this->createUser(
            email: $email,
            name: $fullName,
            role: 'employee',
            status: $accountStatus
        );

        return Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => $employeeNumber,
            'full_name' => $fullName,
            'position' => 'Karyawan Cabang',
            'phone_number' => null,
            'employment_status' => $employmentStatus,
        ]);
    }

    /**
     * Menyediakan data formulir karyawan yang valid.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validEmployeePayload(
        Branch $branch,
        array $overrides = []
    ): array {
        return array_merge([
            'employee_number' => 'KRY-TEST01',
            'full_name' => 'Karyawan Pengujian',
            'position' => 'Karyawan Cabang',
            'phone_number' => '081234567890',
            'branch_id' => $branch->id,
            'employment_status' => 'active',
            'email' => 'karyawan.test01@presensi.test',
            'account_status' => 'active',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ], $overrides);
    }
}
