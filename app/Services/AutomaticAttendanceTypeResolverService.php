<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\EmployeeSchedule;
use LogicException;

final class AutomaticAttendanceTypeResolverService
{
    public const CHECK_IN = 'check_in';

    public const CHECK_OUT = 'check_out';

    /**
     * Menentukan jenis presensi aktual untuk sesi.
     *
     * Sesi manual mempertahankan jenis yang ditetapkan.
     * Sesi otomatis memilih presensi masuk terlebih dahulu,
     * kemudian presensi pulang setelah masuk sudah tercatat.
     *
     * Nilai null berarti presensi masuk dan pulang sudah lengkap.
     */
    public function resolve(
        AttendanceSession $attendanceSession,
        EmployeeSchedule $employeeSchedule
    ): ?string {
        if ($attendanceSession->isCheckIn()) {
            return self::CHECK_IN;
        }

        if ($attendanceSession->isCheckOut()) {
            return self::CHECK_OUT;
        }

        if (
            ! $attendanceSession->isAutoType()
            || ! $attendanceSession->isAutomatic()
        ) {
            throw new LogicException(
                'Jenis atau sumber sesi presensi tidak didukung.'
            );
        }

        $recordedTypes = Attendance::query()
            ->where(
                'employee_schedule_id',
                $employeeSchedule->getKey()
            )
            ->whereIn(
                'attendance_type',
                [
                    self::CHECK_IN,
                    self::CHECK_OUT,
                ]
            )
            ->lockForUpdate()
            ->pluck('attendance_type')
            ->all();

        if (
            ! in_array(
                self::CHECK_IN,
                $recordedTypes,
                true
            )
        ) {
            return self::CHECK_IN;
        }

        if (
            ! in_array(
                self::CHECK_OUT,
                $recordedTypes,
                true
            )
        ) {
            return self::CHECK_OUT;
        }

        return null;
    }
}
