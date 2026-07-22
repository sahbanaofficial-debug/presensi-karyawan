<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DatabaseStructureAndConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_research_tables_and_columns_exist(): void
    {
        $tables = [
            'users' => [
                'id',
                'name',
                'email',
                'password',
                'role',
                'status',
                'last_login_at',
            ],
            'branches' => [
                'id',
                'code',
                'name',
                'address',
                'latitude',
                'longitude',
                'geofence_radius',
                'maximum_accuracy',
                'status',
            ],
            'employees' => [
                'id',
                'user_id',
                'branch_id',
                'employee_number',
                'full_name',
                'position',
                'phone_number',
                'employment_status',
            ],
            'work_schedules' => [
                'id',
                'name',
                'check_in_time',
                'check_out_time',
                'check_in_open_minutes',
                'late_tolerance_minutes',
                'check_out_limit_minutes',
                'status',
            ],
            'employee_schedules' => [
                'id',
                'employee_id',
                'work_schedule_id',
                'schedule_date',
                'schedule_status',
                'approved_by',
                'notes',
            ],
            'schedule_swap_requests' => [
                'id',
                'requester_employee_id',
                'partner_employee_id',
                'requester_date',
                'partner_date',
                'reason',
                'status',
                'approved_by',
                'approved_at',
            ],
            'attendance_sessions' => [
                'id',
                'public_id',
                'branch_id',
                'attendance_type',
                'session_date',
                'start_time',
                'end_time',
                'encrypted_secret',
                'status',
                'created_by',
                'closed_at',
            ],
            'attendances' => [
                'id',
                'employee_id',
                'attendance_session_id',
                'employee_schedule_id',
                'branch_id',
                'attendance_type',
                'attendance_date',
                'attendance_time',
                'latitude',
                'longitude',
                'accuracy',
                'distance',
                'geofence_radius',
                'attendance_status',
                'punctuality_status',
                'validation_status',
            ],
            'validation_logs' => [
                'id',
                'user_id',
                'attendance_session_id',
                'validation_type',
                'status',
                'reason',
                'latitude',
                'longitude',
                'accuracy',
                'distance',
                'created_at',
            ],
            'correction_logs' => [
                'id',
                'attendance_id',
                'changed_by',
                'approved_by',
                'before_data',
                'after_data',
                'reason',
                'created_at',
            ],
        ];

        foreach ($tables as $table => $columns) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Tabel {$table} tidak ditemukan."
            );

            foreach ($columns as $column) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "Kolom {$table}.{$column} tidak ditemukan."
                );
            }
        }
    }

    public function test_primary_master_identifiers_are_unique(): void
    {
        $this->createUser(
            'pengguna@pengujian.test',
            'hrd'
        );

        $this->assertUniqueConstraintViolation(function (): void {
            $this->createUser(
                'pengguna@pengujian.test',
                'admin'
            );
        });

        $this->createBranch('CB02');

        $this->assertUniqueConstraintViolation(function (): void {
            $this->createBranch('CB02');
        });

        $this->createWorkSchedule('Jadwal penuh');

        $this->assertUniqueConstraintViolation(function (): void {
            $this->createWorkSchedule('Jadwal penuh');
        });
    }

    public function test_employee_user_and_employee_number_are_unique(): void
    {
        $branch = $this->createBranch('CB02');

        $firstUser = $this->createUser(
            'employee01@pengujian.test',
            'employee'
        );

        $secondUser = $this->createUser(
            'employee02@pengujian.test',
            'employee'
        );

        Employee::query()->create([
            'user_id' => $firstUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'KRY-001',
            'full_name' => 'Karyawan 01',
            'position' => 'Karyawan Cabang',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        $this->assertUniqueConstraintViolation(
            function () use ($firstUser, $branch): void {
                Employee::query()->create([
                    'user_id' => $firstUser->id,
                    'branch_id' => $branch->id,
                    'employee_number' => 'KRY-002',
                    'full_name' => 'Profil Kedua',
                    'position' => 'Karyawan Cabang',
                    'phone_number' => null,
                    'employment_status' => 'active',
                ]);
            }
        );

        $this->assertUniqueConstraintViolation(
            function () use ($secondUser, $branch): void {
                Employee::query()->create([
                    'user_id' => $secondUser->id,
                    'branch_id' => $branch->id,
                    'employee_number' => 'KRY-001',
                    'full_name' => 'Nomor Duplikat',
                    'position' => 'Karyawan Cabang',
                    'phone_number' => null,
                    'employment_status' => 'active',
                ]);
            }
        );
    }

    public function test_employee_schedule_is_unique_per_employee_and_date(): void
    {
        $context = $this->createAttendanceContext();

        EmployeeSchedule::query()->create([
            'employee_id' => $context['employee']->id,
            'work_schedule_id' => $context['workSchedule']->id,
            'schedule_date' => '2026-07-21',
            'schedule_status' => 'work',
            'approved_by' => $context['hrd']->id,
            'notes' => null,
        ]);

        $this->assertUniqueConstraintViolation(
            function () use ($context): void {
                EmployeeSchedule::query()->create([
                    'employee_id' => $context['employee']->id,
                    'work_schedule_id' => $context['workSchedule']->id,
                    'schedule_date' => '2026-07-21',
                    'schedule_status' => 'work',
                    'approved_by' => $context['hrd']->id,
                    'notes' => 'Jadwal duplikat.',
                ]);
            }
        );
    }

    public function test_attendance_session_public_id_is_unique(): void
    {
        $branch = $this->createBranch('CB02');

        $hrd = $this->createUser(
            'hrd@pengujian.test',
            'hrd'
        );

        $publicId = '11111111-1111-4111-8111-111111111111';

        $this->createAttendanceSession(
            $branch,
            $hrd,
            'check_in',
            $publicId
        );

        $this->assertUniqueConstraintViolation(
            function () use ($branch, $hrd, $publicId): void {
                $this->createAttendanceSession(
                    $branch,
                    $hrd,
                    'check_out',
                    $publicId
                );
            }
        );
    }

    public function test_duplicate_attendance_type_is_rejected_across_sessions(): void
    {
        $context = $this->createCompleteAttendanceContext();

        $firstSession = $this->createAttendanceSession(
            $context['branch'],
            $context['hrd'],
            'check_in'
        );

        $replacementSession = $this->createAttendanceSession(
            $context['branch'],
            $context['hrd'],
            'check_in'
        );

        $this->createAttendance(
            $context,
            $firstSession,
            'check_in',
            '2026-07-21 08:45:00'
        );

        $this->assertUniqueConstraintViolation(
            function () use (
                $context,
                $replacementSession
            ): void {
                $this->createAttendance(
                    $context,
                    $replacementSession,
                    'check_in',
                    '2026-07-21 08:47:00'
                );
            }
        );
    }

    public function test_check_in_and_check_out_can_exist_on_same_schedule(): void
    {
        $context = $this->createCompleteAttendanceContext();

        $checkInSession = $this->createAttendanceSession(
            $context['branch'],
            $context['hrd'],
            'check_in'
        );

        $checkOutSession = $this->createAttendanceSession(
            $context['branch'],
            $context['hrd'],
            'check_out'
        );

        $this->createAttendance(
            $context,
            $checkInSession,
            'check_in',
            '2026-07-21 08:45:00'
        );

        $this->createAttendance(
            $context,
            $checkOutSession,
            'check_out',
            '2026-07-21 21:30:00'
        );

        $this->assertDatabaseCount('attendances', 2);

        $this->assertDatabaseHas('attendances', [
            'employee_schedule_id' => $context['employeeSchedule']->id,
            'attendance_type' => 'check_in',
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_schedule_id' => $context['employeeSchedule']->id,
            'attendance_type' => 'check_out',
        ]);
    }

    private function createUser(
        string $email,
        string $role
    ): User {
        return User::query()->create([
            'name' => ucfirst($role).' Pengujian',
            'email' => $email,
            'password' => Hash::make('Presensi123!'),
            'role' => $role,
            'status' => 'active',
            'last_login_at' => null,
        ]);
    }

    private function createBranch(string $code): Branch
    {
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

    private function createWorkSchedule(
        string $name
    ): WorkSchedule {
        return WorkSchedule::query()->create([
            'name' => $name,
            'check_in_time' => '08:45:00',
            'check_out_time' => '21:30:00',
            'check_in_open_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);
    }

    /**
     * @return array{
     *     hrd: User,
     *     branch: Branch,
     *     employee: Employee,
     *     workSchedule: WorkSchedule
     * }
     */
    private function createAttendanceContext(): array
    {
        $hrd = $this->createUser(
            'hrd@pengujian.test',
            'hrd'
        );

        $employeeUser = $this->createUser(
            'employee@pengujian.test',
            'employee'
        );

        $branch = $this->createBranch('CB02');

        $employee = Employee::query()->create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'KRY-001',
            'full_name' => 'Karyawan Pengujian',
            'position' => 'Karyawan Cabang',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        $workSchedule = $this->createWorkSchedule(
            'Jadwal penuh'
        );

        return [
            'hrd' => $hrd,
            'branch' => $branch,
            'employee' => $employee,
            'workSchedule' => $workSchedule,
        ];
    }

    /**
     * @return array{
     *     hrd: User,
     *     branch: Branch,
     *     employee: Employee,
     *     workSchedule: WorkSchedule,
     *     employeeSchedule: EmployeeSchedule
     * }
     */
    private function createCompleteAttendanceContext(): array
    {
        $context = $this->createAttendanceContext();

        $employeeSchedule = EmployeeSchedule::query()->create([
            'employee_id' => $context['employee']->id,
            'work_schedule_id' => $context['workSchedule']->id,
            'schedule_date' => '2026-07-21',
            'schedule_status' => 'work',
            'approved_by' => $context['hrd']->id,
            'notes' => null,
        ]);

        return [
            ...$context,
            'employeeSchedule' => $employeeSchedule,
        ];
    }

    private function createAttendanceSession(
        Branch $branch,
        User $creator,
        string $attendanceType,
        ?string $publicId = null
    ): AttendanceSession {
        $session = new AttendanceSession([
            'branch_id' => $branch->id,
            'attendance_type' => $attendanceType,
            'session_date' => '2026-07-21',
            'start_time' => $attendanceType === 'check_in'
                ? '2026-07-21 08:15:00'
                : '2026-07-21 21:30:00',
            'end_time' => $attendanceType === 'check_in'
                ? '2026-07-21 09:00:00'
                : '2026-07-21 22:30:00',
            'encrypted_secret' => 'secret-pengujian',
            'status' => 'active',
            'created_by' => $creator->id,
            'closed_at' => null,
        ]);

        if ($publicId !== null) {
            $session->public_id = $publicId;
        }

        $session->save();

        return $session;
    }

    /**
     * @param array{
     *     branch: Branch,
     *     employee: Employee,
     *     employeeSchedule: EmployeeSchedule
     * } $context
     */
    private function createAttendance(
        array $context,
        AttendanceSession $session,
        string $attendanceType,
        string $attendanceTime
    ): Attendance {
        return Attendance::query()->create([
            'employee_id' => $context['employee']->id,
            'attendance_session_id' => $session->id,
            'employee_schedule_id' => $context['employeeSchedule']->id,
            'branch_id' => $context['branch']->id,
            'attendance_type' => $attendanceType,
            'attendance_date' => '2026-07-21',
            'attendance_time' => $attendanceTime,
            'latitude' => 3.53600000,
            'longitude' => 98.69000000,
            'accuracy' => 10.00,
            'distance' => 5.00,
            'geofence_radius' => 30.00,
            'attendance_status' => 'present',
            'punctuality_status' => $attendanceType === 'check_in'
                ? 'on_time'
                : 'not_applicable',
            'validation_status' => 'accepted',
        ]);
    }

    private function assertUniqueConstraintViolation(
        Closure $operation
    ): void {
        try {
            $operation();
        } catch (QueryException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail(
            'Database menerima data duplikat yang seharusnya ditolak.'
        );
    }
}
