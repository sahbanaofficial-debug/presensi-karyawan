<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WeeklyRosterMatrixPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_hrd_create_page_exposes_bulk_weekly_roster_matrix_contract(): void
    {
        $branch = Branch::factory()->create([
            'code' => 'MATRIX-01',
            'name' => 'Cabang Matriks',
            'status' => 'active',
        ]);

        $hrd = User::factory()->create([
            'branch_id' => null,
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $employeeUser = User::factory()->create([
            'branch_id' => $branch->id,
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::query()->create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'KRY-MATRIX-001',
            'full_name' => 'Karyawan Matriks',
            'position' => 'Staf Matriks',
            'phone_number' => '081234567891',
            'employment_status' => 'active',
        ]);

        foreach (
            [
                ['Pulang Sore', '08:00:00', '16:00:00'],
                ['Masuk Siang', '12:00:00', '20:00:00'],
                ['Jadwal Penuh', '08:00:00', '20:00:00'],
            ] as [$name, $checkIn, $checkOut]
        ) {
            WorkSchedule::query()->create([
                'name' => $name,
                'check_in_time' => $checkIn,
                'check_out_time' => $checkOut,
                'check_in_open_minutes' => 30,
                'late_tolerance_minutes' => 5,
                'check_in_limit_minutes' => 30,
                'check_out_limit_minutes' => 60,
                'status' => 'active',
            ]);
        }

        $response = $this
            ->actingAs($hrd)
            ->get(route('weekly-rosters.create'));

        $response
            ->assertOk()
            ->assertViewIs('weekly-rosters.create')
            ->assertSee('Matriks Roster')
            ->assertSee('id="weekly-roster-matrix"', false)
            ->assertSee('data-matrix-body', false)
            ->assertSee('data-week-day="0"', false)
            ->assertSee('data-week-day="6"', false)
            ->assertSee('Senin')
            ->assertSee('Minggu')
            ->assertSee('Pulang Sore')
            ->assertSee('Masuk Siang')
            ->assertSee('Jadwal Penuh')
            ->assertSee('Libur')
            ->assertSee('Cuti')
            ->assertSee('roster-choice-green', false)
            ->assertSee('roster-choice-yellow', false)
            ->assertSee('roster-choice-orange', false)
            ->assertSee('roster-choice-red', false)
            ->assertDontSee('Tambah Item')
            ->assertDontSee('weekly-roster-item-template');
    }
}
