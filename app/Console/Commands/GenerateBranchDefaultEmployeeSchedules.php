<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BranchDefaultEmployeeScheduleGeneratorService;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command;

final class GenerateBranchDefaultEmployeeSchedules extends Command
{
    /**
     * @var string
     */
    protected $signature =
        'employee-schedules:generate-branch-default
        {--date= : Tanggal jadwal dalam format YYYY-MM-DD}';

    /**
     * @var string
     */
    protected $description =
        'Membentuk jadwal harian karyawan dari pola kerja default cabang.';

    public function handle(
        BranchDefaultEmployeeScheduleGeneratorService $generator
    ): int {
        $targetDate = $this->resolveTargetDate();

        if ($targetDate === null) {
            return self::FAILURE;
        }

        $dateString = $targetDate->format(
            'Y-m-d'
        );

        $summary = $generator->generate(
            $targetDate
        );

        $this->table(
            [
                'Tanggal',
                'Cabang diproses',
                'Karyawan aktif',
                'Jadwal dibuat',
                'Jadwal tersedia',
                'Cabang dilewati',
            ],
            [
                [
                    $dateString,
                    $summary['branches'],
                    $summary['employees'],
                    $summary['created'],
                    $summary['existing'],
                    $summary['skipped_branches'],
                ],
            ]
        );

        $this->info(
            'Pembentukan jadwal harian default cabang selesai.'
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
