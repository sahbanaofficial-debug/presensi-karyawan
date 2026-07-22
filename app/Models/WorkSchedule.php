<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'check_in_time',
        'check_out_time',
        'check_in_open_minutes',
        'late_tolerance_minutes',
        'check_out_limit_minutes',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in_open_minutes' => 'integer',
            'late_tolerance_minutes' => 'integer',
            'check_out_limit_minutes' => 'integer',
        ];
    }

    /**
     * Jadwal harian karyawan yang menggunakan pola jadwal ini.
     */
    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    /**
     * Memeriksa apakah pola jadwal aktif.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Mendapatkan waktu pembukaan presensi masuk.
     */
    public function getCheckInOpenTime(
        DateTimeInterface|string $date
    ): CarbonImmutable {
        return $this->combineDateAndTime(
            $date,
            $this->check_in_time
        )->subMinutes($this->check_in_open_minutes);
    }

    /**
     * Mendapatkan batas akhir toleransi keterlambatan.
     */
    public function getLateLimit(
        DateTimeInterface|string $date
    ): CarbonImmutable {
        return $this->combineDateAndTime(
            $date,
            $this->check_in_time
        )->addMinutes($this->late_tolerance_minutes);
    }

    /**
     * Mendapatkan waktu pulang sesuai jadwal.
     */
    public function getCheckOutTime(
        DateTimeInterface|string $date
    ): CarbonImmutable {
        return $this->combineDateAndTime(
            $date,
            $this->check_out_time
        );
    }

    /**
     * Mendapatkan batas akhir presensi pulang.
     */
    public function getCheckOutLimit(
        DateTimeInterface|string $date
    ): CarbonImmutable {
        return $this->getCheckOutTime($date)
            ->addMinutes($this->check_out_limit_minutes);
    }

    /**
     * Menggabungkan tanggal dengan waktu jadwal.
     */
    private function combineDateAndTime(
        DateTimeInterface|string $date,
        string $time
    ): CarbonImmutable {
        $dateString = $date instanceof DateTimeInterface
            ? $date->format('Y-m-d')
            : CarbonImmutable::parse($date)->format('Y-m-d');

        return CarbonImmutable::parse(
            "{$dateString} {$time}",
            config('app.timezone')
        );
    }
}
