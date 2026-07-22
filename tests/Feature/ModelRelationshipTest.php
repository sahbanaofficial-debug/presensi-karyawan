<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\CorrectionLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\ScheduleSwapRequest;
use App\Models\User;
use App\Models\ValidationLog;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_employee_and_branch_relationships_are_consistent(): void
    {
        $context = $this->createRelationshipContext();

        $this->assertNotNull($context['employeeUser']->employee);

        $this->assertTrue(
            $context['employeeUser']->employee->is(
                $context['employee']
            )
        );

        $this->assertTrue(
            $context['employee']->user->is(
                $context['employeeUser']
            )
        );

        $this->assertTrue(
            $context['employee']->branch->is(
                $context['branch']
            )
        );

        $this->assertTrue(
            $context['branch']->employees->contains(
                'id',
                $context['employee']->id
            )
        );

        $this->assertTrue(
            $context['branch']->employees->contains(
                'id',
                $context['partnerEmployee']->id
            )
        );
    }

    public function test_work_schedule_and_employee_schedule_relationships_are_consistent(): void
    {
        $context = $this->createRelationshipContext();

        $this->assertTrue(
            $context['employee']->schedules->contains(
                'id',
                $context['employeeSchedule']->id
            )
        );

        $this->assertTrue(
            $context['workSchedule']->employeeSchedules->contains(
                'id',
                $context['employeeSchedule']->id
            )
        );

        $this->assertTrue(
            $context['employeeSchedule']->employee->is(
                $context['employee']
            )
        );

        $this->assertTrue(
            $context['employeeSchedule']->workSchedule->is(
                $context['workSchedule']
            )
        );

        $this->assertTrue(
            $context['employeeSchedule']->approver->is(
                $context['hrd']
            )
        );

        $this->assertTrue(
            $context['employeeSchedule']->attendances->contains(
                'id',
                $context['attendance']->id
            )
        );
    }

    public function test_attendance_session_and_attendance_relationships_are_consistent(): void
    {
        $context = $this->createRelationshipContext();

        $this->assertTrue(
            $context['attendanceSession']->branch->is(
                $context['branch']
            )
        );

        $this->assertTrue(
            $context['attendanceSession']->creator->is(
                $context['hrd']
            )
        );

        $this->assertTrue(
            $context['attendanceSession']->attendances->contains(
                'id',
                $context['attendance']->id
            )
        );

        $this->assertTrue(
            $context['attendanceSession']->validationLogs->contains(
                'id',
                $context['validationLog']->id
            )
        );

        $this->assertTrue(
            $context['attendance']->employee->is(
                $context['employee']
            )
        );

        $this->assertTrue(
            $context['attendance']->attendanceSession->is(
                $context['attendanceSession']
            )
        );

        $this->assertTrue(
            $context['attendance']->employeeSchedule->is(
                $context['employeeSchedule']
            )
        );

        $this->assertTrue(
            $context['attendance']->branch->is(
                $context['branch']
            )
        );

        $this->assertTrue(
            $context['validationLog']->user->is(
                $context['employeeUser']
            )
        );

        $this->assertTrue(
            $context['validationLog']->attendanceSession->is(
                $context['attendanceSession']
            )
        );
    }

    public function test_attendance_and_correction_log_relationships_are_consistent(): void
    {
        $context = $this->createRelationshipContext();

        $this->assertTrue(
            $context['attendance']->correctionLogs->contains(
                'id',
                $context['correctionLog']->id
            )
        );

        $this->assertTrue(
            $context['correctionLog']->attendance->is(
                $context['attendance']
            )
        );

        $this->assertTrue(
            $context['correctionLog']->changer->is(
                $context['hrd']
            )
        );

        $this->assertTrue(
            $context['correctionLog']->approver->is(
                $context['hrd']
            )
        );

        $this->assertTrue(
            $context['hrd']->changedCorrectionLogs->contains(
                'id',
                $context['correctionLog']->id
            )
        );

        $this->assertTrue(
            $context['hrd']->approvedCorrectionLogs->contains(
                'id',
                $context['correctionLog']->id
            )
        );
    }

    public function test_schedule_swap_and_user_activity_relationships_are_consistent(): void
    {
        $context = $this->createRelationshipContext();

        $this->assertTrue(
            $context['employee']->requestedScheduleSwaps->contains(
                'id',
                $context['scheduleSwapRequest']->id
            )
        );

        $this->assertTrue(
            $context['partnerEmployee']->partneredScheduleSwaps->contains(
                'id',
                $context['scheduleSwapRequest']->id
            )
        );

        $this->assertTrue(
            $context['scheduleSwapRequest']->requesterEmployee->is(
                $context['employee']
            )
        );

        $this->assertTrue(
            $context['scheduleSwapRequest']->partnerEmployee->is(
                $context['partnerEmployee']
            )
        );

        $this->assertTrue(
            $context['scheduleSwapRequest']->approver->is(
                $context['hrd']
            )
        );

        $this->assertTrue(
            $context['hrd']->approvedScheduleSwapRequests->contains(
                'id',
                $context['scheduleSwapRequest']->id
            )
        );

        $this->assertTrue(
            $context['hrd']->createdAttendanceSessions->contains(
                'id',
                $context['attendanceSession']->id
            )
        );

        $this->assertTrue(
            $context['hrd']->approvedEmployeeSchedules->contains(
                'id',
                $context['employeeSchedule']->id
            )
        );

        $this->assertTrue(
            $context['employeeUser']->validationLogs->contains(
                'id',
                $context['validationLog']->id
            )
        );
    }

    /**
     * @return array{
     *     hrd: User,
     *     employeeUser: User,
     *     partnerUser: User,
     *     branch: Branch,
     *     employee: Employee,
     *     partnerEmployee: Employee,
     *     workSchedule: WorkSchedule,
     *     employeeSchedule: EmployeeSchedule,
     *     attendanceSession: AttendanceSession,
     *     attendance: Attendance,
     *     validationLog: ValidationLog,
     *     correctionLog: CorrectionLog,
     *     scheduleSwapRequest: ScheduleSwapRequest
     * }
     */
    private function createRelationshipContext(): array
    {
        $hrd = $this->createUser(
            'hrd@relationship.test',
            'HRD Pengujian',
            'hrd'
        );

        $employeeUser = $this->createUser(
            'employee@relationship.test',
            'Karyawan Pengujian',
            'employee'
        );

        $partnerUser = $this->createUser(
            'partner@relationship.test',
            'Karyawan Pasangan',
            'employee'
        );

        $branch = Branch::query()->create([
            'code' => 'CB02',
            'name' => 'Kantor Cabang 02',
            'address' => 'Jalan Brigjen Zein Hamid, Kompleks Katamso Indah No. A9',
            'latitude' => 3.53600000,
            'longitude' => 98.69000000,
            'geofence_radius' => 30,
            'maximum_accuracy' => 25,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'KRY-001',
            'full_name' => 'Karyawan Pengujian',
            'position' => 'Karyawan Cabang 02',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        $partnerEmployee = Employee::query()->create([
            'user_id' => $partnerUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'KRY-002',
            'full_name' => 'Karyawan Pasangan',
            'position' => 'Karyawan Cabang 02',
            'phone_number' => null,
            'employment_status' => 'active',
        ]);

        $workSchedule = WorkSchedule::query()->create([
            'name' => 'Jadwal penuh',
            'check_in_time' => '08:45:00',
            'check_out_time' => '21:30:00',
            'check_in_open_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);

        $employeeSchedule = EmployeeSchedule::query()->create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $workSchedule->id,
            'schedule_date' => '2026-07-21',
            'schedule_status' => 'work',
            'approved_by' => $hrd->id,
            'notes' => null,
        ]);

        $attendanceSession = AttendanceSession::query()->create([
            'branch_id' => $branch->id,
            'attendance_type' => 'check_in',
            'session_date' => '2026-07-21',
            'start_time' => '2026-07-21 08:15:00',
            'end_time' => '2026-07-21 09:00:00',
            'encrypted_secret' => 'secret-pengujian-relasi',
            'status' => 'active',
            'created_by' => $hrd->id,
            'closed_at' => null,
        ]);

        $attendance = Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_session_id' => $attendanceSession->id,
            'employee_schedule_id' => $employeeSchedule->id,
            'branch_id' => $branch->id,
            'attendance_type' => 'check_in',
            'attendance_date' => '2026-07-21',
            'attendance_time' => '2026-07-21 08:45:00',
            'latitude' => 3.53600000,
            'longitude' => 98.69000000,
            'accuracy' => 10,
            'distance' => 5,
            'geofence_radius' => 30,
            'attendance_status' => 'present',
            'punctuality_status' => 'on_time',
            'validation_status' => 'accepted',
        ]);

        $validationLog = ValidationLog::query()->create([
            'user_id' => $employeeUser->id,
            'attendance_session_id' => $attendanceSession->id,
            'validation_type' => 'totp',
            'status' => 'accepted',
            'reason' => 'Token dan lokasi berhasil divalidasi.',
            'latitude' => 3.53600000,
            'longitude' => 98.69000000,
            'accuracy' => 10,
            'distance' => 5,
        ]);

        $correctionLog = CorrectionLog::query()->create([
            'attendance_id' => $attendance->id,
            'changed_by' => $hrd->id,
            'approved_by' => $hrd->id,
            'before_data' => [
                'attendance_time' => '2026-07-21 08:47:00',
                'punctuality_status' => 'on_time',
            ],
            'after_data' => [
                'attendance_time' => '2026-07-21 08:45:00',
                'punctuality_status' => 'on_time',
            ],
            'reason' => 'Koreksi waktu berdasarkan pemeriksaan HRD.',
        ]);

        $scheduleSwapRequest = ScheduleSwapRequest::query()->create([
            'requester_employee_id' => $employee->id,
            'partner_employee_id' => $partnerEmployee->id,
            'requester_date' => '2026-07-22',
            'partner_date' => '2026-07-23',
            'reason' => 'Pertukaran jadwal untuk kebutuhan operasional.',
            'status' => 'approved',
            'approved_by' => $hrd->id,
            'approved_at' => '2026-07-21 10:00:00',
        ]);

        return [
            'hrd' => $hrd,
            'employeeUser' => $employeeUser,
            'partnerUser' => $partnerUser,
            'branch' => $branch,
            'employee' => $employee,
            'partnerEmployee' => $partnerEmployee,
            'workSchedule' => $workSchedule,
            'employeeSchedule' => $employeeSchedule,
            'attendanceSession' => $attendanceSession,
            'attendance' => $attendance,
            'validationLog' => $validationLog,
            'correctionLog' => $correctionLog,
            'scheduleSwapRequest' => $scheduleSwapRequest,
        ];
    }

    private function createUser(
        string $email,
        string $name,
        string $role
    ): User {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('Presensi123!'),
            'role' => $role,
            'status' => 'active',
            'last_login_at' => null,
        ]);
    }
}
