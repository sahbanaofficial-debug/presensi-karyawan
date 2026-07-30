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

final class WeeklyRosterNavigationUxTest extends TestCase
{
    use RefreshDatabase;

    private WeeklyRosterService $service;

    private int $employeeSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(
            WeeklyRosterService::class
        );
    }

    public function test_hrd_dashboard_and_sidebar_expose_weekly_roster_navigation(): void
    {
        $hrd = $this->createUser('hrd');

        $this->actingAs($hrd)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Roster Mingguan')
            ->assertSee('Roster mingguan')
            ->assertSee(
                route('weekly-rosters.index'),
                false
            );

        $this->actingAs($hrd)
            ->get(route('weekly-rosters.index'))
            ->assertOk()
            ->assertSee('Roster Mingguan')
            ->assertSee('aria-current="page"', false)
            ->assertSee(
                route('weekly-rosters.index'),
                false
            );
    }

    public function test_assigned_admin_sees_roster_navigation_while_unassigned_admin_and_employee_do_not(): void
    {
        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $assignedAdmin = User::factory()->create([
            'branch_id' => $branch->getKey(),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $unassignedAdmin = $this->createUser('admin');

        $employee = $this->createEmployee(
            $branch
        );

        $employeeUser =
            $employee->user()->firstOrFail();

        $this->actingAs($assignedAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Roster Mingguan')
            ->assertSee('Roster mingguan')
            ->assertSee(
                route('weekly-rosters.index'),
                false
            );

        $this->actingAs($assignedAdmin)
            ->get(route('weekly-rosters.index'))
            ->assertOk()
            ->assertSee('Roster Mingguan')
            ->assertSee(
                'aria-current="page"',
                false
            );

        foreach (
            [
                $unassignedAdmin,
                $employeeUser,
            ] as $user
        ) {
            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee(
                    'Roster Mingguan'
                )
                ->assertDontSee(
                    'Roster mingguan'
                )
                ->assertDontSee(
                    route(
                        'weekly-rosters.index'
                    ),
                    false
                );
        }
    }

    public function test_create_page_blocks_actions_when_reference_data_is_missing(): void
    {
        $hrd = $this->createUser('hrd');

        $response = $this->actingAs($hrd)
            ->get(
                route(
                    'weekly-rosters.create'
                )
            );

        $response
            ->assertOk()
            ->assertSee(
                'Referensi roster belum lengkap'
            );

        $content = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/id="add-weekly-roster-item"[^>]*disabled/s',
            $content
        );

        $this->assertMatchesRegularExpression(
            '/id="save-weekly-roster"[^>]*disabled/s',
            $content
        );
    }

    public function test_create_page_reports_ready_reference_counts(): void
    {
        $hrd = $this->createUser('hrd');

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $this->createEmployee($branch);

        WorkSchedule::query()->create([
            'name' => 'Pola UX Roster',
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_in_limit_minutes' => 30,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);

        $response = $this->actingAs($hrd)
            ->get(
                route(
                    'weekly-rosters.create'
                )
            );

        $response
            ->assertOk()
            ->assertSee(
                'Referensi penyusunan roster siap'
            )
            ->assertSee('1 cabang aktif')
            ->assertSee('1 karyawan aktif')
            ->assertSee('1 pola kerja aktif');

        $content = $response->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/id="save-weekly-roster"[^>]*disabled/s',
            $content
        );
    }

    public function test_detail_explains_draft_and_published_states(): void
    {
        $hrd = $this->createUser('hrd');

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            WorkSchedule::query()->create([
                'name' => 'Pola Detail UX',
                'check_in_time' => '08:45:00',
                'check_out_time' => '17:00:00',
                'check_in_open_minutes' => 30,
                'late_tolerance_minutes' => 5,
                'check_in_limit_minutes' => 30,
                'check_out_limit_minutes' => 60,
                'status' => 'active',
            ]);

        $roster = $this->service->createDraft(
            [
                'branch_id' => $branch->id,
                'week_start_date' => '2026-08-03',

                'items' => [
                    [
                        'employee_id' => $employee->id,

                        'work_schedule_id' => $workSchedule->id,

                        'schedule_date' => '2026-08-03',

                        'schedule_status' => 'work',

                        'notes' => null,
                    ],
                ],
            ],
            $hrd
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'weekly-rosters.show',
                    $roster
                )
            )
            ->assertOk()
            ->assertSee(
                'Publikasi bersifat final'
            )
            ->assertSee(
                'Publikasikan Roster'
            );

        $this->service->publish(
            $roster,
            $hrd
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'weekly-rosters.show',
                    $roster
                )
            )
            ->assertOk()
            ->assertSee(
                'Roster telah dipublikasikan'
            )
            ->assertDontSee(
                'Publikasikan Roster'
            );
    }

    private function createUser(
        string $role
    ): User {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function createEmployee(
        Branch $branch
    ): Employee {
        $this->employeeSequence++;

        $user = $this->createUser(
            'employee'
        );

        return Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,

            'employee_number' => sprintf(
                'EMP-WEEK-UX-%03d',
                $this->employeeSequence
            ),

            'full_name' => sprintf(
                'Karyawan Weekly UX %03d',
                $this->employeeSequence
            ),

            'position' => 'Karyawan',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);
    }
}
