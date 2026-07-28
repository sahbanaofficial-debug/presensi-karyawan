<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AutomaticAttendanceSessionLifecycleService;
use Illuminate\Console\Command;

final class ExpireAutomaticAttendanceSessions extends Command
{
    /**
     * @var string
     */
    protected $signature =
        'attendance-sessions:expire-automatic';

    /**
     * @var string
     */
    protected $description =
        'Menandai sesi presensi otomatis yang telah melewati end_time sebagai expired.';

    public function handle(
        AutomaticAttendanceSessionLifecycleService $lifecycleService
    ): int {
        $expiredCount =
            $lifecycleService->expireElapsed();

        $this->info(
            sprintf(
                'Sesi otomatis kedaluwarsa: %d.',
                $expiredCount
            )
        );

        return self::SUCCESS;
    }
}
