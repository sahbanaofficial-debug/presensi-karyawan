<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AttendanceScheduleDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_schedule_time_window_remains_visible_after_attendance_is_complete(): void
    {
        config([
            'app.timezone' => 'Asia/Jakarta',
        ]);

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-07-25 12:00:00',
                'Asia/Jakarta'
            )
        );

        $hrd = User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        $branch = Branch::query()->create([
            'code' => 'REG-DISPLAY',
            'name' => 'Cabang Regression Display',
            'address' => 'Alamat cabang pengujian',
            'latitude' => 3.5374963,
            'longitude' => 98.6844756,
            'geofence_radius' => 30.00,
            'maximum_accuracy' => 25.00,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-REG-DISPLAY',
            'full_name' => 'Karyawan Regression Display',
            'position' => 'Karyawan',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        $workSchedule = WorkSchedule::query()->create([
            'name' => 'Pola Regression Display',
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);

        $employeeSchedule =
            EmployeeSchedule::query()->create([
                'employee_id' => $employee->id,
                'work_schedule_id' => $workSchedule->id,
                'schedule_date' => '2026-07-25',
                'schedule_status' => 'work',
                'approved_by' => $hrd->id,
                'notes' => null,
            ]);

        Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_session_id' => null,
            'employee_schedule_id' => $employeeSchedule->id,
            'branch_id' => $branch->id,
            'attendance_type' => 'check_in',
            'attendance_date' => '2026-07-25',
            'attendance_time' => '2026-07-25 08:45:00',
            'latitude' => null,
            'longitude' => null,
            'accuracy' => null,
            'distance' => null,
            'geofence_radius' => null,
            'attendance_status' => 'present',
            'punctuality_status' => 'on_time',
            'validation_status' => 'accepted',
            'record_source' => 'manual',
        ]);

        Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_session_id' => null,
            'employee_schedule_id' => $employeeSchedule->id,
            'branch_id' => $branch->id,
            'attendance_type' => 'check_out',
            'attendance_date' => '2026-07-25',
            'attendance_time' => '2026-07-25 17:00:00',
            'latitude' => null,
            'longitude' => null,
            'accuracy' => null,
            'distance' => null,
            'geofence_radius' => null,
            'attendance_status' => 'present',
            'punctuality_status' => 'not_applicable',
            'validation_status' => 'accepted',
            'record_source' => 'manual',
        ]);

        $response = $this->actingAs($employeeUser)
            ->get(route('attendance.create'));

        $response
            ->assertOk()
            ->assertViewIs('attendance.create')
            ->assertViewHas('canScan', false)
            ->assertViewHas(
                'scanBlockReason',
                'Presensi masuk dan pulang hari ini sudah tercatat.'
            )
            ->assertViewHas(
                'scheduleTimeWindow',
                static function (
                    mixed $timeWindow
                ): bool {
                    if (! is_array($timeWindow)) {
                        return false;
                    }

                    return $timeWindow[
                        'check_in_opens_at'
                    ]->format('H:i') === '08:15'
                        && $timeWindow[
                            'scheduled_check_in_at'
                        ]->format('H:i') === '08:45'
                        && $timeWindow[
                            'late_limit_at'
                        ]->format('H:i') === '08:50'
                        && $timeWindow[
                            'scheduled_check_out_at'
                        ]->format('H:i') === '17:00'
                        && $timeWindow[
                            'check_out_limit_at'
                        ]->format('H:i') === '18:00';
                }
            )
            ->assertSeeText('08:15 WIB')
            ->assertSeeText('08:45 WIB')
            ->assertSeeText('08:50 WIB')
            ->assertSeeText('17:00 WIB')
            ->assertSeeText('18:00 WIB');
    }
}