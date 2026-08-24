<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class EmployeeScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    public function test_guest_is_redirected_to_login_when_accessing_schedule_module(): void
    {
        $this->get(route('employee-schedules.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_schedule_module(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('employee-schedules.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('employee-schedules.create'))
            ->assertForbidden();
    }

    public function test_hrd_can_open_schedule_index_page(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create([
            'code' => 'SCH-INDEX',
        ]);

        $employee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-INDEX-001',
                'full_name' => 'Karyawan Jadwal Index',
            ]
        );

        $workSchedule = $this->createWorkSchedule([
            'name' => 'Pola Jadwal Index',
        ]);

        $employeeSchedule = $this->createEmployeeSchedule(
            $employee,
            $workSchedule,
            $hrd,
            [
                'schedule_date' => '2026-08-01',
            ]
        );

        $response = $this->actingAs($hrd)
            ->get(route('employee-schedules.index'));

        $response
            ->assertOk()
            ->assertViewIs('employee-schedules.index')
            ->assertViewHas(
                'employeeSchedules',
                static function ($employeeSchedules) use (
                    $employeeSchedule
                ): bool {
                    return $employeeSchedules
                        ->getCollection()
                        ->contains(
                            static fn (
                                EmployeeSchedule $schedule
                            ): bool => $schedule->is(
                                $employeeSchedule
                            )
                        );
                }
            )
            ->assertSee('Jadwal Harian Karyawan')
            ->assertSee('EMP-INDEX-001')
            ->assertSee('Karyawan Jadwal Index')
            ->assertSee('Pola Jadwal Index');
    }

    public function test_create_page_only_displays_active_employees_and_work_schedules(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create([
            'code' => 'SCH-CREATE',
        ]);

        $activeEmployee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-ACTIVE-001',
                'full_name' => 'Karyawan Aktif Jadwal',
                'employment_status' => 'active',
            ]
        );

        $inactiveEmployee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-INACTIVE-001',
                'full_name' => 'Karyawan Tidak Aktif Jadwal',
                'employment_status' => 'inactive',
            ]
        );

        $activeWorkSchedule = $this->createWorkSchedule([
            'name' => 'Pola Aktif Jadwal',
            'status' => 'active',
        ]);

        $inactiveWorkSchedule = $this->createWorkSchedule([
            'name' => 'Pola Tidak Aktif Jadwal',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($hrd)
            ->get(route('employee-schedules.create'));

        $response
            ->assertOk()
            ->assertViewIs('employee-schedules.create')
            ->assertSee('Tambah Jadwal Harian')
            ->assertSee($activeEmployee->employee_number)
            ->assertSee($activeEmployee->full_name)
            ->assertSee($activeWorkSchedule->name)
            ->assertDontSee($inactiveEmployee->employee_number)
            ->assertDontSee($inactiveEmployee->full_name)
            ->assertDontSee($inactiveWorkSchedule->name);
    }

    public function test_hrd_can_store_valid_work_schedule(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $response = $this->actingAs($hrd)
            ->post(
                route('employee-schedules.store'),
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $workSchedule->id,
                    'schedule_date' => '2026-08-02',
                    'schedule_status' => ' WORK ',
                    'notes' => ' Jadwal kerja reguler ',
                ]
            );

        $employeeSchedule = EmployeeSchedule::query()
            ->where('employee_id', $employee->id)
            ->whereDate('schedule_date', '2026-08-02')
            ->firstOrFail();

        $response
            ->assertRedirect(
                route(
                    'employee-schedules.show',
                    $employeeSchedule
                )
            )
            ->assertSessionHas(
                'success',
                'Jadwal harian karyawan berhasil ditambahkan.'
            );

       $this->assertDatabaseHas(
    'employee_schedules',
    [
        'id' => $employeeSchedule->id,
        'employee_id' => $employee->id,
        'work_schedule_id' => $workSchedule->id,
        'schedule_date' => '2026-08-02 00:00:00',
        'schedule_status' => 'work',
        'approved_by' => $hrd->id,
        'notes' => 'Jadwal kerja reguler',
    ]
);
    }

    public function test_hrd_can_store_day_off_without_work_schedule(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $employee = $this->createEmployee($branch);

        $response = $this->actingAs($hrd)
            ->post(
                route('employee-schedules.store'),
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => null,
                    'schedule_date' => '2026-08-03',
                    'schedule_status' => 'off',
                    'notes' => 'Libur bergilir',
                ]
            );

        $employeeSchedule = EmployeeSchedule::query()
            ->where('employee_id', $employee->id)
            ->whereDate('schedule_date', '2026-08-03')
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'employee-schedules.show',
                $employeeSchedule
            )
        );

        $this->assertDatabaseHas(
            'employee_schedules',
            [
                'id' => $employeeSchedule->id,
                'employee_id' => $employee->id,
                'work_schedule_id' => null,
                'schedule_date' => '2026-08-03 00:00:00',
                'schedule_status' => 'off',
                'approved_by' => $hrd->id,
                'notes' => 'Libur bergilir',
            ]
        );
    }

    public function test_work_status_requires_work_schedule_and_off_status_rejects_work_schedule(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $this->actingAs($hrd)
            ->from(route('employee-schedules.create'))
            ->post(
                route('employee-schedules.store'),
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => null,
                    'schedule_date' => '2026-08-04',
                    'schedule_status' => 'work',
                    'notes' => null,
                ]
            )
            ->assertRedirect(
                route('employee-schedules.create')
            )
            ->assertSessionHasErrors([
                'work_schedule_id',
            ]);

        $this->actingAs($hrd)
            ->from(route('employee-schedules.create'))
            ->post(
                route('employee-schedules.store'),
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $workSchedule->id,
                    'schedule_date' => '2026-08-05',
                    'schedule_status' => 'off',
                    'notes' => 'Libur',
                ]
            )
            ->assertRedirect(
                route('employee-schedules.create')
            )
            ->assertSessionHasErrors([
                'work_schedule_id',
            ]);

        $this->assertDatabaseCount(
            'employee_schedules',
            0
        );
    }

    public function test_inactive_employee_and_work_schedule_are_rejected(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();

        $inactiveEmployee = $this->createEmployee(
            $branch,
            [
                'employment_status' => 'inactive',
            ]
        );

        $inactiveWorkSchedule = $this->createWorkSchedule([
            'status' => 'inactive',
        ]);

        $this->actingAs($hrd)
            ->from(route('employee-schedules.create'))
            ->post(
                route('employee-schedules.store'),
                [
                    'employee_id' => $inactiveEmployee->id,
                    'work_schedule_id' => $inactiveWorkSchedule->id,
                    'schedule_date' => '2026-08-06',
                    'schedule_status' => 'work',
                    'notes' => null,
                ]
            )
            ->assertRedirect(
                route('employee-schedules.create')
            )
            ->assertSessionHasErrors([
                'employee_id',
                'work_schedule_id',
            ]);

        $this->assertDatabaseCount(
            'employee_schedules',
            0
        );
    }

    public function test_duplicate_employee_and_date_are_rejected(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $this->createEmployeeSchedule(
            $employee,
            $workSchedule,
            $hrd,
            [
                'schedule_date' => '2026-08-07',
            ]
        );

        $this->actingAs($hrd)
            ->from(route('employee-schedules.create'))
            ->post(
                route('employee-schedules.store'),
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $workSchedule->id,
                    'schedule_date' => '2026-08-07',
                    'schedule_status' => 'work',
                    'notes' => 'Jadwal kedua',
                ]
            )
            ->assertRedirect(
                route('employee-schedules.create')
            )
            ->assertSessionHasErrors([
                'schedule_date',
            ]);

        $this->assertDatabaseCount(
            'employee_schedules',
            1
        );
    }

    public function test_hrd_can_open_schedule_detail_page(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'code' => 'SCH-DETAIL',
            'name' => 'Cabang Detail Jadwal',
        ]);

        $employee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-DETAIL-001',
                'full_name' => 'Karyawan Detail Jadwal',
            ]
        );

        $workSchedule = $this->createWorkSchedule([
            'name' => 'Pola Detail Jadwal',
        ]);

        $employeeSchedule = $this->createEmployeeSchedule(
            $employee,
            $workSchedule,
            $hrd,
            [
                'schedule_date' => '2026-08-08',
                'notes' => 'Catatan detail jadwal',
            ]
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'employee-schedules.show',
                    $employeeSchedule
                )
            )
            ->assertOk()
            ->assertViewIs('employee-schedules.show')
            ->assertViewHas(
                'employeeSchedule',
                static fn (
                    EmployeeSchedule $viewSchedule
                ): bool => $viewSchedule->is(
                    $employeeSchedule
                )
            )
            ->assertSee('Detail Jadwal Harian')
            ->assertSee('EMP-DETAIL-001')
            ->assertSee('Karyawan Detail Jadwal')
            ->assertSee('Pola Detail Jadwal')
            ->assertSee('Catatan detail jadwal');
    }

    public function test_hrd_can_open_schedule_edit_page(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();

        $employee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-EDIT-001',
                'full_name' => 'Karyawan Edit Jadwal',
            ]
        );

        $workSchedule = $this->createWorkSchedule([
            'name' => 'Pola Edit Jadwal',
        ]);

        $employeeSchedule = $this->createEmployeeSchedule(
            $employee,
            $workSchedule,
            $hrd,
            [
                'schedule_date' => '2026-08-09',
            ]
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'employee-schedules.edit',
                    $employeeSchedule
                )
            )
            ->assertOk()
            ->assertViewIs('employee-schedules.edit')
            ->assertSee('Edit Jadwal Harian')
            ->assertSee('EMP-EDIT-001')
            ->assertSee('Karyawan Edit Jadwal')
            ->assertSee('Pola Edit Jadwal');
    }

    public function test_hrd_can_update_work_schedule_to_day_off(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $employeeSchedule = $this->createEmployeeSchedule(
            $employee,
            $workSchedule,
            $hrd,
            [
                'schedule_date' => '2026-08-10',
                'schedule_status' => 'work',
                'notes' => 'Jadwal kerja awal',
            ]
        );

        $response = $this->actingAs($hrd)
            ->put(
                route(
                    'employee-schedules.update',
                    $employeeSchedule
                ),
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => null,
                    'schedule_date' => '2026-08-10',
                    'schedule_status' => 'off',
                    'notes' => ' Libur pengganti ',
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'employee-schedules.show',
                    $employeeSchedule
                )
            )
            ->assertSessionHas(
                'success',
                'Jadwal harian karyawan berhasil diperbarui.'
            );

        $this->assertDatabaseHas(
            'employee_schedules',
            [
                'id' => $employeeSchedule->id,
                'employee_id' => $employee->id,
                'work_schedule_id' => null,
                'schedule_date' => '2026-08-10 00:00:00',
                'schedule_status' => 'off',
                'approved_by' => $hrd->id,
                'notes' => 'Libur pengganti',
            ]
        );
    }

    public function test_hrd_edit_converts_generated_schedule_to_manual_override(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $employee = $this->createEmployee($branch);

        $originalSchedule = $this->createWorkSchedule([
            'name' => 'Jadwal Penuh',
            'check_in_time' => '08:45:00',
            'check_out_time' => '21:30:00',
        ]);

        $replacementSchedule = $this->createWorkSchedule([
            'name' => 'Masuk Siang',
            'check_in_time' => '13:00:00',
            'check_out_time' => '21:30:00',
        ]);

        $employeeSchedule = $this->createEmployeeSchedule(
            $employee,
            $originalSchedule,
            $hrd,
            [
                'schedule_date' => '2026-08-24',
                'schedule_source' =>
                    EmployeeSchedule::SOURCE_BRANCH_DEFAULT,
                'work_schedule_name_snapshot' => 'Jadwal Penuh',
                'check_in_time_snapshot' => '08:45:00',
                'check_out_time_snapshot' => '21:30:00',
                'check_in_open_minutes_snapshot' => 30,
                'check_in_limit_minutes_snapshot' => 30,
                'late_tolerance_minutes_snapshot' => 5,
                'check_out_limit_minutes_snapshot' => 60,
            ]
        );

        $this->actingAs($hrd)
            ->put(
                route(
                    'employee-schedules.update',
                    $employeeSchedule
                ),
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' =>
                        $replacementSchedule->id,
                    'schedule_date' => '2026-08-24',
                    'schedule_status' => 'work',
                    'notes' => 'Jadwal pengujian siang',
                ]
            )
            ->assertRedirect(
                route(
                    'employee-schedules.show',
                    $employeeSchedule
                )
            );

        $employeeSchedule->refresh();

        $this->assertSame(
            $replacementSchedule->id,
            $employeeSchedule->work_schedule_id
        );
        $this->assertSame(
            EmployeeSchedule::SOURCE_MANUAL,
            $employeeSchedule->schedule_source
        );
        $this->assertNull(
            $employeeSchedule->weekly_schedule_item_id
        );
        $this->assertTrue(
            $employeeSchedule->hasEmptyWorkSnapshot()
        );
    }

    public function test_search_and_status_filter_return_matching_schedule(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $workSchedule = $this->createWorkSchedule();

        $alphaEmployee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-ALPHA-001',
                'full_name' => 'Karyawan Alpha Jadwal',
            ]
        );

        $betaEmployee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-BETA-001',
                'full_name' => 'Karyawan Beta Jadwal',
            ]
        );

        $this->createEmployeeSchedule(
            $alphaEmployee,
            $workSchedule,
            $hrd,
            [
                'schedule_date' => '2026-08-11',
                'schedule_status' => 'work',
            ]
        );

        $this->createEmployeeSchedule(
            $betaEmployee,
            null,
            $hrd,
            [
                'schedule_date' => '2026-08-11',
                'schedule_status' => 'off',
            ]
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'employee-schedules.index',
                    [
                        'search' => 'Alpha',
                        'schedule_status' => 'work',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas('search', 'Alpha')
            ->assertViewHas(
                'selectedStatus',
                'work'
            )
            ->assertSee('EMP-ALPHA-001')
            ->assertSee('Karyawan Alpha Jadwal')
            ->assertDontSee('EMP-BETA-001')
            ->assertDontSee('Karyawan Beta Jadwal');
    }

    public function test_schedule_index_uses_fifteen_items_per_page(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $startingDate = CarbonImmutable::parse(
            '2026-09-01'
        );

        for ($index = 0; $index < 16; $index++) {
            $this->createEmployeeSchedule(
                $employee,
                $workSchedule,
                $hrd,
                [
                    'schedule_date' => $startingDate
                        ->addDays($index)
                        ->toDateString(),
                ]
            );
        }

        $firstPage = $this->actingAs($hrd)
            ->get(route('employee-schedules.index'));

        $firstPage
            ->assertOk()
            ->assertViewHas(
                'employeeSchedules',
                static function (
                    $employeeSchedules
                ): bool {
                    return $employeeSchedules->perPage() === 15
                        && $employeeSchedules
                            ->currentPage() === 1
                        && $employeeSchedules->total() === 16
                        && $employeeSchedules->count() === 15;
                }
            );

        $secondPage = $this->actingAs($hrd)
            ->get(
                route(
                    'employee-schedules.index',
                    [
                        'page' => 2,
                    ]
                )
            );

        $secondPage
            ->assertOk()
            ->assertViewHas(
                'employeeSchedules',
                static function (
                    $employeeSchedules
                ): bool {
                    return $employeeSchedules->perPage() === 15
                        && $employeeSchedules
                            ->currentPage() === 2
                        && $employeeSchedules->total() === 16
                        && $employeeSchedules->count() === 1;
                }
            );
    }

    public function test_hrd_can_delete_schedule_without_attendance(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $employeeSchedule = $this->createEmployeeSchedule(
            $employee,
            $workSchedule,
            $hrd,
            [
                'schedule_date' => '2026-08-12',
            ]
        );

        $response = $this->actingAs($hrd)
            ->delete(
                route(
                    'employee-schedules.destroy',
                    $employeeSchedule
                )
            );

        $response
            ->assertRedirect(
                route('employee-schedules.index')
            )
            ->assertSessionHas(
                'success',
                'Jadwal harian karyawan berhasil dihapus.'
            );

        $this->assertDatabaseMissing(
            'employee_schedules',
            [
                'id' => $employeeSchedule->id,
            ]
        );
    }

    public function test_schedule_with_attendance_cannot_be_deleted(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'latitude' => '3.59519600',
            'longitude' => '98.67222600',
            'geofence_radius' => '30.00',
        ]);

        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $employeeSchedule = $this->createEmployeeSchedule(
            $employee,
            $workSchedule,
            $hrd,
            [
                'schedule_date' => '2026-08-13',
            ]
        );

        $this->createAttendance(
            $employeeSchedule,
            $employee,
            $branch,
            $hrd
        );

        $response = $this->actingAs($hrd)
            ->delete(
                route(
                    'employee-schedules.destroy',
                    $employeeSchedule
                )
            );

        $response
            ->assertRedirect(
                route(
                    'employee-schedules.show',
                    $employeeSchedule
                )
            )
            ->assertSessionHas(
                'error',
                'Jadwal harian tidak dapat dihapus karena sudah memiliki data presensi.'
            );

        $this->assertDatabaseHas(
            'employee_schedules',
            [
                'id' => $employeeSchedule->id,
            ]
        );

        $this->assertDatabaseHas(
            'attendances',
            [
                'employee_schedule_id' => $employeeSchedule->id,
                'attendance_type' => 'check_in',
            ]
        );
    }

    private function createHrd(): User
    {
        return User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createEmployee(
        Branch $branch,
        array $overrides = []
    ): Employee {
        $this->employeeSequence++;

        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::query()->create(
            array_replace(
                [
                    'user_id' => $employeeUser->id,
                    'branch_id' => $branch->id,
                    'employee_number' => sprintf(
                        'EMP-SCH-%03d',
                        $this->employeeSequence
                    ),
                    'full_name' => sprintf(
                        'Karyawan Jadwal %03d',
                        $this->employeeSequence
                    ),
                    'position' => 'Karyawan',
                    'phone_number' => null,
                    'employment_status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createWorkSchedule(
        array $overrides = []
    ): WorkSchedule {
        $this->workScheduleSequence++;

        return WorkSchedule::query()->create(
            array_replace(
                [
                    'name' => sprintf(
                        'Pola Jadwal Test %03d',
                        $this->workScheduleSequence
                    ),
                    'check_in_time' => '08:45:00',
                    'check_out_time' => '17:00:00',
                    'check_in_open_minutes' => 30,
                    'late_tolerance_minutes' => 5,
                    'check_out_limit_minutes' => 60,
                    'status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createEmployeeSchedule(
        Employee $employee,
        ?WorkSchedule $workSchedule,
        User $approver,
        array $overrides = []
    ): EmployeeSchedule {
        return EmployeeSchedule::query()->create(
            array_replace(
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $workSchedule?->id,
                    'schedule_date' => '2026-08-20',
                    'schedule_status' => $workSchedule === null
                            ? 'off'
                            : 'work',
                    'approved_by' => $approver->id,
                    'notes' => null,
                ],
                $overrides
            )
        );
    }

    private function createAttendance(
        EmployeeSchedule $employeeSchedule,
        Employee $employee,
        Branch $branch,
        User $creator
    ): void {
        $scheduleDate = $employeeSchedule
            ->schedule_date
            ->format('Y-m-d');

        $now = now();

        $attendanceSessionId = DB::table(
            'attendance_sessions'
        )->insertGetId([
            'public_id' => (string) Str::uuid(),
            'branch_id' => $branch->id,
            'attendance_type' => 'check_in',
            'session_date' => $scheduleDate,
            'start_time' => "{$scheduleDate} 08:15:00",
            'end_time' => "{$scheduleDate} 09:30:00",
            'encrypted_secret' => 'encrypted-secret-for-feature-test',
            'status' => 'closed',
            'created_by' => $creator->id,
            'closed_at' => "{$scheduleDate} 09:30:00",
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('attendances')->insert([
            'employee_id' => $employee->id,
            'attendance_session_id' => $attendanceSessionId,
            'employee_schedule_id' => $employeeSchedule->id,
            'branch_id' => $branch->id,
            'attendance_type' => 'check_in',
            'attendance_date' => $scheduleDate,
            'attendance_time' => "{$scheduleDate} 08:45:00",
            'latitude' => '3.59519600',
            'longitude' => '98.67222600',
            'accuracy' => '10.00',
            'distance' => '5.00',
            'geofence_radius' => '30.00',
            'attendance_status' => 'present',
            'punctuality_status' => 'on_time',
            'validation_status' => 'accepted',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
