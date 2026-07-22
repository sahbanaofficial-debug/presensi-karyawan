<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class WorkScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Presensi123!';

    public function test_guest_is_redirected_from_work_schedule_pages(): void
    {
        $this->get(route('work-schedules.index'))
            ->assertRedirect(route('login'));

        $this->get(route('work-schedules.create'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_hrd_can_open_work_schedule_pages(): void
    {
        $hrd = $this->createUser(
            email: 'hrd@work-schedule.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $workSchedule = $this->createWorkSchedule(
            name: 'Jadwal Pengujian'
        );

        $this->actingAs($hrd)
            ->get(route('work-schedules.index'))
            ->assertOk()
            ->assertSeeText('Pola Jadwal Kerja')
            ->assertSeeText('Jadwal Pengujian');

        $this->get(route('work-schedules.create'))
            ->assertOk()
            ->assertSeeText('Tambah Pola Jadwal')
            ->assertSeeText('Nama Jadwal');

        $this->get(
            route('work-schedules.show', $workSchedule)
        )
            ->assertOk()
            ->assertSeeText('Detail Pola Jadwal')
            ->assertSeeText('Jadwal Pengujian');

        $this->get(
            route('work-schedules.edit', $workSchedule)
        )
            ->assertOk()
            ->assertSeeText('Edit Pola Jadwal')
            ->assertSeeText('Jadwal Pengujian');
    }

    public function test_admin_and_employee_cannot_manage_work_schedules(): void
    {
        $admin = $this->createUser(
            email: 'admin@work-schedule.test',
            name: 'Admin Pengujian',
            role: 'admin'
        );

        $this->actingAs($admin)
            ->get(route('work-schedules.index'))
            ->assertForbidden();

        $branch = $this->createBranch('CB02');

        $employee = $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-001',
            email: 'employee@work-schedule.test'
        );

        $this->actingAs($employee->user)
            ->get(route('work-schedules.index'))
            ->assertForbidden();

        $this->get(route('work-schedules.create'))
            ->assertForbidden();
    }

    public function test_work_schedule_index_can_search_and_filter(): void
    {
        $this->createWorkSchedule(
            name: 'Jadwal Penuh',
            status: 'active'
        );

        $this->createWorkSchedule(
            name: 'Jam Pulang Sore',
            checkOutTime: '17:00',
            status: 'inactive'
        );

        $hrd = $this->createUser(
            email: 'hrd@work-schedule.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->get(route('work-schedules.index', [
                'search' => 'Penuh',
                'status' => 'active',
            ]));

        $response
            ->assertOk()
            ->assertSeeText('Jadwal Penuh')
            ->assertDontSeeText('Jam Pulang Sore')
            ->assertSeeText('Filter aktif');
    }

    public function test_hrd_can_store_valid_work_schedule(): void
    {
        $hrd = $this->createUser(
            email: 'hrd@work-schedule.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->post(
                route('work-schedules.store'),
                [
                    'name' => ' Jadwal Pengujian ',
                    'check_in_time' => '09:00:00',
                    'check_out_time' => '17:00:00',
                    'check_in_open_minutes' => '30',
                    'late_tolerance_minutes' => '5',
                    'check_out_limit_minutes' => '60',
                    'status' => ' ACTIVE ',
                ]
            );

        $workSchedule = WorkSchedule::query()
            ->where('name', 'Jadwal Pengujian')
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'work-schedules.show',
                $workSchedule
            )
        );

        $response->assertSessionHas(
            'success',
            'Pola jadwal kerja berhasil ditambahkan.'
        );

        $this->assertDatabaseHas('work_schedules', [
            'id' => $workSchedule->id,
            'name' => 'Jadwal Pengujian',
            'check_in_open_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);

        $this->assertSame(
            '09:00',
            $this->formatTime(
                $workSchedule->check_in_time
            )
        );

        $this->assertSame(
            '17:00',
            $this->formatTime(
                $workSchedule->check_out_time
            )
        );
    }

    public function test_check_out_time_must_be_after_check_in_time(): void
    {
        $hrd = $this->createUser(
            email: 'hrd@work-schedule.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->from(route('work-schedules.create'))
            ->post(
                route('work-schedules.store'),
                [
                    'name' => 'Jadwal Tidak Valid',
                    'check_in_time' => '09:00',
                    'check_out_time' => '08:00',
                    'check_in_open_minutes' => 30,
                    'late_tolerance_minutes' => 5,
                    'check_out_limit_minutes' => 60,
                    'status' => 'active',
                ]
            );

        $response->assertRedirect(
            route('work-schedules.create')
        );

        $response->assertSessionHasErrors(
            'check_out_time'
        );

        $this->assertDatabaseMissing(
            'work_schedules',
            [
                'name' => 'Jadwal Tidak Valid',
            ]
        );
    }

    public function test_duplicate_work_schedule_name_is_rejected(): void
    {
        $this->createWorkSchedule(
            name: 'Jadwal Penuh'
        );

        $hrd = $this->createUser(
            email: 'hrd@work-schedule.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->from(route('work-schedules.create'))
            ->post(
                route('work-schedules.store'),
                [
                    'name' => 'Jadwal Penuh',
                    'check_in_time' => '08:45',
                    'check_out_time' => '21:30',
                    'check_in_open_minutes' => 30,
                    'late_tolerance_minutes' => 5,
                    'check_out_limit_minutes' => 60,
                    'status' => 'active',
                ]
            );

        $response->assertRedirect(
            route('work-schedules.create')
        );

        $response->assertSessionHasErrors('name');

        $this->assertSame(
            1,
            WorkSchedule::query()
                ->where('name', 'Jadwal Penuh')
                ->count()
        );
    }

    public function test_hrd_can_update_schedule_using_its_existing_name(): void
    {
        $workSchedule = $this->createWorkSchedule(
            name: 'Jadwal Pengujian'
        );

        $hrd = $this->createUser(
            email: 'hrd@work-schedule.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->put(
                route(
                    'work-schedules.update',
                    $workSchedule
                ),
                [
                    'name' => ' Jadwal Pengujian ',
                    'check_in_time' => '09:15',
                    'check_out_time' => '17:30',
                    'check_in_open_minutes' => 20,
                    'late_tolerance_minutes' => 10,
                    'check_out_limit_minutes' => 45,
                    'status' => 'inactive',
                ]
            );

        $response->assertRedirect(
            route(
                'work-schedules.show',
                $workSchedule
            )
        );

        $response->assertSessionHas(
            'success',
            'Pola jadwal kerja berhasil diperbarui.'
        );

        $workSchedule->refresh();

        $this->assertSame(
            'Jadwal Pengujian',
            $workSchedule->name
        );

        $this->assertSame(
            '09:15',
            $this->formatTime(
                $workSchedule->check_in_time
            )
        );

        $this->assertSame(
            '17:30',
            $this->formatTime(
                $workSchedule->check_out_time
            )
        );

        $this->assertSame(
            20,
            $workSchedule->check_in_open_minutes
        );

        $this->assertSame(
            10,
            $workSchedule->late_tolerance_minutes
        );

        $this->assertSame(
            45,
            $workSchedule->check_out_limit_minutes
        );

        $this->assertSame(
            'inactive',
            $workSchedule->status
        );
    }

    public function test_schedule_cannot_use_name_owned_by_another_schedule(): void
    {
        $firstSchedule = $this->createWorkSchedule(
            name: 'Jadwal Pengujian'
        );

        $this->createWorkSchedule(
            name: 'Jadwal Penuh'
        );

        $hrd = $this->createUser(
            email: 'hrd@work-schedule.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->from(
                route(
                    'work-schedules.edit',
                    $firstSchedule
                )
            )
            ->put(
                route(
                    'work-schedules.update',
                    $firstSchedule
                ),
                [
                    'name' => 'Jadwal Penuh',
                    'check_in_time' => '09:00',
                    'check_out_time' => '17:00',
                    'check_in_open_minutes' => 30,
                    'late_tolerance_minutes' => 5,
                    'check_out_limit_minutes' => 60,
                    'status' => 'active',
                ]
            );

        $response->assertRedirect(
            route(
                'work-schedules.edit',
                $firstSchedule
            )
        );

        $response->assertSessionHasErrors('name');

        $firstSchedule->refresh();

        $this->assertSame(
            'Jadwal Pengujian',
            $firstSchedule->name
        );
    }

    public function test_hrd_can_delete_unused_work_schedule(): void
    {
        $workSchedule = $this->createWorkSchedule(
            name: 'Jadwal Belum Digunakan'
        );

        $hrd = $this->createUser(
            email: 'hrd@work-schedule.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $response = $this
            ->actingAs($hrd)
            ->delete(
                route(
                    'work-schedules.destroy',
                    $workSchedule
                )
            );

        $response->assertRedirect(
            route('work-schedules.index')
        );

        $response->assertSessionHas(
            'success',
            'Pola jadwal kerja berhasil dihapus.'
        );

        $this->assertDatabaseMissing(
            'work_schedules',
            [
                'id' => $workSchedule->id,
            ]
        );
    }

    public function test_used_work_schedule_cannot_be_deleted(): void
    {
        $hrd = $this->createUser(
            email: 'hrd@work-schedule.test',
            name: 'HRD Pengujian',
            role: 'hrd'
        );

        $branch = $this->createBranch('CB02');

        $employee = $this->createEmployee(
            branch: $branch,
            employeeNumber: 'KRY-001',
            email: 'employee@work-schedule.test'
        );

        $workSchedule = $this->createWorkSchedule(
            name: 'Jadwal Digunakan'
        );

        DB::table('employee_schedules')->insert([
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => '2026-07-22',
            'schedule_status' => 'work',
            'approved_by' => $hrd->id,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($hrd)
            ->delete(
                route(
                    'work-schedules.destroy',
                    $workSchedule
                )
            );

        $response->assertRedirect(
            route(
                'work-schedules.show',
                $workSchedule
            )
        );

        $response->assertSessionHas(
            'error',
            'Pola jadwal kerja tidak dapat dihapus karena sudah digunakan pada jadwal karyawan.'
        );

        $this->assertDatabaseHas(
            'work_schedules',
            [
                'id' => $workSchedule->id,
                'name' => 'Jadwal Digunakan',
            ]
        );
    }

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

    private function createEmployee(
        Branch $branch,
        string $employeeNumber,
        string $email
    ): Employee {
        $user = $this->createUser(
            email: $email,
            name: 'Karyawan Pengujian',
            role: 'employee'
        );

        return Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => $employeeNumber,
            'full_name' => 'Karyawan Pengujian',
            'position' => 'Karyawan Cabang',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);
    }

    private function createWorkSchedule(
        string $name,
        string $checkInTime = '08:45',
        string $checkOutTime = '21:30',
        string $status = 'active'
    ): WorkSchedule {
        return WorkSchedule::query()->create([
            'name' => $name,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'check_in_open_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => $status,
        ]);
    }

    private function formatTime(
        mixed $value
    ): string {
        return Carbon::parse($value)
            ->format('H:i');
    }
}
