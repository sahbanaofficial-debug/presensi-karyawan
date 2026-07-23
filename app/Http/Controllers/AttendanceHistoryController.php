<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class AttendanceHistoryController extends Controller
{
    private const ITEMS_PER_PAGE = 15;

    /**
     * Menampilkan riwayat presensi karyawan
     * yang sedang login.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_if(
            $user === null,
            401
        );

        $employee = Employee::query()
            ->with('branch')
            ->where(
                'user_id',
                $user->getKey()
            )
            ->where(
                'employment_status',
                'active'
            )
            ->firstOrFail();

        $attendanceType = strtolower(
            trim(
                (string) $request->query(
                    'attendance_type',
                    ''
                )
            )
        );

        if (
            ! in_array(
                $attendanceType,
                [
                    'check_in',
                    'check_out',
                ],
                true
            )
        ) {
            $attendanceType = '';
        }

        $attendanceDate = $this->validDateOrNull(
            (string) $request->query(
                'attendance_date',
                ''
            )
        );

        $attendances = Attendance::query()
            ->where(
                'employee_id',
                $employee->getKey()
            )
            ->when(
                $attendanceType !== '',
                fn (Builder $query) => $query->where(
                    'attendance_type',
                    $attendanceType
                )
            )
            ->when(
                $attendanceDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'attendance_date',
                    $attendanceDate
                )
            )
            ->orderByDesc('attendance_date')
            ->orderByDesc('attendance_time')
            ->paginate(self::ITEMS_PER_PAGE)
            ->withQueryString();

        $summary = [
            'total' => Attendance::query()
                ->where(
                    'employee_id',
                    $employee->getKey()
                )
                ->count(),

            'check_in' => Attendance::query()
                ->where(
                    'employee_id',
                    $employee->getKey()
                )
                ->where(
                    'attendance_type',
                    'check_in'
                )
                ->count(),

            'check_out' => Attendance::query()
                ->where(
                    'employee_id',
                    $employee->getKey()
                )
                ->where(
                    'attendance_type',
                    'check_out'
                )
                ->count(),

            'late' => Attendance::query()
                ->where(
                    'employee_id',
                    $employee->getKey()
                )
                ->where(
                    'punctuality_status',
                    'late'
                )
                ->count(),
        ];

        return view(
            'attendance.history',
            [
                'employee' => $employee,

                'branch' => $employee->branch,

                'attendances' => $attendances,

                'summary' => $summary,

                'selectedAttendanceType' => $attendanceType,

                'selectedAttendanceDate' => $attendanceDate,
            ]
        );
    }

    /**
     * Memeriksa format tanggal filter.
     */
    private function validDateOrNull(
        string $value
    ): ?string {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat(
                '!Y-m-d',
                $value,
                (string) config(
                    'app.timezone',
                    'Asia/Jakarta'
                )
            );
        } catch (Throwable) {
            return null;
        }

        if (
            $date === false
            || $date->format('Y-m-d') !== $value
        ) {
            return null;
        }

        return $value;
    }
}
