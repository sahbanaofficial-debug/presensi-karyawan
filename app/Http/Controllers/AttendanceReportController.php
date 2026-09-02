<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class AttendanceReportController extends Controller
{
    private const ITEMS_PER_PAGE = 20;

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user !== null
            && in_array($user->role, ['hrd', 'admin'], true),
            403
        );

        $adminBranchId = $this->resolveAdminBranchId($user);
        $today = CarbonImmutable::now(
            (string) config('app.timezone', 'Asia/Jakarta')
        )->startOfDay();
        $defaultFrom = $today->subDays(30);

        $dateFrom = $this->validDateOrNull(
            (string) $request->query('date_from', '')
        ) ?? $defaultFrom->format('Y-m-d');
        $dateTo = $this->validDateOrNull(
            (string) $request->query('date_to', '')
        ) ?? $today->format('Y-m-d');

        [$dateFrom, $dateTo] = $this->normalisePeriod(
            $dateFrom,
            $dateTo
        );

        $requestedBranchId = $this->positiveIntegerOrNull(
            $request->query('branch_id')
        );
        $branchId = $adminBranchId ?? $requestedBranchId;
        $employeeId = $this->positiveIntegerOrNull(
            $request->query('employee_id')
        );

        if ($employeeId !== null) {
            $employeeMatchesScope = Employee::query()
                ->whereKey($employeeId)
                ->when(
                    $branchId !== null,
                    static fn (Builder $query) => $query->where(
                        'branch_id',
                        $branchId
                    )
                )
                ->exists();

            if (! $employeeMatchesScope) {
                $employeeId = null;
            }
        }

        $reportStatus = $this->validEnumOrEmpty(
            (string) $request->query('report_status', ''),
            [
                'present',
                'on_time',
                'late',
                'complete',
                'incomplete',
                'not_recorded',
            ]
        );

        $baseQuery = EmployeeSchedule::query()
            ->where('schedule_status', 'work')
            ->whereDate('schedule_date', '>=', $dateFrom)
            ->whereDate('schedule_date', '<=', $dateTo)
            ->when(
                $branchId !== null,
                static fn (Builder $query) => $query->whereHas(
                    'employee',
                    static fn (Builder $employeeQuery) => $employeeQuery->where('branch_id', $branchId)
                )
            )
            ->when(
                $employeeId !== null,
                static fn (Builder $query) => $query->where(
                    'employee_id',
                    $employeeId
                )
            );

        $summary = [
            'scheduled' => (clone $baseQuery)->count(),
            'present' => $this->withCheckIn(clone $baseQuery)->count(),
            'on_time' => $this->withCheckIn(
                clone $baseQuery,
                'on_time'
            )->count(),
            'late' => $this->withCheckIn(
                clone $baseQuery,
                'late'
            )->count(),
            'complete' => $this->withCheckOut(
                $this->withCheckIn(clone $baseQuery)
            )->count(),
            'not_recorded' => $this->withoutCheckIn(
                clone $baseQuery
            )->count(),
        ];

        $reportQuery = $this->applyStatusFilter(
            clone $baseQuery,
            $reportStatus
        );

        $reports = $reportQuery
            ->with([
                'employee.branch',
                'workSchedule',
                'attendances' => static fn ($query) => $query
                    ->orderBy('attendance_time')
                    ->orderBy('id'),
            ])
            ->orderByDesc('schedule_date')
            ->orderBy('employee_id')
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

        return view('attendance-reports.index', [
            'reports' => $reports,
            'branches' => $branches,
            'employees' => $employees,
            'summary' => $summary,
            'selectedDateFrom' => $dateFrom,
            'selectedDateTo' => $dateTo,
            'selectedBranchId' => $branchId,
            'selectedEmployeeId' => $employeeId,
            'selectedReportStatus' => $reportStatus,
            'isAdmin' => $user->role === 'admin',
        ]);
    }

    private function resolveAdminBranchId(object $user): ?int
    {
        if ($user->role !== 'admin') {
            return null;
        }

        abort_if($user->branch_id === null, 403);

        $branchId = (int) $user->branch_id;

        abort_unless(
            Branch::query()
                ->whereKey($branchId)
                ->where('status', 'active')
                ->exists(),
            403
        );

        return $branchId;
    }

    private function withCheckIn(
        Builder $query,
        ?string $punctualityStatus = null
    ): Builder {
        return $query->whereHas(
            'attendances',
            static function (Builder $attendanceQuery) use (
                $punctualityStatus
            ): void {
                $attendanceQuery->where('attendance_type', 'check_in');

                if ($punctualityStatus !== null) {
                    $attendanceQuery->where(
                        'punctuality_status',
                        $punctualityStatus
                    );
                }
            }
        );
    }

    private function withCheckOut(Builder $query): Builder
    {
        return $query->whereHas(
            'attendances',
            static fn (Builder $attendanceQuery) => $attendanceQuery->where(
                'attendance_type',
                'check_out'
            )
        );
    }

    private function withoutCheckIn(Builder $query): Builder
    {
        return $query->whereDoesntHave(
            'attendances',
            static fn (Builder $attendanceQuery) => $attendanceQuery->where(
                'attendance_type',
                'check_in'
            )
        );
    }

    private function withoutCheckOut(Builder $query): Builder
    {
        return $query->whereDoesntHave(
            'attendances',
            static fn (Builder $attendanceQuery) => $attendanceQuery->where(
                'attendance_type',
                'check_out'
            )
        );
    }

    private function applyStatusFilter(
        Builder $query,
        string $status
    ): Builder {
        return match ($status) {
            'present' => $this->withCheckIn($query),
            'on_time' => $this->withCheckIn($query, 'on_time'),
            'late' => $this->withCheckIn($query, 'late'),
            'complete' => $this->withCheckOut(
                $this->withCheckIn($query)
            ),
            'incomplete' => $this->withoutCheckOut(
                $this->withCheckIn($query)
            ),
            'not_recorded' => $this->withoutCheckIn($query),
            default => $query,
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function normalisePeriod(
        string $dateFrom,
        string $dateTo
    ): array {
        $from = CarbonImmutable::parse($dateFrom);
        $to = CarbonImmutable::parse($dateTo);

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diffInDays($to) > 365) {
            $from = $to->subDays(365);
        }

        return [$from->format('Y-m-d'), $to->format('Y-m-d')];
    }

    private function positiveIntegerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $validatedValue = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        return $validatedValue === false
            ? null
            : (int) $validatedValue;
    }

    /**
     * @param  list<string>  $allowedValues
     */
    private function validEnumOrEmpty(
        string $value,
        array $allowedValues
    ): string {
        $value = strtolower(trim($value));

        return in_array($value, $allowedValues, true)
            ? $value
            : '';
    }

    private function validDateOrNull(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat(
                '!Y-m-d',
                $value,
                (string) config('app.timezone', 'Asia/Jakarta')
            );
        } catch (Throwable) {
            return null;
        }

        return $date !== false && $date->format('Y-m-d') === $value
            ? $value
            : null;
    }
}
