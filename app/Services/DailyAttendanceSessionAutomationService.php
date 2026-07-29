<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\EmployeeSchedule;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class DailyAttendanceSessionAutomationService
{
    public function __construct(
        private readonly AttendanceScheduleService $scheduleService,
        private readonly TotpService $totpService
    ) {}

    /**
     * Membentuk satu sesi otomatis per cabang dan tanggal
     * berdasarkan jadwal harian karyawan.
     *
     * @return array{
     *     branches: int,
     *     created: int,
     *     reused: int,
     *     failed: int,
     *     failures: list<string>
     * }
     */
    public function generate(
        DateTimeInterface|string $date
    ): array {
        $dateString = $this->dateString($date);

        $schedules = EmployeeSchedule::query()
            ->with([
                'employee.branch',
                'workSchedule',
                'weeklyScheduleItem',
            ])
            ->whereDate(
                'schedule_date',
                $dateString
            )
            ->where(
                'schedule_status',
                'work'
            )
            ->whereHas(
                'employee',
                static function ($query): void {
                    $query
                        ->where(
                            'employment_status',
                            'active'
                        )
                        ->whereHas(
                            'branch',
                            static function ($branchQuery): void {
                                $branchQuery->where(
                                    'status',
                                    'active'
                                );
                            }
                        );
                }
            )
            ->orderBy('employee_id')
            ->get();

        $groups = $schedules->groupBy(
            static fn (
                EmployeeSchedule $schedule
            ): int => (int) $schedule
                ->employee
                ->branch_id
        );

        $summary = [
            'branches' => $groups->count(),
            'created' => 0,
            'reused' => 0,
            'failed' => 0,
            'failures' => [],
        ];

        foreach ($groups as $branchId => $branchSchedules) {
            try {
                $session = $this->createForBranch(
                    (int) $branchId,
                    $dateString,
                    $branchSchedules
                );

                if ($session->wasRecentlyCreated) {
                    $summary['created']++;

                    continue;
                }

                $summary['reused']++;
            } catch (Throwable $exception) {
                report($exception);

                $summary['failed']++;
                $summary['failures'][] = sprintf(
                    'Cabang #%d: %s',
                    $branchId,
                    $exception->getMessage()
                );
            }
        }

        return $summary;
    }

    /**
     * @param  Collection<int, EmployeeSchedule>  $schedules
     */
    private function createForBranch(
        int $branchId,
        string $dateString,
        Collection $schedules
    ): AttendanceSession {
        if ($schedules->isEmpty()) {
            throw ValidationException::withMessages([
                'employee_schedules' => 'Jadwal harian kerja tidak tersedia.',
            ]);
        }

        $windows = $schedules->map(
            fn (
                EmployeeSchedule $schedule
            ): array => $this->scheduleService
                ->timeWindow($schedule)
        );

        $startTime = $windows
            ->sortBy(
                static fn (array $window): int => $window['check_in_opens_at']
                    ->getTimestamp()
            )
            ->first()['check_in_opens_at'];

        $endTime = $windows
            ->sortByDesc(
                static fn (array $window): int => $window['check_out_limit_at']
                    ->getTimestamp()
            )
            ->first()['check_out_limit_at'];

        $weeklyScheduleId =
            $this->weeklyScheduleId($schedules);

        $automationKey = sprintf(
            'AUTO:%d:%s',
            $branchId,
            $dateString
        );

        return DB::transaction(
            function () use (
                $branchId,
                $dateString,
                $startTime,
                $endTime,
                $weeklyScheduleId,
                $automationKey
            ): AttendanceSession {
                $branch = Branch::query()
                    ->whereKey($branchId)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($branch === null) {
                    throw ValidationException::withMessages([
                        'branch' => 'Cabang jadwal tidak aktif atau tidak ditemukan.',
                    ]);
                }

                $existing = AttendanceSession::query()
                    ->where(
                        'automation_key',
                        $automationKey
                    )
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    $this->assertExistingMatches(
                        $existing,
                        $branch,
                        $dateString,
                        $startTime,
                        $endTime,
                        $weeklyScheduleId
                    );

                    return $existing;
                }

                $conflict = AttendanceSession::query()
                    ->where(
                        'branch_id',
                        $branch->getKey()
                    )
                    ->whereDate(
                        'session_date',
                        $dateString
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->lockForUpdate()
                    ->first();

                if ($conflict !== null) {
                    throw ValidationException::withMessages([
                        'attendance_session' => 'Cabang sudah memiliki sesi presensi aktif pada tanggal tersebut.',
                    ]);
                }

                return AttendanceSession::query()->create([
                    'branch_id' => $branch->getKey(),

                    'weekly_schedule_id' => $weeklyScheduleId,

                    'attendance_type' => AttendanceSession::TYPE_AUTO,

                    'session_source' => AttendanceSession::SOURCE_AUTOMATIC,

                    'automation_key' => $automationKey,
                    'session_date' => $dateString,
                    'start_time' => $startTime,
                    'end_time' => $endTime,

                    'encrypted_secret' => $this->totpService
                        ->generateSecret(),

                    'status' => 'active',
                    'created_by' => null,
                    'closed_at' => null,
                ]);
            },
            3
        );
    }

    /**
     * Mempertahankan relasi roster lama ketika seluruh
     * jadwal harian berasal dari satu roster yang sama.
     *
     * @param  Collection<int, EmployeeSchedule>  $schedules
     */
    private function weeklyScheduleId(
        Collection $schedules
    ): ?int {
        $containsNonWeekly = $schedules->contains(
            static fn (
                EmployeeSchedule $schedule
            ): bool => $schedule
                ->weekly_schedule_item_id === null
        );

        if ($containsNonWeekly) {
            return null;
        }

        $weeklyIds = $schedules
            ->map(
                static fn (
                    EmployeeSchedule $schedule
                ): ?int => $schedule
                    ->weeklyScheduleItem
                    ?->weekly_schedule_id
            )
            ->filter()
            ->unique()
            ->values();

        if ($weeklyIds->count() !== 1) {
            return null;
        }

        return (int) $weeklyIds->first();
    }

    private function assertExistingMatches(
        AttendanceSession $session,
        Branch $branch,
        string $dateString,
        CarbonImmutable $startTime,
        CarbonImmutable $endTime,
        ?int $weeklyScheduleId
    ): void {
        $matches =
            (int) $session->branch_id
                === (int) $branch->getKey()
            && $session->isAutoType()
            && $session->isAutomatic()
            && $session->session_date
                ->format('Y-m-d') === $dateString
            && $session->start_time
                ->format('Y-m-d H:i:s')
                === $startTime->format('Y-m-d H:i:s')
            && $session->end_time
                ->format('Y-m-d H:i:s')
                === $endTime->format('Y-m-d H:i:s')
            && (
                $session->weekly_schedule_id === null
                    ? $weeklyScheduleId === null
                    : (int) $session->weekly_schedule_id
                        === $weeklyScheduleId
            );

        if ($matches) {
            return;
        }

        throw ValidationException::withMessages([
            'attendance_session' => 'Sesi otomatis yang sudah ada tidak sesuai dengan jadwal harian terkini.',
        ]);
    }

    private function dateString(
        DateTimeInterface|string $date
    ): string {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return CarbonImmutable::parse(
            $date,
            (string) config(
                'app.timezone',
                'Asia/Jakarta'
            )
        )->format('Y-m-d');
    }
}
