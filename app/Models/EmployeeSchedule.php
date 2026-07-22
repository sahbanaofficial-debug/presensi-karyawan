<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeSchedule extends Model
{
    use HasFactory;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'work_schedule_id',
        'schedule_date',
        'schedule_status',
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
        ];
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
            return $this->work_schedule_id !== null;
        }

        return $this->work_schedule_id === null;
    }
}
