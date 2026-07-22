<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'attendance_session_id',
        'employee_schedule_id',
        'branch_id',
        'attendance_type',
        'attendance_date',
        'attendance_time',
        'latitude',
        'longitude',
        'accuracy',
        'distance',
        'geofence_radius',
        'attendance_status',
        'punctuality_status',
        'validation_status',
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'attendance_time' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'float',
            'distance' => 'float',
            'geofence_radius' => 'float',
        ];
    }

    /**
     * Karyawan yang melakukan presensi.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Sesi QR Code yang digunakan untuk presensi.
     */
    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    /**
     * Jadwal harian yang menjadi dasar presensi.
     */
    public function employeeSchedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class);
    }

    /**
     * Cabang tempat presensi dilakukan.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Riwayat koreksi terhadap data presensi.
     */
    public function correctionLogs(): HasMany
    {
        return $this->hasMany(CorrectionLog::class);
    }

    /**
     * Memeriksa apakah transaksi merupakan presensi masuk.
     */
    public function isCheckIn(): bool
    {
        return $this->attendance_type === 'check_in';
    }

    /**
     * Memeriksa apakah transaksi merupakan presensi pulang.
     */
    public function isCheckOut(): bool
    {
        return $this->attendance_type === 'check_out';
    }

    /**
     * Memeriksa apakah presensi masuk tepat waktu.
     */
    public function isOnTime(): bool
    {
        return $this->punctuality_status === 'on_time';
    }

    /**
     * Memeriksa apakah presensi masuk terlambat.
     */
    public function isLate(): bool
    {
        return $this->punctuality_status === 'late';
    }

    /**
     * Memeriksa apakah penilaian ketepatan waktu tidak berlaku.
     */
    public function isPunctualityNotApplicable(): bool
    {
        return $this->punctuality_status === 'not_applicable';
    }

    /**
     * Memeriksa apakah transaksi presensi telah diterima.
     */
    public function isAccepted(): bool
    {
        return $this->validation_status === 'accepted';
    }

    /**
     * Memeriksa konsistensi jenis presensi dan status ketepatan waktu.
     */
    public function hasConsistentPunctualityStatus(): bool
    {
        if ($this->isCheckIn()) {
            return $this->isOnTime() || $this->isLate();
        }

        if ($this->isCheckOut()) {
            return $this->isPunctualityNotApplicable();
        }

        return false;
    }

    /**
     * Memeriksa apakah lokasi berada dalam radius geofence tersimpan.
     */
    public function isInsideStoredGeofence(): bool
    {
        return $this->distance <= $this->geofence_radius;
    }

    /**
     * Memeriksa apakah data koordinat berada dalam rentang valid.
     */
    public function hasValidCoordinateRange(): bool
    {
        return $this->latitude >= -90
            && $this->latitude <= 90
            && $this->longitude >= -180
            && $this->longitude <= 180;
    }
}
