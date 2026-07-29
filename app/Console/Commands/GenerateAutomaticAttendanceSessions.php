<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BranchDefaultEmployeeScheduleGeneratorService;
use App\Services\DailyAttendanceSessionAutomationService;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command;

final class GenerateAutomaticAttendanceSessions extends Command
{
    /**
     * @var string
     */
    protected $signature =
        'attendance-sessions:generate-automatic
        {--date= : Tanggal jadwal harian dalam format YYYY-MM-DD}';

    /**
     * @var string
     */
    protected $description =
        'Membentuk sesi presensi otomatis dari jadwal harian karyawan.';

    public function handle(
        BranchDefaultEmployeeScheduleGeneratorService $scheduleGenerator,
        DailyAttendanceSessionAutomationService $sessionGenerator
    ): int {
        $targetDate = $this->resolveTargetDate();

        if ($targetDate === null) {
            return self::FAILURE;
        }

        $dateString = $targetDate->format(
            'Y-m-d'
        );

        $scheduleSummary = $scheduleGenerator->generate(
            $targetDate
        );

        $sessionSummary = $sessionGenerator->generate(
            $targetDate
        );

        $this->table(
            [
                'Tanggal',
                'Jadwal dibuat',
                'Jadwal tersedia',
                'Cabang sesi',
                'Sesi dibuat',
                'Sesi digunakan ulang',
                'Cabang gagal',
            ],
            [
                [
                    $dateString,
                    $scheduleSummary['created'],
                    $scheduleSummary['existing'],
                    $sessionSummary['branches'],
                    $sessionSummary['created'],
                    $sessionSummary['reused'],
                    $sessionSummary['failed'],
                ],
            ]
        );

        foreach ($sessionSummary['failures'] as $failure) {
            $this->error($failure);
        }

        if ($sessionSummary['failed'] > 0) {
            return self::FAILURE;
        }

        if ($sessionSummary['branches'] === 0) {
            $this->info(
                sprintf(
                    'Tidak ada jadwal harian kerja pada %s.',
                    $dateString
                )
            );

            return self::SUCCESS;
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
