<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class AttendanceMonitoringController extends Controller
{
    private const ITEMS_PER_PAGE = 20;

    /**
     * Menampilkan monitoring presensi untuk HRD
     * dan admin.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user !== null
            && in_array(
                $user->role,
                [
                    'hrd',
                    'admin',
                ],
                true
            ),
            403
        );

        $adminBranchId = null;

        if ($user->role === 'admin') {
            abort_if(
                $user->branch_id === null,
                403
            );

            $adminBranchId = (int) $user->branch_id;

            $adminBranchIsActive = Branch::query()
                ->whereKey($adminBranchId)
                ->where('status', 'active')
                ->exists();

            abort_unless(
                $adminBranchIsActive,
                403
            );
        }

        $attendanceDate = $this->validDateOrNull(
            (string) $request->query(
                'attendance_date',
                ''
            )
        );

        $requestedBranchId = $this->positiveIntegerOrNull(
            $request->query('branch_id')
        );

        $branchId = $adminBranchId
            ?? $requestedBranchId;

        $employeeId = $this->positiveIntegerOrNull(
            $request->query('employee_id')
        );

        if (
            $adminBranchId !== null
            && $employeeId !== null
        ) {
            $employeeBelongsToAdminBranch = Employee::query()
                ->whereKey($employeeId)
                ->where(
                    'branch_id',
                    $adminBranchId
                )
                ->exists();

            if (! $employeeBelongsToAdminBranch) {
                $employeeId = null;
            }
        }

        $attendanceType = $this->validEnumOrEmpty(
            value: (string) $request->query(
                'attendance_type',
                ''
            ),
            allowedValues: [
                'check_in',
                'check_out',
            ]
        );

        $punctualityStatus =
            $this->validEnumOrEmpty(
                value: (string) $request->query(
                    'punctuality_status',
                    ''
                ),
                allowedValues: [
                    'on_time',
                    'late',
                    'not_applicable',
                ]
            );

        $attendanceQuery = Attendance::query()
            ->with([
                'employee',
                'branch',
            ])
            ->when(
                $attendanceDate !== null,
                static fn (Builder $query) => $query->whereDate(
                    'attendance_date',
                    $attendanceDate
                )
            )
            ->when(
                $branchId !== null,
                static fn (Builder $query) => $query->where(
                    'branch_id',
                    $branchId
                )
            )
            ->when(
                $employeeId !== null,
                static fn (Builder $query) => $query->where(
                    'employee_id',
                    $employeeId
                )
            )
            ->when(
                $attendanceType !== '',
                static fn (Builder $query) => $query->where(
                    'attendance_type',
                    $attendanceType
                )
            )
            ->when(
                $punctualityStatus !== '',
                static fn (Builder $query) => $query->where(
                    'punctuality_status',
                    $punctualityStatus
                )
            );

        $summary = [
            'total' => (clone $attendanceQuery)
                ->count(),

            'employees' => (clone $attendanceQuery)
                ->distinct()
                ->count('employee_id'),

            'check_in' => (clone $attendanceQuery)
                ->where(
                    'attendance_type',
                    'check_in'
                )
                ->count(),

            'check_out' => (clone $attendanceQuery)
                ->where(
                    'attendance_type',
                    'check_out'
                )
                ->count(),

            'on_time' => (clone $attendanceQuery)
                ->where(
                    'punctuality_status',
                    'on_time'
                )
                ->count(),

            'late' => (clone $attendanceQuery)
                ->where(
                    'punctuality_status',
                    'late'
                )
                ->count(),
        ];

        $attendances = $attendanceQuery
            ->orderByDesc('attendance_date')
            ->orderByDesc('attendance_time')
            ->orderByDesc('id')
            ->paginate(self::ITEMS_PER_PAGE)
            ->withQueryString();

        $branches = Branch::query()
            ->when(
                $adminBranchId !== null,
                static fn (Builder $query) => $query->whereKey(
                    $adminBranchId
                )
            )
            ->orderBy('name')
            ->get();

        $employees = Employee::query()
            ->with('branch')
            ->when(
                $branchId !== null,
                static fn (Builder $query) => $query->where(
                    'branch_id',
                    $branchId
                )
            )
            ->orderBy('full_name')
            ->get();

        return view(
            'attendance-monitoring.index',
            [
                'attendances' => $attendances,

                'branches' => $branches,

                'employees' => $employees,

                'summary' => $summary,

                'selectedAttendanceDate' => $attendanceDate,

                'selectedBranchId' => $branchId,

                'selectedEmployeeId' => $employeeId,

                'selectedAttendanceType' => $attendanceType,

                'selectedPunctualityStatus' => $punctualityStatus,
            ]
        );
    }

    /**
     * Mengubah nilai menjadi ID positif.
     */
    private function positiveIntegerOrNull(
        mixed $value
    ): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $validatedValue = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($validatedValue === false) {
            return null;
        }

        return (int) $validatedValue;
    }

    /**
     * Memeriksa nilai filter berbentuk enum.
     *
     * @param  array<int, string>  $allowedValues
     */
    private function validEnumOrEmpty(
        string $value,
        array $allowedValues
    ): string {
        $value = strtolower(
            trim($value)
        );

        if (
            ! in_array(
                $value,
                $allowedValues,
                true
            )
        ) {
            return '';
        }

        return $value;
    }

    /**
     * Memeriksa tanggal dengan format Y-m-d.
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
