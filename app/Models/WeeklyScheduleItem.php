<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WeeklyScheduleItem extends Model
{
    public const STATUS_WORK = 'work';

    public const STATUS_OFF = 'off';

    public const STATUS_LEAVE = 'leave';

    public const STATUS_PERMIT = 'permit';

    public const STATUS_SICK = 'sick';

    use HasFactory;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'weekly_schedule_id',
        'employee_id',
        'work_schedule_id',
        'schedule_date',
        'schedule_status',
        'work_schedule_name_snapshot',
        'check_in_time_snapshot',
        'check_out_time_snapshot',
        'check_in_open_minutes_snapshot',
        'check_in_limit_minutes_snapshot',
        'late_tolerance_minutes_snapshot',
        'check_out_limit_minutes_snapshot',
        'notes',
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schedule_date' => 'date',
            'check_in_open_minutes_snapshot' => 'integer',
            'check_in_limit_minutes_snapshot' => 'integer',
            'late_tolerance_minutes_snapshot' => 'integer',
            'check_out_limit_minutes_snapshot' => 'integer',
        ];
    }

    /**
     * Roster mingguan pemilik item.
     */
    public function weeklySchedule(): BelongsTo
    {
        return $this->belongsTo(
            WeeklySchedule::class
        );
    }

    /**
     * Karyawan pemilik jadwal.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Master pola kerja yang dipilih saat draft dibuat.
     */
    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(
            WorkSchedule::class
        );
    }

    /**
     * Jadwal harian hasil publikasi item roster.
     */
    public function employeeSchedule(): HasOne
    {
        return $this->hasOne(
            EmployeeSchedule::class
        );
    }

    public function isWorkDay(): bool
    {
        return $this->schedule_status === self::STATUS_WORK;
    }

    public function isOff(): bool
    {
        return $this->schedule_status === self::STATUS_OFF;
    }

    public function isLeave(): bool
    {
        return $this->schedule_status === self::STATUS_LEAVE;
    }

    public function isPermit(): bool
    {
        return $this->schedule_status === self::STATUS_PERMIT;
    }

    public function isSick(): bool
    {
        return $this->schedule_status === self::STATUS_SICK;
    }

    /**
     * Hanya jadwal kerja yang membutuhkan presensi.
     */
    public function requiresAttendance(): bool
    {
        return $this->isWorkDay();
    }

    /**
     * Memeriksa kelengkapan snapshot pola kerja.
     */
    public function hasCompleteWorkSnapshot(): bool
    {
        return $this->work_schedule_name_snapshot !== null
            && $this->check_in_time_snapshot !== null
            && $this->check_out_time_snapshot !== null
            && $this->check_in_open_minutes_snapshot !== null
            && $this->check_in_limit_minutes_snapshot !== null
            && $this->late_tolerance_minutes_snapshot !== null
            && $this->check_out_limit_minutes_snapshot !== null;
    }

    /**
     * Memeriksa bahwa status nonkerja tidak menyimpan snapshot kerja.
     */
    public function hasEmptyWorkSnapshot(): bool
    {
        return $this->work_schedule_name_snapshot === null
            && $this->check_in_time_snapshot === null
            && $this->check_out_time_snapshot === null
            && $this->check_in_open_minutes_snapshot === null
            && $this->check_in_limit_minutes_snapshot === null
            && $this->late_tolerance_minutes_snapshot === null
            && $this->check_out_limit_minutes_snapshot === null;
    }

    /**
     * Memeriksa konsistensi status, pola kerja, dan snapshot.
     */
    public function hasValidScheduleConfiguration(): bool
    {
        if ($this->isWorkDay()) {
            return $this->work_schedule_id !== null
                && $this->hasCompleteWorkSnapshot();
        }

        return $this->work_schedule_id === null
            && $this->hasEmptyWorkSnapshot();
    }
}
