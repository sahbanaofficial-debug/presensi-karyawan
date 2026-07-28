<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\WeeklySchedule;
use App\Models\WeeklyScheduleItem;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class WeeklyAttendanceSessionAutomationService
{
    public function __construct(
        private readonly TotpService $totpService
    ) {}

    /**
     * Membuat satu sesi otomatis per cabang dan tanggal kerja.
     *
     * Rentang sesi dimulai dari waktu pembukaan presensi masuk
     * paling awal dan berakhir pada batas presensi pulang paling
     * akhir dari seluruh snapshot pola kerja pada tanggal tersebut.
     *
     * @return Collection<int, AttendanceSession>
     */
    public function createForPublishedRoster(
        WeeklySchedule $weeklySchedule
    ): Collection {
        return DB::transaction(
            function () use (
                $weeklySchedule
            ): Collection {
                $lockedRoster = WeeklySchedule::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $weeklySchedule->getKey()
                    );

                if (! $lockedRoster->isPublished()) {
                    throw ValidationException::withMessages([
                        'weekly_schedule' => 'Sesi otomatis hanya dapat dibuat dari roster yang sudah dipublikasikan.',
                    ]);
                }

                $branch = Branch::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $lockedRoster->branch_id
                    );

                if (
                    ! $branch->isActive()
                    || ! $branch->hasGeofenceConfiguration()
                ) {
                    throw ValidationException::withMessages([
                        'branch' => 'Cabang harus aktif dan memiliki konfigurasi geofence lengkap.',
                    ]);
                }

                $workItems = $lockedRoster
                    ->items()
                    ->where(
                        'schedule_status',
                        'work'
                    )
                    ->orderBy('schedule_date')
                    ->orderBy('id')
                    ->get();

                if ($workItems->isEmpty()) {
                    return collect();
                }

                $sessions = collect();

                $groupedItems = $workItems->groupBy(
                    static fn (
                        WeeklyScheduleItem $item
                    ): string => $item->schedule_date
                        ->format('Y-m-d')
                );

                foreach (
                    $groupedItems as $scheduleDate => $dateItems
                ) {
                    $window = $this->resolveSessionWindow(
                        $scheduleDate,
                        $dateItems
                    );

                    $sessions->push(
                        $this->createOrReuseSession(
                            weeklySchedule: $lockedRoster,

                            branch: $branch,

                            scheduleDate: $scheduleDate,

                            startTime: $window['start'],

                            endTime: $window['end']
                        )
                    );
                }

                return $sessions->values();
            },
            3
        );
    }

    /**
     * @param  Collection<int, WeeklyScheduleItem>  $items
     * @return array{
     *     start: CarbonImmutable,
     *     end: CarbonImmutable
     * }
     */
    private function resolveSessionWindow(
        string $scheduleDate,
        Collection $items
    ): array {
        $starts = collect();
        $ends = collect();

        foreach ($items as $item) {
            $this->assertCompleteSnapshot(
                $item
            );

            $checkIn = $this->dateTimeFromSnapshot(
                $scheduleDate,
                $item->check_in_time_snapshot
            );

            $checkOut = $this->dateTimeFromSnapshot(
                $scheduleDate,
                $item->check_out_time_snapshot
            );

            $starts->push(
                $checkIn->subMinutes(
                    (int) $item
                        ->check_in_open_minutes_snapshot
                )
            );

            $ends->push(
                $checkOut->addMinutes(
                    (int) $item
                        ->check_out_limit_minutes_snapshot
                )
            );
        }

        return [
            'start' => $starts
                ->sortBy(
                    static fn (
                        CarbonImmutable $moment
                    ): int => $moment->getTimestamp()
                )
                ->firstOrFail(),

            'end' => $ends
                ->sortByDesc(
                    static fn (
                        CarbonImmutable $moment
                    ): int => $moment->getTimestamp()
                )
                ->firstOrFail(),
        ];
    }

    private function createOrReuseSession(
        WeeklySchedule $weeklySchedule,
        Branch $branch,
        string $scheduleDate,
        CarbonImmutable $startTime,
        CarbonImmutable $endTime
    ): AttendanceSession {
        $automationKey = sprintf(
            'AUTO:%d:%s',
            $branch->getKey(),
            $scheduleDate
        );

        $existing = AttendanceSession::query()
            ->where(
                'automation_key',
                $automationKey
            )
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            $this->assertExistingSessionMatches(
                attendanceSession: $existing,

                weeklySchedule: $weeklySchedule,

                branch: $branch,

                scheduleDate: $scheduleDate,

                startTime: $startTime,

                endTime: $endTime
            );

            return $existing;
        }

        $conflictingSession = AttendanceSession::query()
            ->where(
                'branch_id',
                $branch->getKey()
            )
            ->whereDate(
                'session_date',
                $scheduleDate
            )
            ->where(
                'status',
                'active'
            )
            ->lockForUpdate()
            ->first();

        if ($conflictingSession !== null) {
            throw ValidationException::withMessages([
                'attendance_session' => 'Sesi presensi aktif untuk cabang dan tanggal tersebut sudah tersedia.',
            ]);
        }

        return AttendanceSession::query()->create([
            'branch_id' => $branch->getKey(),

            'weekly_schedule_id' => $weeklySchedule->getKey(),

            'attendance_type' => AttendanceSession::TYPE_AUTO,

            'session_source' => AttendanceSession::SOURCE_AUTOMATIC,

            'automation_key' => $automationKey,

            'session_date' => $scheduleDate,

            'start_time' => $startTime,

            'end_time' => $endTime,

            'encrypted_secret' => $this->totpService
                ->generateSecret(),

            'status' => 'active',

            'created_by' => null,

            'closed_at' => null,
        ]);
    }

    private function assertCompleteSnapshot(
        WeeklyScheduleItem $item
    ): void {
        $requiredValues = [
            $item->check_in_time_snapshot,
            $item->check_out_time_snapshot,
            $item
                ->check_in_open_minutes_snapshot,
            $item
                ->check_out_limit_minutes_snapshot,
        ];

        foreach ($requiredValues as $value) {
            if ($value === null || $value === '') {
                throw ValidationException::withMessages([
                    'weekly_schedule_item' => sprintf(
                        'Snapshot pola kerja item roster %d tidak lengkap.',
                        $item->getKey()
                    ),
                ]);
            }
        }
    }

    private function assertExistingSessionMatches(
        AttendanceSession $attendanceSession,
        WeeklySchedule $weeklySchedule,
        Branch $branch,
        string $scheduleDate,
        CarbonImmutable $startTime,
        CarbonImmutable $endTime
    ): void {
        $matches =
            (int) $attendanceSession
                ->weekly_schedule_id
                === (int) $weeklySchedule
                    ->getKey()

            && (int) $attendanceSession
                ->branch_id
                === (int) $branch
                    ->getKey()

            && $attendanceSession->isAutoType()

            && $attendanceSession->isAutomatic()

            && $attendanceSession
                ->session_date
                ->format('Y-m-d')
                === $scheduleDate

            && $attendanceSession
                ->start_time
                ->format('Y-m-d H:i:s')
                === $startTime
                    ->format('Y-m-d H:i:s')

            && $attendanceSession
                ->end_time
                ->format('Y-m-d H:i:s')
                === $endTime
                    ->format('Y-m-d H:i:s');

        if (! $matches) {
            throw new LogicException(
                'Automation key sesi presensi terhubung dengan data yang tidak konsisten.'
            );
        }
    }

    private function dateTimeFromSnapshot(
        string $scheduleDate,
        mixed $snapshotTime
    ): CarbonImmutable {
        return CarbonImmutable::parse(
            sprintf(
                '%s %s',
                $scheduleDate,
                $this->timeString(
                    $snapshotTime
                )
            ),
            $this->timezone()
        );
    }

    private function timeString(
        mixed $value
    ): string {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i:s');
        }

        return substr(
            (string) $value,
            0,
            8
        );
    }

    private function timezone(): string
    {
        return (string) config(
            'app.timezone',
            'Asia/Jakarta'
        );
    }
}
