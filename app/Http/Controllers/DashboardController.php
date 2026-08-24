<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    private const PERIOD_WEEK = 'week';

    private const PERIOD_TODAY = 'today';

    /**
     * Menampilkan dashboard sesuai peran dan ruang lingkup cabang pengguna.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $dashboardStats = null;
        $attendanceSummary = null;
        $branchAttendance = collect();
        $recentAttendances = collect();
        $dashboardBranches = collect();
        $dashboardDateLabel = null;
        $dashboardScopeLabel = null;
        $dashboardPeriodKey = self::PERIOD_WEEK;

        /*
         * EMPLOYEE TODAY DASHBOARD V1
         * Ringkasan jadwal dan presensi karyawan hari ini.
         */
        $employeeTodaySchedule = null;
        $employeeTodayCheckIn = null;
        $employeeTodayCheckOut = null;

        if (
            $user?->role === 'employee'
            && $user->employee !== null
        ) {
            $today = now('Asia/Jakarta')->toDateString();

            $employeeTodaySchedule =
                EmployeeSchedule::query()
                    ->with('workSchedule')
                    ->where(
                        'employee_id',
                        $user->employee->id
                    )
                    ->whereDate(
                        'schedule_date',
                        $today
                    )
                    ->first();

            if ($employeeTodaySchedule !== null) {
                $todayAttendances =
                    Attendance::query()
                        ->where(
                            'employee_schedule_id',
                            $employeeTodaySchedule->id
                        )
                        ->where(
                            'validation_status',
                            'accepted'
                        )
                        ->get();

                $employeeTodayCheckIn =
                    $todayAttendances->firstWhere(
                        'attendance_type',
                        'check_in'
                    );

                $employeeTodayCheckOut =
                    $todayAttendances->firstWhere(
                        'attendance_type',
                        'check_out'
                    );
            }
        }

        $dashboardPeriodLabel = 'Minggu ini';
        $selectedDashboardBranchId = null;

        if ($user->hasRole('hrd') || $user->hasRole('admin')) {
            $timezone = (string) config(
                'app.timezone',
                'Asia/Jakarta'
            );

            $today = Date::now($timezone)->toImmutable();
            $dashboardPeriodKey = $this->resolvePeriod(
                $request->query('period')
            );

            [$periodStart, $periodEnd] = $this->periodRange(
                $today,
                $dashboardPeriodKey
            );

            $dashboardBranches = $this->availableBranches($user);

            $selectedDashboardBranchId =
                $this->selectedBranchId(
                    $user,
                    $request,
                    $dashboardBranches
                );

            $branchIds = $this->scopedBranchIds(
                $user,
                $dashboardBranches,
                $selectedDashboardBranchId
            );

            $startDate = $periodStart->toDateString();
            $endDate = $periodEnd->toDateString();

            $dashboardStats = [
                'active_branches' => Branch::query()
                    ->whereIn('id', $branchIds)
                    ->where('status', 'active')
                    ->count(),

                'active_employees' => Employee::query()
                    ->whereIn('branch_id', $branchIds)
                    ->where('employment_status', 'active')
                    ->count(),

                'active_terminals' => BranchTerminal::query()
                    ->whereIn('branch_id', $branchIds)
                    ->where(
                        'status',
                        BranchTerminal::STATUS_ACTIVE
                    )
                    ->count(),

                'period_attendances' => Attendance::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereDate(
                        'attendance_date',
                        '>=',
                        $startDate
                    )
                    ->whereDate(
                        'attendance_date',
                        '<=',
                        $endDate
                    )
                    ->where('validation_status', 'accepted')
                    ->count(),
            ];

            // Dipertahankan untuk kompatibilitas test dan view lama.
            $dashboardStats['today_attendances'] =
                $dashboardStats['period_attendances'];

            $attendanceSummary = [
                'on_time' => Attendance::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereDate(
                        'attendance_date',
                        '>=',
                        $startDate
                    )
                    ->whereDate(
                        'attendance_date',
                        '<=',
                        $endDate
                    )
                    ->where('validation_status', 'accepted')
                    ->where('attendance_type', 'check_in')
                    ->where('punctuality_status', 'on_time')
                    ->count(),

                'late' => Attendance::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereDate(
                        'attendance_date',
                        '>=',
                        $startDate
                    )
                    ->whereDate(
                        'attendance_date',
                        '<=',
                        $endDate
                    )
                    ->where('validation_status', 'accepted')
                    ->where('attendance_type', 'check_in')
                    ->where('punctuality_status', 'late')
                    ->count(),

                'check_out' => Attendance::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereDate(
                        'attendance_date',
                        '>=',
                        $startDate
                    )
                    ->whereDate(
                        'attendance_date',
                        '<=',
                        $endDate
                    )
                    ->where('validation_status', 'accepted')
                    ->where('attendance_type', 'check_out')
                    ->count(),
            ];

            $attendanceSummary['total'] = array_sum(
                $attendanceSummary
            );

            $branchAttendance = Branch::query()
                ->whereIn('id', $branchIds)
                ->where('status', 'active')
                ->withCount([
                    'attendances as attendance_count' => static function (
                        Builder $query
                    ) use ($startDate, $endDate): void {
                        $query
                            ->whereDate(
                                'attendance_date',
                                '>=',
                                $startDate
                            )
                            ->whereDate(
                                'attendance_date',
                                '<=',
                                $endDate
                            )
                            ->where(
                                'validation_status',
                                'accepted'
                            );
                    },
                ])
                ->orderByDesc('attendance_count')
                ->orderBy('code')
                ->get([
                    'id',
                    'code',
                    'name',
                ]);

            $recentAttendances = Attendance::query()
                ->with([
                    'employee:id,branch_id,employee_number,full_name',
                    'branch:id,code,name',
                ])
                ->whereIn('branch_id', $branchIds)
                ->whereDate(
                    'attendance_date',
                    '>=',
                    $startDate
                )
                ->whereDate(
                    'attendance_date',
                    '<=',
                    $endDate
                )
                ->orderByDesc('attendance_date')
                ->orderByDesc('attendance_time')
                ->limit(8)
                ->get();

            $dashboardPeriodLabel =
                $dashboardPeriodKey === self::PERIOD_TODAY
                    ? 'Hari ini'
                    : 'Minggu ini';

            $dashboardDateLabel = $this->periodDateLabel(
                $periodStart,
                $periodEnd,
                $dashboardPeriodKey
            );

            $dashboardScopeLabel = $this->scopeLabel(
                $user,
                $dashboardBranches,
                $selectedDashboardBranchId
            );
        }

        return view('dashboard', [
            'dashboardStats' => $dashboardStats,
            'attendanceSummary' => $attendanceSummary,
            'branchAttendance' => $branchAttendance,
            'recentAttendances' => $recentAttendances,
            'dashboardBranches' => $dashboardBranches,
            'dashboardDateLabel' => $dashboardDateLabel,
            'dashboardScopeLabel' => $dashboardScopeLabel,
            'dashboardPeriodKey' => $dashboardPeriodKey,
            'employeeTodaySchedule' => $employeeTodaySchedule,
            'employeeTodayCheckIn' => $employeeTodayCheckIn,
            'employeeTodayCheckOut' => $employeeTodayCheckOut,
            'dashboardPeriodLabel' => $dashboardPeriodLabel,
            'selectedDashboardBranchId' => $selectedDashboardBranchId,
        ]);
    }

    private function resolvePeriod(mixed $period): string
    {
        return in_array(
            $period,
            [
                self::PERIOD_WEEK,
                self::PERIOD_TODAY,
            ],
            true
        )
            ? (string) $period
            : self::PERIOD_WEEK;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function periodRange(
        CarbonImmutable $today,
        string $period
    ): array {
        if ($period === self::PERIOD_TODAY) {
            return [
                $today->startOfDay(),
                $today->endOfDay(),
            ];
        }

        return [
            $today->startOfWeek(),
            $today->endOfWeek(),
        ];
    }

    /**
     * @return Collection<int, Branch>
     */
    private function availableBranches(User $user): Collection
    {
        if ($user->hasRole('hrd')) {
            return Branch::query()
                ->where('status', 'active')
                ->orderBy('code')
                ->get([
                    'id',
                    'code',
                    'name',
                ]);
        }

        if (
            ! $user->hasRole('admin')
            || $user->branch_id === null
        ) {
            return collect();
        }

        return Branch::query()
            ->whereKey($user->branch_id)
            ->get([
                'id',
                'code',
                'name',
            ]);
    }

    /**
     * @param  Collection<int, Branch>  $availableBranches
     */
    private function selectedBranchId(
        User $user,
        Request $request,
        Collection $availableBranches
    ): ?int {
        if ($user->hasRole('admin')) {
            return $user->branch_id === null
                ? null
                : (int) $user->branch_id;
        }

        if (! $user->hasRole('hrd')) {
            return null;
        }

        $requestedBranchId = filter_var(
            $request->query('branch_id'),
            FILTER_VALIDATE_INT
        );

        if ($requestedBranchId === false) {
            return null;
        }

        return $availableBranches->contains(
            static fn (Branch $branch): bool => (int) $branch->id === (int) $requestedBranchId
        )
            ? (int) $requestedBranchId
            : null;
    }

    /**
     * @param  Collection<int, Branch>  $availableBranches
     * @return Collection<int, int>
     */
    private function scopedBranchIds(
        User $user,
        Collection $availableBranches,
        ?int $selectedBranchId
    ): Collection {
        if ($user->hasRole('hrd')) {
            if ($selectedBranchId !== null) {
                return collect([$selectedBranchId]);
            }

            return $availableBranches
                ->pluck('id')
                ->map(
                    static fn (mixed $id): int => (int) $id
                )
                ->values();
        }

        if (
            ! $user->hasRole('admin')
            || $user->branch_id === null
        ) {
            return collect();
        }

        return collect([(int) $user->branch_id]);
    }

    private function periodDateLabel(
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        string $period
    ): string {
        if ($period === self::PERIOD_TODAY) {
            return $periodStart
                ->locale('id')
                ->translatedFormat('d F Y');
        }

        return sprintf(
            '%s – %s',
            $periodStart
                ->locale('id')
                ->translatedFormat('d F Y'),
            $periodEnd
                ->locale('id')
                ->translatedFormat('d F Y')
        );
    }

    /**
     * @param  Collection<int, Branch>  $availableBranches
     */
    private function scopeLabel(
        User $user,
        Collection $availableBranches,
        ?int $selectedBranchId
    ): string {
        if ($user->hasRole('hrd')) {
            if ($selectedBranchId === null) {
                return 'Seluruh cabang';
            }

            $branch = $availableBranches->first(
                static fn (Branch $item): bool => (int) $item->id === $selectedBranchId
            );

            return $branch === null
                ? 'Seluruh cabang'
                : sprintf(
                    '%s — %s',
                    $branch->code,
                    $branch->name
                );
        }

        $branch = $availableBranches->first();

        return $branch === null
            ? 'Cabang belum ditetapkan'
            : sprintf(
                '%s — %s',
                $branch->code,
                $branch->name
            );
    }
}
