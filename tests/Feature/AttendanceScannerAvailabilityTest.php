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
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class AttendanceScannerAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_scanner_is_blocked_before_check_in_opens(): void
    {
        $message =
            'Presensi masuk belum dibuka. Presensi dapat dilakukan mulai pukul 08:15 WIB.';

        $this->openScannerAt(
            '2026-11-01 08:14:59'
        )
            ->assertOk()
            ->assertViewHas('canScan', false)
            ->assertViewHas(
                'scanBlockReason',
                $message
            )
            ->assertSeeText($message);
    }

    public function test_scanner_is_available_at_final_check_in_limit(): void
    {
        $this->openScannerAt(
            '2026-11-01 09:15:00'
        )
            ->assertOk()
            ->assertViewHas('canScan', true)
            ->assertViewHas(
                'scanBlockReason',
                static fn (mixed $reason): bool => $reason === null
            )
            ->assertDontSeeText(
                'Pemindai presensi tidak tersedia'
            );
    }

    public function test_scanner_is_blocked_after_final_check_in_limit(): void
    {
        $message =
            'Batas akhir presensi masuk telah lewat pada pukul 09:15 WIB.';

        $this->openScannerAt(
            '2026-11-01 09:15:01'
        )
            ->assertOk()
            ->assertViewHas('canScan', false)
            ->assertViewHas(
                'scanBlockReason',
                $message
            )
            ->assertSeeText($message);
    }

    public function test_scanner_is_blocked_before_scheduled_check_out(): void
    {
        $message =
            'Presensi pulang belum diperbolehkan. Jadwal pulang adalah pukul 17:00 WIB.';

        $this->openScannerAt(
            moment: '2026-11-01 16:59:59',
            withCheckIn: true
        )
            ->assertOk()
            ->assertViewHas('canScan', false)
            ->assertViewHas(
                'scanBlockReason',
                $message
            )
            ->assertSeeText($message);
    }

    public function test_scanner_is_available_at_scheduled_check_out(): void
    {
        $this->openScannerAt(
            moment: '2026-11-01 17:00:00',
            withCheckIn: true
        )
            ->assertOk()
            ->assertViewHas('canScan', true)
            ->assertViewHas(
                'scanBlockReason',
                null
            );
    }

    public function test_scanner_is_available_at_final_check_out_limit(): void
    {
        $this->openScannerAt(
            moment: '2026-11-01 18:00:00',
            withCheckIn: true
        )
            ->assertOk()
            ->assertViewHas('canScan', true)
            ->assertViewHas(
                'scanBlockReason',
                null
            );
    }

    public function test_scanner_is_blocked_after_final_check_out_limit(): void
    {
        $message =
            'Batas akhir presensi pulang telah lewat pada pukul 18:00 WIB.';

        $this->openScannerAt(
            moment: '2026-11-01 18:00:01',
            withCheckIn: true
        )
            ->assertOk()
            ->assertViewHas('canScan', false)
            ->assertViewHas(
                'scanBlockReason',
                $message
            )
            ->assertSeeText($message);
    }

    public function test_location_control_is_disabled_when_scanner_is_blocked(): void
    {
        $response = $this->openScannerAt(
            '2026-11-01 09:15:01'
        );

        $response->assertOk();

        $this->assertLocationButtonDisabled(
            $response,
            true
        );

        $html = (string) $response->getContent();

        $this->assertMatchesRegularExpression(
            '/checkLocationButton\.disabled\s*=\s*'
                .'! canScan\s*\|\|\s*'
                .'! hasMapConfiguration/s',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/const checkCurrentLocation = async function \(\) '
                .'\{\s*if \(\s*! canScan\s*\|\|/s',
            $html
        );

        $this->assertStringContainsString(
            'Pemeriksaan Lokasi Tidak Tersedia',
            $html
        );
    }

    public function test_location_control_is_enabled_when_scanner_is_available(): void
    {
        $response = $this->openScannerAt(
            '2026-11-01 09:15:00'
        );

        $response->assertOk();

        $this->assertLocationButtonDisabled(
            $response,
            false
        );
    }

    private function assertLocationButtonDisabled(
        TestResponse $response,
        bool $expectedDisabled
    ): void {
        $html = (string) $response->getContent();

        $matched = preg_match(
            '/<button\b(?=[^>]*\bid="check-location-button")'
                .'[^>]*>/is',
            $html,
            $matches
        );

        $this->assertSame(
            1,
            $matched,
            'Tombol Periksa Lokasi tidak ditemukan.'
        );

        $isDisabled = preg_match(
            '/\sdisabled(?:\s|=|>)/i',
            $matches[0]
        ) === 1;

        $this->assertSame(
            $expectedDisabled,
            $isDisabled
        );
    }

    private function openScannerAt(
        string $moment,
        bool $withCheckIn = false
    ): TestResponse {
        config([
            'app.timezone' => 'Asia/Jakarta',
        ]);

        $currentMoment = CarbonImmutable::parse(
            $moment,
            'Asia/Jakarta'
        );

        $this->travelTo($currentMoment);

        $branch = Branch::factory()->create([
            'code' => 'BR-SCANNER-TIME',
            'name' => 'Cabang Scanner Time Window',
            'latitude' => 3.595196,
            'longitude' => 98.672226,
            'geofence_radius' => 30.0,
            'maximum_accuracy' => 20.0,
            'status' => 'active',
        ]);

        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-SCANNER-TIME',
            'full_name' => 'Karyawan Scanner Time Window',
            'position' => 'Karyawan',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        $approver = User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $workSchedule =
            WorkSchedule::query()->create([
                'name' => 'Pola Scanner Time Window',
                'check_in_time' => '08:45:00',
                'check_out_time' => '17:00:00',
                'check_in_open_minutes' => 30,
                'check_in_limit_minutes' => 30,
                'late_tolerance_minutes' => 5,
                'check_out_limit_minutes' => 60,
                'status' => 'active',
            ]);

        $employeeSchedule =
            EmployeeSchedule::query()->create([
                'employee_id' => $employee->id,
                'work_schedule_id' => $workSchedule->id,
                'schedule_date' => $currentMoment->format('Y-m-d'),
                'schedule_status' => 'work',
                'approved_by' => $approver->id,
                'notes' => null,
            ]);

        if ($withCheckIn) {
            $this->createAttendance(
                employee: $employee,
                employeeSchedule: $employeeSchedule,
                branch: $branch,
                attendanceType: 'check_in',
                attendanceTime: $currentMoment
                    ->setTime(8, 45)
            );
        }

        return $this->actingAs($employeeUser)
            ->get(route('attendance.create'));
    }

    private function createAttendance(
        Employee $employee,
        EmployeeSchedule $employeeSchedule,
        Branch $branch,
        string $attendanceType,
        CarbonImmutable $attendanceTime
    ): Attendance {
        return Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_session_id' => null,
            'employee_schedule_id' => $employeeSchedule->id,
            'branch_id' => $branch->id,
            'attendance_type' => $attendanceType,
            'attendance_date' => $attendanceTime->format('Y-m-d'),
            'attendance_time' => $attendanceTime,
            'latitude' => null,
            'longitude' => null,
            'accuracy' => null,
            'distance' => null,
            'geofence_radius' => null,
            'attendance_status' => 'present',
            'punctuality_status' => $attendanceType === 'check_in'
                    ? 'on_time'
                    : 'not_applicable',
            'validation_status' => 'accepted',
            'record_source' => 'manual',
        ]);
    }
}
