<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\EmployeeSchedule;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

final class BranchDefaultEmployeeScheduleGeneratorService
{
    /**
     * Membentuk jadwal harian untuk seluruh karyawan aktif
     * berdasarkan pola kerja default cabangnya.
     *
     * Jadwal manual atau roster mingguan yang sudah tersedia
     * tidak ditimpa.
     *
     * @return array{
     *     branches: int,
     *     employees: int,
     *     created: int,
     *     existing: int,
     *     skipped_branches: int
     * }
     */
    public function generate(
        DateTimeInterface|string $date
    ): array {
        $dateString = $this->dateString($date);

        $summary = [
            'branches' => 0,
            'employees' => 0,
            'created' => 0,
            'existing' => 0,
            'skipped_branches' => 0,
        ];

        $branches = Branch::query()
            ->where('status', 'active')
            ->whereNotNull(
                'default_work_schedule_id'
            )
            ->with([
                'defaultWorkSchedule',
                'employees' => static function (
                    HasMany $query
                ): void {
                    $query
                        ->where(
                            'employment_status',
                            'active'
                        )
                        ->orderBy('id');
                },
            ])
            ->orderBy('id')
            ->get();

        foreach ($branches as $branch) {
            $workSchedule =
                $branch->defaultWorkSchedule;

            if (
                $workSchedule === null
                || ! $workSchedule->isActive()
            ) {
                $summary['skipped_branches']++;

                continue;
            }

            $summary['branches']++;

            $branchResult = DB::transaction(
                function () use (
                    $branch,
                    $workSchedule,
                    $dateString
                ): array {
                    $created = 0;
                    $existing = 0;
                    $employees = 0;

                    foreach ($branch->employees as $employee) {
                        $employees++;

                        $existingSchedule =
                            EmployeeSchedule::query()
                                ->where(
                                    'employee_id',
                                    $employee->getKey()
                                )
                                ->whereDate(
                                    'schedule_date',
                                    $dateString
                                )
                                ->first();

                        if ($existingSchedule !== null) {
                            $existing++;

                            continue;
                        }

                        EmployeeSchedule::query()->create([
                            'weekly_schedule_item_id' => null,

                            'employee_id' => $employee->getKey(),

                            'work_schedule_id' => $workSchedule->getKey(),

                            'schedule_date' => $dateString,

                            'schedule_status' => 'work',

                            'schedule_source' => EmployeeSchedule::SOURCE_BRANCH_DEFAULT,

                            'work_schedule_name_snapshot' => $workSchedule->name,

                            'check_in_time_snapshot' => $this->timeSnapshot(
                                $workSchedule
                                    ->check_in_time
                            ),

                            'check_out_time_snapshot' => $this->timeSnapshot(
                                $workSchedule
                                    ->check_out_time
                            ),

                            'check_in_open_minutes_snapshot' => $workSchedule
                                ->check_in_open_minutes,

                            'check_in_limit_minutes_snapshot' => $workSchedule
                                ->check_in_limit_minutes,

                            'late_tolerance_minutes_snapshot' => $workSchedule
                                ->late_tolerance_minutes,

                            'check_out_limit_minutes_snapshot' => $workSchedule
                                ->check_out_limit_minutes,

                            'approved_by' => null,
                            'notes' => null,
                        ]);

                        $created++;
                    }

                    return [
                        'employees' => $employees,
                        'created' => $created,
                        'existing' => $existing,
                    ];
                }
            );

            $summary['employees'] +=
                $branchResult['employees'];

            $summary['created'] +=
                $branchResult['created'];

            $summary['existing'] +=
                $branchResult['existing'];
        }

        return $summary;
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

    private function timeSnapshot(
        mixed $value
    ): string {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i:s');
        }

        return CarbonImmutable::parse(
            (string) $value,
            (string) config(
                'app.timezone',
                'Asia/Jakarta'
            )
        )->format('H:i:s');
    }
}
