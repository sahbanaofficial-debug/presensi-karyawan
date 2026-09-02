<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Support\Aug13AttendanceHistoryRestorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class Aug13AttendanceHistoryRestorerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_restores_the_original_history_once(): void
    {
        $this->createReferences();

        $restorer = app(Aug13AttendanceHistoryRestorer::class);
        $first = $restorer->restore();
        $second = $restorer->restore();

        $this->assertSame('restored', $first['status']);
        $this->assertSame('already_restored', $second['status']);
        $this->assertSame(5, $first['employee_schedules']);
        $this->assertSame(1, $first['attendance_sessions']);
        $this->assertSame(6, $first['attendances']);

        $this->assertDatabaseCount('employee_schedules', 5);
        $this->assertDatabaseCount('attendance_sessions', 1);
        $this->assertDatabaseCount('attendances', 6);
        $this->assertDatabaseCount('data_restore_snapshots', 1);

        $p005 = Employee::query()
            ->where('employee_number', 'P005')
            ->firstOrFail();

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $p005->id,
            'attendance_type' => 'check_in',
            'attendance_date' => '2026-08-13',
            'attendance_time' => '2026-08-13 08:51:13',
            'accuracy' => 19.98,
            'distance' => 9.49,
            'punctuality_status' => 'late',
            'validation_status' => 'accepted',
        ]);

        $p004 = Employee::query()
            ->where('employee_number', 'P004')
            ->firstOrFail();

        $this->assertSame(
            2,
            DB::table('attendances')
                ->where('employee_id', $p004->id)
                ->whereDate('attendance_date', '2026-08-13')
                ->count()
        );
    }

    public function test_it_stops_before_writing_when_a_schedule_conflicts(): void
    {
        $references = $this->createReferences();

        $otherSchedule = WorkSchedule::query()->create([
            'name' => 'Jadwal Berbeda',
            'check_in_time' => '10:00:00',
            'check_out_time' => '18:00:00',
            'check_in_open_minutes' => 30,
            'check_in_limit_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);

        DB::table('employee_schedules')->insert([
            'weekly_schedule_item_id' => null,
            'employee_id' => $references['employees']['P001']->id,
            'work_schedule_id' => $otherSchedule->id,
            'schedule_date' => '2026-08-13',
            'schedule_status' => 'work',
            'schedule_source' => 'manual',
            'approved_by' => $references['hrd']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            app(Aug13AttendanceHistoryRestorer::class)->restore();
            $this->fail('Konflik jadwal seharusnya menghentikan pemulihan.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'isinya berbeda',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('employee_schedules', 1);
        $this->assertDatabaseCount('attendance_sessions', 0);
        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('data_restore_snapshots', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function createReferences(): array
    {
        $workSchedule = WorkSchedule::query()->create([
            'name' => 'Pulang Sore',
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'check_in_limit_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);

        $branch = Branch::query()->create([
            'default_work_schedule_id' => $workSchedule->id,
            'code' => 'CB02',
            'name' => 'Kantor Cabang 02',
            'address' => 'Alamat pengujian',
            'latitude' => 3.53744900,
            'longitude' => 98.68446000,
            'geofence_radius' => 30.00,
            'maximum_accuracy' => 25.00,
            'status' => 'active',
        ]);

        $hrd = User::factory()->create([
            'name' => 'HRD',
            'email' => 'hrd@presensi.test',
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $employees = [];

        foreach (range(1, 5) as $number) {
            $employeeNumber = 'P'.str_pad(
                (string) $number,
                3,
                '0',
                STR_PAD_LEFT
            );
            $user = User::factory()->create([
                'name' => "Percobaan {$number}",
                'email' => "percobaan{$number}@presensi.test",
                'role' => 'employee',
                'status' => 'active',
            ]);

            $employees[$employeeNumber] = Employee::query()->create([
                'user_id' => $user->id,
                'branch_id' => $branch->id,
                'employee_number' => $employeeNumber,
                'full_name' => "Percobaan {$number}",
                'position' => 'percobaan',
                'employment_status' => 'active',
            ]);
        }

        DB::table('branch_terminals')->insert([
            'public_id' => (string) Str::uuid(),
            'branch_id' => $branch->id,
            'name' => 'Terminal Cabang 02',
            'device_token_hash' => hash('sha256', 'terminal-test-token'),
            'activation_code_hash' => null,
            'activation_expires_at' => null,
            'activated_at' => '2026-08-24 12:40:00',
            'last_seen_at' => '2026-08-24 12:49:25',
            'status' => 'active',
            'created_by' => $hrd->id,
            'revoked_by' => null,
            'revoked_at' => null,
            'created_at' => '2026-08-24 12:39:00',
            'updated_at' => '2026-08-24 12:49:25',
        ]);

        return [
            'work_schedule' => $workSchedule,
            'branch' => $branch,
            'hrd' => $hrd,
            'employees' => $employees,
        ];
    }
}
