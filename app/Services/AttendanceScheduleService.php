<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Throwable;

final class AttendanceScheduleService
{
    private const CHECK_IN = 'check_in';

    private const CHECK_OUT = 'check_out';

    /**
     * Mencari jadwal harian karyawan berdasarkan tanggal.
     *
     * Gunakan lockForUpdate ketika dipanggil di dalam
     * transaksi pencatatan presensi.
     */
    public function findForDate(
        Employee $employee,
        DateTimeInterface|string $scheduleDate,
        bool $lockForUpdate = false
    ): ?EmployeeSchedule {
        $resolvedDate = $this->resolveDate(
            $scheduleDate
        );

        $query = EmployeeSchedule::query()
            ->where(
                'employee_id',
                $employee->getKey()
            )
            ->whereDate(
                'schedule_date',
                $resolvedDate->format('Y-m-d')
            )
            ->with('workSchedule');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * Menilai apakah presensi dapat dilakukan pada
     * waktu tertentu berdasarkan jadwal karyawan.
     *
     * @return array{
     *     allowed: bool,
     *     code: string,
     *     message: string,
     *     punctuality_status: string|null,
     *     schedule_date: CarbonImmutable|null,
     *     check_in_opens_at: CarbonImmutable|null,
     *     scheduled_check_in_at: CarbonImmutable|null,
     *     late_limit_at: CarbonImmutable|null,
     *     scheduled_check_out_at: CarbonImmutable|null,
     *     check_out_limit_at: CarbonImmutable|null
     * }
     */
    public function evaluate(
        EmployeeSchedule $employeeSchedule,
        string $attendanceType,
        DateTimeInterface|string|null $moment = null
    ): array {
        $attendanceType = strtolower(
            trim($attendanceType)
        );

        $this->validateAttendanceType(
            $attendanceType
        );

        $employeeSchedule->loadMissing(
            'workSchedule'
        );

        if (! $employeeSchedule->requiresAttendance()) {
            return $this->result(
                allowed: false,
                code: 'schedule_not_working',
                message: $this->nonWorkingDayMessage(
                    $employeeSchedule
                )
            );
        }

        if (
            ! $employeeSchedule
                ->hasValidScheduleConfiguration()
            || $employeeSchedule->workSchedule === null
        ) {
            return $this->result(
                allowed: false,
                code: 'schedule_configuration_invalid',
                message: 'Jadwal kerja karyawan tidak memiliki pola waktu yang valid.'
            );
        }

        try {
            $timeWindow = $this->timeWindow(
                $employeeSchedule
            );
        } catch (InvalidArgumentException) {
            return $this->result(
                allowed: false,
                code: 'schedule_configuration_invalid',
                message: 'Konfigurasi waktu pada jadwal kerja tidak valid.'
            );
        }

        $currentMoment = $this->resolveMoment(
            $moment
        );

        if (
            ! $currentMoment->isSameDay(
                $timeWindow['schedule_date']
            )
        ) {
            return $this->result(
                allowed: false,
                code: 'outside_schedule_date',
                message: 'Presensi tidak dilakukan pada tanggal jadwal karyawan.',
                timeWindow: $timeWindow
            );
        }

        if ($attendanceType === self::CHECK_IN) {
            return $this->evaluateCheckIn(
                $currentMoment,
                $timeWindow
            );
        }

        return $this->evaluateCheckOut(
            $currentMoment,
            $timeWindow
        );
    }

    /**
     * Menghasilkan seluruh batas waktu jadwal.
     *
     * @return array{
     *     schedule_date: CarbonImmutable,
     *     check_in_opens_at: CarbonImmutable,
     *     scheduled_check_in_at: CarbonImmutable,
     *     late_limit_at: CarbonImmutable,
     *     scheduled_check_out_at: CarbonImmutable,
     *     check_out_limit_at: CarbonImmutable
     * }
     */
    public function timeWindow(
        EmployeeSchedule $employeeSchedule
    ): array {
        $employeeSchedule->loadMissing(
            'workSchedule'
        );

        $workSchedule =
            $employeeSchedule->workSchedule;

        if ($workSchedule === null) {
            throw new InvalidArgumentException(
                'Jadwal harian tidak memiliki pola jadwal kerja.'
            );
        }

        $scheduledCheckInAt =
            $this->combineScheduleDateAndTime(
                $employeeSchedule,
                $workSchedule->check_in_time
            );

        $scheduledCheckOutAt =
            $this->combineScheduleDateAndTime(
                $employeeSchedule,
                $workSchedule->check_out_time
            );

        if (
            $scheduledCheckOutAt
                ->lessThanOrEqualTo(
                    $scheduledCheckInAt
                )
        ) {
            throw new InvalidArgumentException(
                'Waktu pulang harus setelah waktu masuk.'
            );
        }

        $checkInOpenMinutes =
            $this->nonNegativeMinutes(
                $workSchedule
                    ->check_in_open_minutes,
                'check_in_open_minutes'
            );

        $lateToleranceMinutes =
            $this->nonNegativeMinutes(
                $workSchedule
                    ->late_tolerance_minutes,
                'late_tolerance_minutes'
            );

        $checkOutLimitMinutes =
            $this->nonNegativeMinutes(
                $workSchedule
                    ->check_out_limit_minutes,
                'check_out_limit_minutes'
            );

        return [
            'schedule_date' => $this->resolveDate(
                $employeeSchedule
                    ->schedule_date
            ),

            'check_in_opens_at' => $scheduledCheckInAt
                ->subMinutes(
                    $checkInOpenMinutes
                ),

            'scheduled_check_in_at' => $scheduledCheckInAt,

            'late_limit_at' => $scheduledCheckInAt
                ->addMinutes(
                    $lateToleranceMinutes
                ),

            'scheduled_check_out_at' => $scheduledCheckOutAt,

            'check_out_limit_at' => $scheduledCheckOutAt
                ->addMinutes(
                    $checkOutLimitMinutes
                ),
        ];
    }

    /**
     * Menilai waktu presensi masuk.
     *
     * Karyawan yang melewati batas toleransi tetap
     * diterima dengan status terlambat.
     *
     * @param array{
     *     schedule_date: CarbonImmutable,
     *     check_in_opens_at: CarbonImmutable,
     *     scheduled_check_in_at: CarbonImmutable,
     *     late_limit_at: CarbonImmutable,
     *     scheduled_check_out_at: CarbonImmutable,
     *     check_out_limit_at: CarbonImmutable
     * } $timeWindow
     * @return array<string, mixed>
     */
    private function evaluateCheckIn(
        CarbonImmutable $currentMoment,
        array $timeWindow
    ): array {
        if (
            $currentMoment->lessThan(
                $timeWindow[
                    'check_in_opens_at'
                ]
            )
        ) {
            return $this->result(
                allowed: false,
                code: 'check_in_not_open',
                message: sprintf(
                    'Presensi masuk belum dibuka. Presensi dapat dilakukan mulai pukul %s WIB.',
                    $timeWindow[
                        'check_in_opens_at'
                    ]->format('H:i')
                ),
                timeWindow: $timeWindow
            );
        }

        $punctualityStatus =
            $currentMoment->greaterThan(
                $timeWindow['late_limit_at']
            )
                ? 'late'
                : 'on_time';

        $message =
            $punctualityStatus === 'late'
                ? 'Presensi masuk diterima dengan status terlambat.'
                : 'Presensi masuk diterima dengan status tepat waktu.';

        return $this->result(
            allowed: true,
            code: 'accepted',
            message: $message,
            punctualityStatus: $punctualityStatus,
            timeWindow: $timeWindow
        );
    }

    /**
     * Menilai waktu presensi pulang.
     *
     * @param array{
     *     schedule_date: CarbonImmutable,
     *     check_in_opens_at: CarbonImmutable,
     *     scheduled_check_in_at: CarbonImmutable,
     *     late_limit_at: CarbonImmutable,
     *     scheduled_check_out_at: CarbonImmutable,
     *     check_out_limit_at: CarbonImmutable
     * } $timeWindow
     * @return array<string, mixed>
     */
    private function evaluateCheckOut(
        CarbonImmutable $currentMoment,
        array $timeWindow
    ): array {
        if (
            $currentMoment->lessThan(
                $timeWindow[
                    'scheduled_check_out_at'
                ]
            )
        ) {
            return $this->result(
                allowed: false,
                code: 'check_out_too_early',
                message: sprintf(
                    'Presensi pulang belum diperbolehkan. Jadwal pulang adalah pukul %s WIB.',
                    $timeWindow[
                        'scheduled_check_out_at'
                    ]->format('H:i')
                ),
                timeWindow: $timeWindow
            );
        }

        if (
            $currentMoment->greaterThan(
                $timeWindow[
                    'check_out_limit_at'
                ]
            )
        ) {
            return $this->result(
                allowed: false,
                code: 'check_out_limit_passed',
                message: sprintf(
                    'Batas akhir presensi pulang telah lewat pada pukul %s WIB.',
                    $timeWindow[
                        'check_out_limit_at'
                    ]->format('H:i')
                ),
                timeWindow: $timeWindow
            );
        }

        return $this->result(
            allowed: true,
            code: 'accepted',
            message: 'Presensi pulang diterima.',
            punctualityStatus: 'not_applicable',
            timeWindow: $timeWindow
        );
    }

    /**
     * Membentuk hasil evaluasi dengan struktur konsisten.
     *
     * @param array{
     *     schedule_date: CarbonImmutable,
     *     check_in_opens_at: CarbonImmutable,
     *     scheduled_check_in_at: CarbonImmutable,
     *     late_limit_at: CarbonImmutable,
     *     scheduled_check_out_at: CarbonImmutable,
     *     check_out_limit_at: CarbonImmutable
     * }|null $timeWindow
     * @return array{
     *     allowed: bool,
     *     code: string,
     *     message: string,
     *     punctuality_status: string|null,
     *     schedule_date: CarbonImmutable|null,
     *     check_in_opens_at: CarbonImmutable|null,
     *     scheduled_check_in_at: CarbonImmutable|null,
     *     late_limit_at: CarbonImmutable|null,
     *     scheduled_check_out_at: CarbonImmutable|null,
     *     check_out_limit_at: CarbonImmutable|null
     * }
     */
    private function result(
        bool $allowed,
        string $code,
        string $message,
        ?string $punctualityStatus = null,
        ?array $timeWindow = null
    ): array {
        return [
            'allowed' => $allowed,

            'code' => $code,

            'message' => $message,

            'punctuality_status' => $punctualityStatus,

            'schedule_date' => $timeWindow[
                    'schedule_date'
                ] ?? null,

            'check_in_opens_at' => $timeWindow[
                    'check_in_opens_at'
                ] ?? null,

            'scheduled_check_in_at' => $timeWindow[
                    'scheduled_check_in_at'
                ] ?? null,

            'late_limit_at' => $timeWindow[
                    'late_limit_at'
                ] ?? null,

            'scheduled_check_out_at' => $timeWindow[
                    'scheduled_check_out_at'
                ] ?? null,

            'check_out_limit_at' => $timeWindow[
                    'check_out_limit_at'
                ] ?? null,
        ];
    }

    /**
     * Pesan untuk jadwal nonkerja.
     */
    private function nonWorkingDayMessage(
        EmployeeSchedule $employeeSchedule
    ): string {
        return match (
            $employeeSchedule->schedule_status
        ) {
            'off' => 'Karyawan berstatus libur pada tanggal sesi.',

            'permit' => 'Karyawan berstatus izin pada tanggal sesi.',

            'sick' => 'Karyawan berstatus sakit pada tanggal sesi.',

            default => 'Karyawan tidak memiliki jadwal kerja pada tanggal sesi.',
        };
    }

    /**
     * Menggabungkan tanggal jadwal dengan jam
     * pada pola jadwal kerja.
     */
    private function combineScheduleDateAndTime(
        EmployeeSchedule $employeeSchedule,
        mixed $time
    ): CarbonImmutable {
        $scheduleDate = $this->resolveDate(
            $employeeSchedule->schedule_date
        );

        $normalizedTime =
            $this->normalizeTime($time);

        try {
            $dateTime =
                CarbonImmutable::createFromFormat(
                    '!Y-m-d H:i:s',
                    sprintf(
                        '%s %s',
                        $scheduleDate
                            ->format('Y-m-d'),
                        $normalizedTime
                    ),
                    $this->timezone()
                );
        } catch (Throwable) {
            $dateTime = false;
        }

        if ($dateTime === false) {
            throw new InvalidArgumentException(
                'Waktu jadwal tidak dapat diproses.'
            );
        }

        return $dateTime;
    }

    /**
     * Menormalisasi nilai TIME dari basis data.
     */
    private function normalizeTime(
        mixed $time
    ): string {
        if ($time instanceof DateTimeInterface) {
            return $time->format('H:i:s');
        }

        if (! is_string($time)) {
            throw new InvalidArgumentException(
                'Nilai waktu jadwal tidak valid.'
            );
        }

        $time = trim($time);

        if (
            preg_match(
                '/^\d{2}:\d{2}$/',
                $time
            ) === 1
        ) {
            $time .= ':00';
        }

        if (
            preg_match(
                '/^\d{2}:\d{2}:\d{2}$/',
                $time
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Format waktu jadwal tidak valid.'
            );
        }

        try {
            $parsedTime =
                CarbonImmutable::createFromFormat(
                    '!H:i:s',
                    $time,
                    $this->timezone()
                );
        } catch (Throwable) {
            $parsedTime = false;
        }

        if (
            $parsedTime === false
            || $parsedTime->format('H:i:s')
                !== $time
        ) {
            throw new InvalidArgumentException(
                'Nilai waktu jadwal tidak valid.'
            );
        }

        return $time;
    }

    /**
     * Nilai menit harus berupa bilangan bulat
     * nol atau lebih besar.
     */
    private function nonNegativeMinutes(
        mixed $value,
        string $field
    ): int {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s harus berupa angka.',
                    $field
                )
            );
        }

        $minutes = (int) $value;

        if (
            $minutes < 0
            || (float) $value
                !== (float) $minutes
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s harus berupa bilangan bulat nonnegatif.',
                    $field
                )
            );
        }

        return $minutes;
    }

    /**
     * Jenis presensi harus berasal dari sesi
     * yang valid.
     */
    private function validateAttendanceType(
        string $attendanceType
    ): void {
        if (
            ! in_array(
                $attendanceType,
                [
                    self::CHECK_IN,
                    self::CHECK_OUT,
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Jenis presensi tidak didukung.'
            );
        }
    }

    /**
     * Mengubah nilai tanggal menjadi awal hari.
     */
    private function resolveDate(
        DateTimeInterface|string $date
    ): CarbonImmutable {
        if ($date instanceof DateTimeInterface) {
            return CarbonImmutable::instance(
                $date
            )
                ->setTimezone(
                    $this->timezone()
                )
                ->startOfDay();
        }

        return CarbonImmutable::parse(
            $date,
            $this->timezone()
        )->startOfDay();
    }

    /**
     * Mengubah waktu evaluasi menjadi waktu lokal.
     */
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

    /**
     * Zona waktu operasional sistem.
     */
    private function timezone(): string
    {
        return (string) config(
            'app.timezone',
            'Asia/Jakarta'
        );
    }
}
