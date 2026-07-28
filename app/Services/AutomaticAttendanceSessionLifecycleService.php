<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AttendanceSession;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

final class AutomaticAttendanceSessionLifecycleService
{
    /**
     * Menandai sesi otomatis aktif yang telah melewati
     * end_time sebagai expired.
     *
     * Tepat pada end_time sesi masih dianggap aktif agar
     * konsisten dengan validasi transaksi presensi.
     */
    public function expireElapsed(
        DateTimeInterface|string|null $moment = null
    ): int {
        $currentMoment = $this->resolveMoment(
            $moment
        );

        return DB::transaction(
            function () use (
                $currentMoment
            ): int {
                $sessions = AttendanceSession::query()
                    ->where(
                        'attendance_type',
                        AttendanceSession::TYPE_AUTO
                    )
                    ->where(
                        'session_source',
                        AttendanceSession::SOURCE_AUTOMATIC
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->where(
                        'end_time',
                        '<',
                        $currentMoment
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($sessions as $session) {
                    $session->update([
                        'status' => 'expired',
                    ]);
                }

                return $sessions->count();
            },
            3
        );
    }

    private function resolveMoment(
        DateTimeInterface|string|null $moment
    ): CarbonImmutable {
        if ($moment instanceof DateTimeInterface) {
            return CarbonImmutable::instance(
                $moment
            )->setTimezone(
                $this->timezone()
            );
        }

        if (is_string($moment)) {
            return CarbonImmutable::parse(
                $moment,
                $this->timezone()
            );
        }

        return CarbonImmutable::now(
            $this->timezone()
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
