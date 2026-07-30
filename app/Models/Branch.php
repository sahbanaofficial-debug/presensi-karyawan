<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'default_work_schedule_id',
        'code',
        'name',
        'address',
        'latitude',
        'longitude',
        'geofence_radius',
        'maximum_accuracy',
        'status',
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_work_schedule_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'geofence_radius' => 'float',
            'maximum_accuracy' => 'float',
        ];
    }

    /**
     * Pola jadwal kerja default cabang.
     */
    public function defaultWorkSchedule(): BelongsTo
    {
        return $this->belongsTo(
            WorkSchedule::class,
            'default_work_schedule_id'
        );
    }

    /**
     * Memeriksa apakah cabang telah memiliki jadwal default.
     */
    public function hasDefaultWorkSchedule(): bool
    {
        return $this->default_work_schedule_id !== null;
    }

    /**
     * Akun admin operasional yang ditugaskan pada cabang.
     */
    public function administrators(): HasMany
    {
        return $this->hasMany(User::class)
            ->where('role', 'admin');
    }

    /**
     * Karyawan yang ditempatkan pada cabang.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Sesi presensi yang dibuat untuk cabang.
     */
    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    /**
     * Roster mingguan milik cabang.
     */
    public function weeklySchedules(): HasMany
    {
        return $this->hasMany(WeeklySchedule::class);
    }

    /**
     * Terminal QR yang terdaftar pada cabang.
     */
    public function branchTerminals(): HasMany
    {
        return $this->hasMany(BranchTerminal::class);
    }

    /**
     * Transaksi presensi yang terjadi pada cabang.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Memeriksa apakah cabang masih aktif.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Memeriksa apakah parameter geofence sudah lengkap.
     */
    public function hasGeofenceConfiguration(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null
            && $this->geofence_radius > 0
            && $this->maximum_accuracy > 0;
    }
}
