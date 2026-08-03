<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class DashboardController extends Controller
{
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
        $dashboardDateLabel = null;
        $dashboardScopeLabel = null;

        if ($user->hasRole('hrd') || $user->hasRole('admin')) {
            $timezone = (string) config(
                'app.timezone',
                'Asia/Jakarta'
            );

            $today = CarbonImmutable::now($timezone);
            $todayDate = $today->toDateString();

            $branchIds = $this->scopedBranchIds($user);

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

                'today_attendances' => Attendance::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereDate('attendance_date', $todayDate)
                    ->where('validation_status', 'accepted')
                    ->count(),
            ];

            $attendanceSummary = [
                'on_time' => Attendance::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereDate('attendance_date', $todayDate)
                    ->where('validation_status', 'accepted')
                    ->where('attendance_type', 'check_in')
                    ->where('punctuality_status', 'on_time')
                    ->count(),

                'late' => Attendance::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereDate('attendance_date', $todayDate)
                    ->where('validation_status', 'accepted')
                    ->where('attendance_type', 'check_in')
                    ->where('punctuality_status', 'late')
                    ->count(),

                'check_out' => Attendance::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereDate('attendance_date', $todayDate)
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
                    ) use ($todayDate): void {
                        $query
                            ->whereDate(
                                'attendance_date',
                                $todayDate
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
                ->whereDate('attendance_date', $todayDate)
                ->orderByDesc('attendance_time')
                ->limit(8)
                ->get();

            $dashboardDateLabel = $today
                ->locale('id')
                ->translatedFormat('d F Y');

            $dashboardScopeLabel = $user->hasRole('hrd')
                ? 'Seluruh cabang'
                : (Branch::query()
                    ->whereKey($user->branch_id)
                    ->value('name')
                    ?? 'Cabang belum ditetapkan');
        }

        return view('dashboard', [
            'dashboardStats' => $dashboardStats,
            'attendanceSummary' => $attendanceSummary,
            'branchAttendance' => $branchAttendance,
            'recentAttendances' => $recentAttendances,
            'dashboardDateLabel' => $dashboardDateLabel,
            'dashboardScopeLabel' => $dashboardScopeLabel,
        ]);
    }

    /**
     * @return Collection<int, int>
     */
    private function scopedBranchIds(User $user): Collection
    {
        if ($user->hasRole('hrd')) {
            return Branch::query()
                ->pluck('id')
                ->map(
                    static fn (mixed $id): int => (int) $id
                );
        }

        if (
            ! $user->hasRole('admin')
            || $user->branch_id === null
        ) {
            return collect();
        }

        return collect([(int) $user->branch_id]);
    }
}
