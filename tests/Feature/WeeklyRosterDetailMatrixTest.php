<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\WeeklyRosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WeeklyRosterDetailMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_and_published_roster_use_the_same_weekly_matrix(): void
    {
        $hrd = User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $branch = Branch::factory()->create([
            'code' => 'MATRIX-DETAIL',
            'name' => 'Cabang Matriks Detail',
            'status' => 'active',
        ]);

        $firstEmployee = $this->createEmployee(
            $branch,
            'MATRIX-DETAIL-001',
            'Karyawan Matriks Pertama'
        );

        $secondEmployee = $this->createEmployee(
            $branch,
            'MATRIX-DETAIL-002',
            'Karyawan Matriks Kedua'
        );

        $workSchedule = WorkSchedule::query()->create([
            'name' => 'Pulang Sore Matriks',
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_in_limit_minutes' => 30,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);

        $service = $this->app->make(
            WeeklyRosterService::class
        );

        $roster = $service->createDraft(
            [
                'branch_id' => $branch->id,
                'week_start_date' => '2026-08-03',
                'items' => [
                    [
                        'employee_id' => $firstEmployee->id,
                        'work_schedule_id' => $workSchedule->id,
                        'schedule_date' => '2026-08-03',
                        'schedule_status' => 'work',
                        'notes' => null,
                    ],
                    [
                        'employee_id' => $firstEmployee->id,
                        'work_schedule_id' => null,
                        'schedule_date' => '2026-08-04',
                        'schedule_status' => 'off',
                        'notes' => null,
                    ],
                    [
                        'employee_id' => $secondEmployee->id,
                        'work_schedule_id' => null,
                        'schedule_date' => '2026-08-05',
                        'schedule_status' => 'leave',
                        'notes' => 'Cuti tahunan',
                    ],
                ],
            ],
            $hrd
        );

        $this->actingAs($hrd)
            ->get(route('weekly-rosters.show', $roster))
            ->assertOk()
            ->assertSee('id="weekly-roster-detail-matrix"', false)
            ->assertSee('Senin')
            ->assertSee('Minggu')
            ->assertSee('Karyawan Matriks Pertama')
            ->assertSee('Karyawan Matriks Kedua')
            ->assertSee('Pulang Sore Matriks')
            ->assertSee('Libur')
            ->assertSee('Cuti')
            ->assertSee('Publikasikan Roster');

        $service->publish($roster, $hrd);

        $this->actingAs($hrd)
            ->get(route('weekly-rosters.show', $roster))
            ->assertOk()
            ->assertSee('id="weekly-roster-detail-matrix"', false)
            ->assertSee('Roster telah dipublikasikan')
            ->assertSee('Pulang Sore Matriks')
            ->assertSee('Libur')
            ->assertSee('Cuti')
            ->assertDontSee('Publikasikan Roster');
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
