<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeSchedule extends Model
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_WEEKLY = 'weekly';

    public const SOURCE_BRANCH_DEFAULT = 'branch_default';

    use HasFactory;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'weekly_schedule_item_id',
        'employee_id',
        'work_schedule_id',
        'schedule_date',
        'schedule_status',
        'schedule_source',
        'work_schedule_name_snapshot',
        'check_in_time_snapshot',
        'check_out_time_snapshot',
        'check_in_open_minutes_snapshot',
        'check_in_limit_minutes_snapshot',
        'late_tolerance_minutes_snapshot',
        'check_out_limit_minutes_snapshot',
        'approved_by',
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
     * Item roster mingguan asal jadwal harian.
     */
    public function weeklyScheduleItem(): BelongsTo
    {
        return $this->belongsTo(
            WeeklyScheduleItem::class
        );
    }

    /**
     * Karyawan pemilik jadwal harian.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Pola jadwal kerja yang digunakan.
     */
    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    /**
     * Pengguna yang menetapkan atau menyetujui jadwal.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    /**
     * Transaksi presensi pada jadwal harian ini.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Memeriksa apakah karyawan dijadwalkan bekerja.
     */
    public function isWorkDay(): bool
    {
        return $this->schedule_status === 'work';
    }

    /**
     * Memeriksa apakah karyawan sedang libur.
     */
    public function isOff(): bool
    {
        return $this->schedule_status === 'off';
    }

    /**
     * Memeriksa apakah karyawan sedang cuti.
     */
    public function isLeave(): bool
    {
        return $this->schedule_status === 'leave';
    }

    /**
     * Memeriksa apakah karyawan berstatus izin.
     */
    public function isPermit(): bool
    {
        return $this->schedule_status === 'permit';
    }

    /**
     * Memeriksa apakah karyawan berstatus sakit.
     */
    public function isSick(): bool
    {
        return $this->schedule_status === 'sick';
    }

    /**
     * Memeriksa apakah jadwal berasal dari roster mingguan.
     */
    public function isFromWeeklySchedule(): bool
    {
        return $this->schedule_source === self::SOURCE_WEEKLY;
    }

    /**
     * Memeriksa apakah jadwal dibentuk dari
     * pola kerja default cabang.
     */
    public function isFromBranchDefaultSchedule(): bool
    {
        return $this->schedule_source
            === self::SOURCE_BRANCH_DEFAULT;
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
     * Memeriksa bahwa jadwal nonkerja tidak menyimpan snapshot kerja.
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
     * Menentukan apakah karyawan wajib melakukan presensi.
     */
    public function requiresAttendance(): bool
    {
        return $this->isWorkDay();
    }

    /**
     * Memeriksa konsistensi status kerja dengan pola jadwal.
     */
    public function hasValidScheduleConfiguration(): bool
    {
        if ($this->isWorkDay()) {
            if ($this->work_schedule_id === null) {
                return false;
            }

            if ($this->isFromWeeklySchedule()) {
                return $this->weekly_schedule_item_id !== null
                    && $this->hasCompleteWorkSnapshot();
            }

            if ($this->isFromBranchDefaultSchedule()) {
                return $this->weekly_schedule_item_id === null
                    && $this->hasCompleteWorkSnapshot();
            }

            return true;
        }

        if ($this->work_schedule_id !== null) {
            return false;
        }

        if ($this->isFromWeeklySchedule()) {
            return $this->weekly_schedule_item_id !== null
                && $this->hasEmptyWorkSnapshot();
        }

        if ($this->isFromBranchDefaultSchedule()) {
            return false;
        }

        return true;
    }
}
