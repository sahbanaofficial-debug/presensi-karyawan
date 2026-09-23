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

final class AuthenticationAndMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Presensi123!';

    public function test_guest_sees_original_login_layout_with_motion_stylesheet(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('css/login-motion.css')
            ->assertSee('css/ui-luxe.css')
            ->assertSee('Masuk ke Akun')
            ->assertSee('QR Dinamis')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('Tampilkan kata sandi');
    }

    public function test_guest_is_redirected_to_login_when_opening_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_active_hrd_can_login(): void
    {
        $user = $this->createUser('hrd');

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = $this->createUser('hrd');

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'PasswordSalah!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login(): void
    {
        $user = $this->createUser('hrd', 'inactive');

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_employee_without_profile_cannot_login(): void
    {
        $user = $this->createUser('employee');

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_employee_with_inactive_profile_cannot_login(): void
    {
        [$user] = $this->createEmployee('inactive');

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_employee_with_active_profile_can_login(): void
    {
        [$user] = $this->createEmployee();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_role_middleware_only_allows_matching_roles(): void
    {
        Route::middleware([
            'web',
            'auth',
            'active',
        ])->group(function (): void {
            Route::get(
                '/_test/access/hrd',
                fn (): string => 'AKSES HRD DITERIMA'
            )->middleware('role:hrd');

            Route::get(
                '/_test/access/admin',
                fn (): string => 'AKSES ADMIN DITERIMA'
            )->middleware('role:admin');

            Route::get(
                '/_test/access/employee',
                fn (): string => 'AKSES KARYAWAN DITERIMA'
            )->middleware('role:employee');
        });

        $hrd = $this->createUser('hrd');
        $admin = $this->createUser('admin');
        [$employeeUser] = $this->createEmployee();

        $this->actingAs($hrd)
            ->get('/_test/access/hrd')
            ->assertOk()
            ->assertSeeText('AKSES HRD DITERIMA');

        $this->get('/_test/access/admin')
            ->assertForbidden();

        $this->get('/_test/access/employee')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/_test/access/admin')
            ->assertOk()
            ->assertSeeText('AKSES ADMIN DITERIMA');

        $this->get('/_test/access/hrd')
            ->assertForbidden();

        $this->get('/_test/access/employee')
            ->assertForbidden();

        $this->actingAs($employeeUser)
            ->get('/_test/access/employee')
            ->assertOk()
            ->assertSeeText('AKSES KARYAWAN DITERIMA');

        $this->get('/_test/access/hrd')
            ->assertForbidden();

        $this->get('/_test/access/admin')
            ->assertForbidden();
    }

    public function test_active_middleware_logs_out_employee_after_profile_is_deactivated(): void
    {
        [$user, $employee] = $this->createEmployee();

        $this->actingAs($user);

        $employee->update([
            'employment_status' => 'inactive',
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->createUser('hrd');

        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect(route('login'));

        $response->assertSessionHas(
            'success',
            'Anda berhasil keluar dari sistem.'
        );

        $this->assertGuest();
    }

    private function createUser(
        string $role,
        string $status = 'active'
    ): User {
        $name = match ($role) {
            'hrd' => 'HRD',
            'admin' => 'Admin Operasional',
            'employee' => 'Karyawan Pengujian',
            default => 'Pengguna Pengujian',
        };

        return User::query()->create([
            'name' => $name,
            'email' => "{$role}@pengujian.test",
            'password' => Hash::make(self::PASSWORD),
            'role' => $role,
            'status' => $status,
            'last_login_at' => null,
        ]);
    }

    /**
     * @return array{0: User, 1: Employee}
     */
    private function createEmployee(
        string $employmentStatus = 'active'
    ): array {
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

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'KRY-001',
            'full_name' => 'Karyawan Pengujian',
            'position' => 'Karyawan Cabang 02',
            'phone_number' => null,
            'employment_status' => $employmentStatus,
        ]);

        return [$user, $employee];
    }
}
