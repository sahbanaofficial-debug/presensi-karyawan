<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    public const RECORD_SOURCE_SCANNER = 'scanner';

    public const RECORD_SOURCE_MANUAL = 'manual';

    use HasFactory;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'attendance_session_id',
        'branch_terminal_id',
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
        'late_minutes',
        'validation_status',

        'record_source',
        'last_corrected_by',
        'last_correction_reason',
        'last_corrected_at',
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
            'branch_terminal_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'float',
            'distance' => 'float',
            'geofence_radius' => 'float',
            'late_minutes' => 'integer',

            'record_source' => 'string',
            'last_corrected_by' => 'integer',
            'last_corrected_at' => 'datetime',
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
     * Terminal cabang yang menampilkan QR presensi.
     */
    public function branchTerminal(): BelongsTo
    {
        return $this->belongsTo(
            BranchTerminal::class
        );
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
     * Memeriksa apakah presensi berasal dari terminal terdaftar.
     */
    public function wasRecordedThroughTerminal(): bool
    {
        return $this->branch_terminal_id !== null;
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

    /**
     * Seluruh riwayat koreksi pada presensi ini.
     *
     * @return HasMany<AttendanceCorrection, $this>
     */
    public function corrections(): HasMany
    {
        return $this->hasMany(
            AttendanceCorrection::class,
            'attendance_id'
        )->latest('created_at');
    }

    /**
     * HRD terakhir yang melakukan koreksi.
     *
     * @return BelongsTo<User, $this>
     */
    public function lastCorrectedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'last_corrected_by'
        );
    }
}
