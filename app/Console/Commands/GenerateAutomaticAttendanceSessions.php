<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WeeklySchedule;
use App\Services\WeeklyAttendanceSessionAutomationService;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

final class GenerateAutomaticAttendanceSessions extends Command
{
    /**
     * @var string
     */
    protected $signature =
        'attendance-sessions:generate-automatic
        {--date= : Tanggal acuan roster dalam format YYYY-MM-DD}';

    /**
     * @var string
     */
    protected $description =
        'Membentuk sesi presensi otomatis dari roster mingguan terpublikasi.';

    public function handle(
        WeeklyAttendanceSessionAutomationService $automationService
    ): int {
        $targetDate = $this->resolveTargetDate();

        if ($targetDate === null) {
            return self::FAILURE;
        }

        $dateString = $targetDate->format(
            'Y-m-d'
        );

        $weeklySchedules = WeeklySchedule::query()
            ->where(
                'status',
                'published'
            )
            ->whereDate(
                'week_start_date',
                '<=',
                $dateString
            )
            ->whereDate(
                'week_end_date',
                '>=',
                $dateString
            )
            ->whereHas(
                'items',
                static function (
                    Builder $query
                ) use (
                    $dateString
                ): void {
                    $query
                        ->whereDate(
                            'schedule_date',
                            $dateString
                        )
                        ->where(
                            'schedule_status',
                            'work'
                        );
                }
            )
            ->orderBy('branch_id')
            ->orderBy('id')
            ->get();

        if ($weeklySchedules->isEmpty()) {
            $this->info(
                sprintf(
                    'Tidak ada roster terpublikasi dengan jadwal kerja pada %s.',
                    $dateString
                )
            );

            return self::SUCCESS;
        }

        $processedRosters = 0;
        $createdSessions = 0;
        $reusedSessions = 0;
        $failedRosters = 0;

        foreach ($weeklySchedules as $weeklySchedule) {
            try {
                $sessions = $automationService
                    ->createForPublishedRoster(
                        $weeklySchedule
                    );

                $processedRosters++;

                foreach ($sessions as $session) {
                    if ($session->wasRecentlyCreated) {
                        $createdSessions++;

                        continue;
                    }

                    $reusedSessions++;
                }
            } catch (Throwable $exception) {
                $failedRosters++;

                report($exception);

                $this->error(
                    sprintf(
                        'Roster #%d cabang #%d gagal: %s',
                        $weeklySchedule->getKey(),
                        $weeklySchedule->branch_id,
                        $exception->getMessage()
                    )
                );
            }
        }

        $this->table(
            [
                'Tanggal acuan',
                'Roster diproses',
                'Sesi dibuat',
                'Sesi digunakan ulang',
                'Roster gagal',
            ],
            [
                [
                    $dateString,
                    $processedRosters,
                    $createdSessions,
                    $reusedSessions,
                    $failedRosters,
                ],
            ]
        );

        if ($failedRosters > 0) {
            return self::FAILURE;
        }

        $this->info(
            'Pembentukan sesi otomatis selesai.'
        );

        return self::SUCCESS;
    }

    private function resolveTargetDate(): ?CarbonImmutable
    {
        $timezone = new DateTimeZone(
            (string) config(
                'app.timezone',
                'Asia/Jakarta'
            )
        );

        $dateOption = $this->option(
            'date'
        );

        if (
            $dateOption === null
            || trim((string) $dateOption) === ''
        ) {
            return CarbonImmutable::now(
                $timezone
            )->startOfDay();
        }

        $dateString = trim(
            (string) $dateOption
        );

        $parsedDate = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $dateString,
            $timezone
        );

        $dateErrors =
            DateTimeImmutable::getLastErrors();

        $hasDateErrors =
            is_array($dateErrors)
            && (
                $dateErrors['warning_count'] > 0
                || $dateErrors['error_count'] > 0
            );

        if (
            $parsedDate === false
            || $hasDateErrors
            || $parsedDate->format('Y-m-d')
                !== $dateString
        ) {
            $this->error(
                'Opsi --date harus berupa tanggal valid dengan format YYYY-MM-DD.'
            );

            return null;
        }

        return CarbonImmutable::instance(
            $parsedDate
        );
    }
}
