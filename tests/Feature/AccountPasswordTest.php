<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AccountPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const CURRENT_PASSWORD = 'Presensi123!';

    private const NEW_PASSWORD = 'PresensiBaru123!';

    public function test_guest_cannot_open_password_page(): void
    {
        $this->get(route('account.password.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_password_page_is_available_for_all_active_roles(): void
    {
        $users = [
            $this->createUser('hrd'),
            $this->createUser('admin'),
            $this->createEmployeeUser(),
        ];

        foreach ($users as $user) {
            $this->actingAs($user)
                ->get(route('account.password.edit'))
                ->assertOk()
                ->assertSeeText('Ubah Password')
                ->assertSeeText('Password saat ini')
                ->assertSeeText('Simpan password baru');
        }
    }

    public function test_authenticated_user_can_update_own_password(): void
    {
        $user = $this->createUser('hrd');

        $response = $this
            ->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => self::CURRENT_PASSWORD,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ]);

        $response
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHas(
                'success',
                'Kata sandi akun berhasil diperbarui.'
            );

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(
            Hash::check(
                self::NEW_PASSWORD,
                (string) $user->fresh()->password
            )
        );
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->createUser('hrd');

        $response = $this
            ->actingAs($user)
            ->from(route('account.password.edit'))
            ->put(route('account.password.update'), [
                'current_password' => 'PasswordLamaSalah1!',
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ]);

        $response
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(
            Hash::check(
                self::CURRENT_PASSWORD,
                (string) $user->fresh()->password
            )
        );
    }

    public function test_new_password_must_be_strong_and_confirmed(): void
    {
        $user = $this->createUser('hrd');

        $this->actingAs($user)
            ->from(route('account.password.edit'))
            ->put(route('account.password.update'), [
                'current_password' => self::CURRENT_PASSWORD,
                'password' => 'passwordbaru',
                'password_confirmation' => 'tidak-sama',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasErrors('password');

        $this->assertTrue(
            Hash::check(
                self::CURRENT_PASSWORD,
                (string) $user->fresh()->password
            )
        );
    }

    public function test_new_password_must_differ_from_current_password(): void
    {
        $user = $this->createUser('hrd');

        $this->actingAs($user)
            ->from(route('account.password.edit'))
            ->put(route('account.password.update'), [
                'current_password' => self::CURRENT_PASSWORD,
                'password' => self::CURRENT_PASSWORD,
                'password_confirmation' => self::CURRENT_PASSWORD,
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasErrors('password');
    }

    private function createUser(string $role): User
    {
        return User::query()->create([
            'name' => match ($role) {
                'hrd' => 'HRD',
                'admin' => 'Admin Operasional',
                default => 'Karyawan Pengujian',
            },
            'email' => "{$role}@ubah-password.test",
            'password' => Hash::make(self::CURRENT_PASSWORD),
            'role' => $role,
            'status' => 'active',
            'last_login_at' => null,
        ]);
    }

    private function createEmployeeUser(): User
    {
        $user = $this->createUser('employee');

        $branch = Branch::query()->create([
            'code' => 'CB02',
            'name' => 'Kantor Cabang 02',
            'address' => 'Jalan Brigjen Zein Hamid, Kompleks Katamso Indah No. A9',
            'latitude' => null,
            'longitude' => null,
            'geofence_radius' => 30,
            'maximum_accuracy' => 25,
            'status' => 'active',
        ]);

        Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'KRY-PASSWORD',
            'full_name' => 'Karyawan Pengujian',
            'position' => 'Karyawan Cabang 02',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        return $user;
    }
}
