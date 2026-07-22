<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Presensi123!';

    public function test_guest_is_redirected_when_opening_branch_page(): void
    {
        $response = $this->get(route('branches.index'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_hrd_can_open_branch_index_and_create_pages(): void
    {
        $hrd = $this->createUser(
            'hrd@branch.test',
            'HRD Pengujian',
            'hrd'
        );

        $this->actingAs($hrd)
            ->get(route('branches.index'))
            ->assertOk()
            ->assertSeeText('Data Cabang');

        $this->get(route('branches.create'))
            ->assertOk()
            ->assertSeeText('Tambah Cabang')
            ->assertSeeText('Kode cabang')
            ->assertSeeText('Radius geofence');
    }

    public function test_admin_and_employee_cannot_manage_branches(): void
    {
        $admin = $this->createUser(
            'admin@branch.test',
            'Admin Pengujian',
            'admin'
        );

        $this->actingAs($admin)
            ->get(route('branches.index'))
            ->assertForbidden();

        $employeeUser = $this->createActiveEmployeeUser();

        $this->actingAs($employeeUser)
            ->get(route('branches.index'))
            ->assertForbidden();
    }

    public function test_hrd_can_store_valid_branch_with_empty_coordinates(): void
    {
        $hrd = $this->createUser(
            'hrd@branch.test',
            'HRD Pengujian',
            'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->post(route('branches.store'), [
                'code' => ' test01 ',
                'name' => ' Cabang Pengujian ',
                'address' => ' Jalan Pengujian No. 1 ',
                'latitude' => '',
                'longitude' => '',
                'geofence_radius' => '30.00',
                'maximum_accuracy' => '25.00',
                'status' => 'active',
            ]);

        $branch = Branch::query()
            ->where('code', 'TEST01')
            ->firstOrFail();

        $response->assertRedirect(
            route('branches.show', $branch)
        );

        $response->assertSessionHas(
            'success',
            'Data cabang berhasil ditambahkan.'
        );

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'code' => 'TEST01',
            'name' => 'Cabang Pengujian',
            'address' => 'Jalan Pengujian No. 1',
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $this->assertSame(
            30.0,
            $branch->geofence_radius
        );

        $this->assertSame(
            25.0,
            $branch->maximum_accuracy
        );
    }

    public function test_coordinates_must_be_filled_as_a_pair(): void
    {
        $hrd = $this->createUser(
            'hrd@branch.test',
            'HRD Pengujian',
            'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->from(route('branches.create'))
            ->post(route('branches.store'), [
                'code' => 'TEST01',
                'name' => 'Cabang Pengujian',
                'address' => 'Jalan Pengujian No. 1',
                'latitude' => '3.53600000',
                'longitude' => '',
                'geofence_radius' => '30.00',
                'maximum_accuracy' => '25.00',
                'status' => 'active',
            ]);

        $response->assertRedirect(
            route('branches.create')
        );

        $response->assertSessionHasErrors(
            'longitude'
        );

        $this->assertDatabaseMissing('branches', [
            'code' => 'TEST01',
        ]);
    }

    public function test_duplicate_branch_code_is_rejected(): void
    {
        $hrd = $this->createUser(
            'hrd@branch.test',
            'HRD Pengujian',
            'hrd'
        );

        $this->createBranch('CB02');

        $response = $this
            ->actingAs($hrd)
            ->from(route('branches.create'))
            ->post(route('branches.store'), [
                'code' => ' cb02 ',
                'name' => 'Cabang Duplikat',
                'address' => 'Jalan Duplikat',
                'latitude' => '',
                'longitude' => '',
                'geofence_radius' => '30.00',
                'maximum_accuracy' => '25.00',
                'status' => 'active',
            ]);

        $response->assertRedirect(
            route('branches.create')
        );

        $response->assertSessionHasErrors('code');

        $this->assertSame(
            1,
            Branch::query()
                ->where('code', 'CB02')
                ->count()
        );
    }

    public function test_hrd_can_update_branch_using_its_existing_code(): void
    {
        $hrd = $this->createUser(
            'hrd@branch.test',
            'HRD Pengujian',
            'hrd'
        );

        $branch = $this->createBranch('TEST01');

        $response = $this
            ->actingAs($hrd)
            ->put(route('branches.update', $branch), [
                'code' => ' test01 ',
                'name' => ' Cabang Diperbarui ',
                'address' => ' Jalan Pengujian No. 2 ',
                'latitude' => '3.53600000',
                'longitude' => '98.69000000',
                'geofence_radius' => '40.00',
                'maximum_accuracy' => '20.00',
                'status' => 'inactive',
            ]);

        $response->assertRedirect(
            route('branches.show', $branch)
        );

        $response->assertSessionHas(
            'success',
            'Data cabang berhasil diperbarui.'
        );

        $branch->refresh();

        $this->assertSame('TEST01', $branch->code);
        $this->assertSame(
            'Cabang Diperbarui',
            $branch->name
        );
        $this->assertSame(
            'Jalan Pengujian No. 2',
            $branch->address
        );
        $this->assertSame(3.536, $branch->latitude);
        $this->assertSame(98.69, $branch->longitude);
        $this->assertSame(
            40.0,
            $branch->geofence_radius
        );
        $this->assertSame(
            20.0,
            $branch->maximum_accuracy
        );
        $this->assertSame('inactive', $branch->status);
    }

    public function test_branch_cannot_use_code_owned_by_another_branch(): void
    {
        $hrd = $this->createUser(
            'hrd@branch.test',
            'HRD Pengujian',
            'hrd'
        );

        $this->createBranch('CB02');
        $branch = $this->createBranch('TEST01');

        $response = $this
            ->actingAs($hrd)
            ->from(route('branches.edit', $branch))
            ->put(route('branches.update', $branch), [
                'code' => 'cb02',
                'name' => $branch->name,
                'address' => $branch->address,
                'latitude' => '',
                'longitude' => '',
                'geofence_radius' => '30.00',
                'maximum_accuracy' => '25.00',
                'status' => 'active',
            ]);

        $response->assertRedirect(
            route('branches.edit', $branch)
        );

        $response->assertSessionHasErrors('code');

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'code' => 'TEST01',
        ]);
    }

    public function test_hrd_can_delete_branch_without_related_data(): void
    {
        $hrd = $this->createUser(
            'hrd@branch.test',
            'HRD Pengujian',
            'hrd'
        );

        $branch = $this->createBranch('TEST01');

        $response = $this
            ->actingAs($hrd)
            ->delete(route('branches.destroy', $branch));

        $response->assertRedirect(
            route('branches.index')
        );

        $response->assertSessionHas(
            'success',
            'Data cabang berhasil dihapus.'
        );

        $this->assertDatabaseMissing('branches', [
            'id' => $branch->id,
        ]);
    }

    public function test_branch_with_employee_cannot_be_deleted(): void
    {
        $hrd = $this->createUser(
            'hrd@branch.test',
            'HRD Pengujian',
            'hrd'
        );

        $branch = $this->createBranch('CB02');

        $employeeUser = $this->createUser(
            'employee@branch.test',
            'Karyawan Pengujian',
            'employee'
        );

        Employee::query()->create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'KRY-001',
            'full_name' => 'Karyawan Pengujian',
            'position' => 'Karyawan Cabang',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        $response = $this
            ->actingAs($hrd)
            ->delete(route('branches.destroy', $branch));

        $response->assertRedirect(
            route('branches.show', $branch)
        );

        $response->assertSessionHas('error');

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'code' => 'CB02',
        ]);
    }

    private function createUser(
        string $email,
        string $name,
        string $role
    ): User {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
            'role' => $role,
            'status' => 'active',
            'last_login_at' => null,
        ]);
    }

    private function createBranch(
        string $code
    ): Branch {
        return Branch::query()->create([
            'code' => $code,
            'name' => "Kantor {$code}",
            'address' => 'Jalan Pengujian No. 1',
            'latitude' => null,
            'longitude' => null,
            'geofence_radius' => 30,
            'maximum_accuracy' => 25,
            'status' => 'active',
        ]);
    }

    private function createActiveEmployeeUser(): User
    {
        $branch = $this->createBranch(
            'EMP-BRANCH'
        );

        $user = $this->createUser(
            'employee@branch.test',
            'Karyawan Pengujian',
            'employee'
        );

        Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'KRY-001',
            'full_name' => 'Karyawan Pengujian',
            'position' => 'Karyawan Cabang',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        return $user;
    }
}
