<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WeeklyScheduleItem;
use App\Models\WorkSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WeeklyRosterService
{
    /**
     * Menyimpan roster mingguan sebagai draft.
     *
     * @param array{
     *     branch_id: int,
     *     week_start_date: string,
     *     items: array<int, array{
     *         employee_id: int,
     *         work_schedule_id: int|null,
     *         schedule_date: string,
     *         schedule_status: string,
     *         notes: string|null
     *     }>
     * } $validated
     */
    public function createDraft(
        array $validated,
        User $creator
    ): WeeklySchedule {
        $this->ensureActiveHrd($creator);

        return DB::transaction(
            function () use (
                $validated,
                $creator
            ): WeeklySchedule {
                $branchId = (int) $validated['branch_id'];

                $weekStartDate =
                    (string) $validated['week_start_date'];

                $duplicateExists =
                    WeeklySchedule::query()
                        ->where('branch_id', $branchId)
                        ->whereDate(
                            'week_start_date',
                            $weekStartDate
                        )
                        ->lockForUpdate()
                        ->exists();

                if ($duplicateExists) {
                    throw ValidationException::withMessages([
                        'week_start_date' => 'Roster untuk cabang dan minggu tersebut sudah tersedia.',
                    ]);
                }

                $workScheduleIds = collect(
                    $validated['items']
                )
                    ->pluck('work_schedule_id')
                    ->filter(
                        static fn (mixed $id): bool => is_int($id) && $id > 0
                    )
                    ->unique()
                    ->values()
                    ->all();

                $workSchedules =
                    WorkSchedule::query()
                        ->whereKey($workScheduleIds)
                        ->get()
                        ->keyBy(
                            static fn (
                                WorkSchedule $workSchedule
                            ): int => (int) $workSchedule
                                ->getKey()
                        );

                $weekEndDate = now()
                    ->createFromFormat(
                        'Y-m-d',
                        $weekStartDate,
                        config(
                            'app.timezone',
                            'Asia/Jakarta'
                        )
                    )
                    ->addDays(6)
                    ->toDateString();

                $weeklySchedule =
                    WeeklySchedule::query()->create([
                        'branch_id' => $branchId,

                        'week_start_date' => $weekStartDate,

                        'week_end_date' => $weekEndDate,

                        'status' => 'draft',

                        'created_by' => $creator->getKey(),

                        'published_by' => null,

                        'published_at' => null,
                    ]);

                foreach (
                    $validated['items'] as $item
                ) {
                    $status =
                        (string) $item[
                            'schedule_status'
                        ];

                    $workScheduleId =
                        $item['work_schedule_id'];

                    $workSchedule = null;

                    if ($status === 'work') {
                        $workSchedule =
                            $workSchedules->get(
                                (int) $workScheduleId
                            );

                        if ($workSchedule === null) {
                            throw ValidationException::withMessages([
                                'items' => 'Pola jadwal kerja salah satu item roster tidak ditemukan.',
                            ]);
                        }
                    }

                    $weeklyScheduleItem =
    new WeeklyScheduleItem;

                    $weeklyScheduleItem->forceFill(
                        array_merge(
                            [
                                'employee_id' => (int) $item[
                                        'employee_id'
                                    ],

                                'work_schedule_id' => $workSchedule?->getKey(),

                                'schedule_date' => (string) $item[
                                        'schedule_date'
                                    ],

                                'schedule_status' => $status,

                                'notes' => $item['notes'],
                            ],

                            $this->snapshotAttributes(
                                $workSchedule
                            )
                        )
                    );

                    $weeklySchedule
                        ->items()
                        ->save($weeklyScheduleItem);
                }

                return $weeklySchedule->load([
                    'branch',
                    'creator',
                    'items.employee',
                    'items.workSchedule',
                ]);
            },
            3
        );
    }

    /**
     * Memublikasikan draft roster menjadi jadwal harian.
     */
    public function publish(
        WeeklySchedule $weeklySchedule,
        User $publisher
    ): WeeklySchedule {
        $this->ensureActiveHrd($publisher);

        return DB::transaction(
            function () use (
                $weeklySchedule,
                $publisher
            ): WeeklySchedule {
                $lockedRoster =
                    WeeklySchedule::query()
                        ->whereKey(
                            $weeklySchedule->getKey()
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (! $lockedRoster->isDraft()) {
                    throw ValidationException::withMessages([
                        'publish' => 'Hanya roster berstatus draft yang dapat dipublikasikan.',
                    ]);
                }

                $lockedRoster->load([
                    'items' => static function (
                        $query
                    ): void {
                        $query
                            ->orderBy('schedule_date')
                            ->orderBy('employee_id');
                    },
                ]);

                if ($lockedRoster->items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'publish' => 'Roster tidak memiliki item untuk dipublikasikan.',
                    ]);
                }

                $conflictingItems = [];

                foreach (
                    $lockedRoster->items as $item
                ) {
                    $scheduleDate =
                        $this->dateString(
                            $item->schedule_date
                        );

                    $existingSchedule =
                        EmployeeSchedule::query()
                            ->where(
                                'employee_id',
                                $item->employee_id
                            )
                            ->whereDate(
                                'schedule_date',
                                $scheduleDate
                            )
                            ->lockForUpdate()
                            ->first();

                    if ($existingSchedule !== null) {
                        $conflictingItems[] = sprintf(
                            'karyawan #%d pada %s',
                            (int) $item->employee_id,
                            $scheduleDate
                        );
                    }
                }

                if ($conflictingItems !== []) {
                    throw ValidationException::withMessages([
                        'publish' => sprintf(
                            'Publikasi dibatalkan karena jadwal harian sudah tersedia untuk %s.',
                            implode(
                                ', ',
                                $conflictingItems
                            )
                        ),
                    ]);
                }

                foreach (
                    $lockedRoster->items as $item
                ) {
                    EmployeeSchedule::query()->create([
                        'employee_id' => $item->employee_id,

                        'work_schedule_id' => $item->work_schedule_id,

                        'weekly_schedule_item_id' => $item->getKey(),

                        'schedule_date' => $this->dateString(
                            $item->schedule_date
                        ),

                        'schedule_status' => $item->schedule_status,

                        'approved_by' => $publisher->getKey(),

                        'notes' => $item->notes,
                    ]);
                }

                $lockedRoster->forceFill([
                    'status' => 'published',

                    'published_by' => $publisher->getKey(),

                    'published_at' => now(
                        config(
                            'app.timezone',
                            'Asia/Jakarta'
                        )
                    ),
                ])->save();

                return $lockedRoster->fresh([
                    'branch',
                    'creator',
                    'publisher',
                    'items.employee',
                    'items.workSchedule',
                    'items.employeeSchedule',
                ]);
            },
            3
        );
    }

    /**
     * Membuat snapshot konfigurasi pola kerja.
     *
     * @return array{
     *     work_schedule_name_snapshot: string|null,
     *     check_in_time_snapshot: mixed,
     *     check_out_time_snapshot: mixed,
     *     check_in_open_minutes_snapshot: int|null,
     *     late_tolerance_minutes_snapshot: int|null,
     *     check_in_limit_minutes_snapshot: int|null,
     *     check_out_limit_minutes_snapshot: int|null
     * }
     */
    private function snapshotAttributes(
        ?WorkSchedule $workSchedule
    ): array {
        if ($workSchedule === null) {
            return [
                'work_schedule_name_snapshot' => null,

                'check_in_time_snapshot' => null,

                'check_out_time_snapshot' => null,

                'check_in_open_minutes_snapshot' => null,

                'late_tolerance_minutes_snapshot' => null,

                'check_in_limit_minutes_snapshot' => null,

                'check_out_limit_minutes_snapshot' => null,
            ];
        }

        return [
            'work_schedule_name_snapshot' => $workSchedule->name,

            'check_in_time_snapshot' => $workSchedule->getRawOriginal(
                'check_in_time'
            ),

            'check_out_time_snapshot' => $workSchedule->getRawOriginal(
                'check_out_time'
            ),

            'check_in_open_minutes_snapshot' => (int) $workSchedule
                ->check_in_open_minutes,

            'late_tolerance_minutes_snapshot' => (int) $workSchedule
                ->late_tolerance_minutes,

            'check_in_limit_minutes_snapshot' => (int) $workSchedule
                ->check_in_limit_minutes,

            'check_out_limit_minutes_snapshot' => (int) $workSchedule
                ->check_out_limit_minutes,
        ];
    }

    /**
     * Menjamin mutasi roster hanya dilakukan HRD aktif.
     */
    private function ensureActiveHrd(
        User $user
    ): void {
        if (
            ! $user->hasRole('hrd')
            || ! $user->isActive()
        ) {
            throw ValidationException::withMessages([
                'authorization' => 'Hanya HRD aktif yang dapat mengelola roster mingguan.',
            ]);
        }
    }

    /**
     * Mengubah cast tanggal menjadi Y-m-d.
     */
    private function dateString(
        mixed $value
    ): string {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return substr(
            (string) $value,
            0,
            10
        );
    }
}
