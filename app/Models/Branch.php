<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
            'latitude' => 'float',
            'longitude' => 'float',
            'geofence_radius' => 'float',
            'maximum_accuracy' => 'float',
        ];
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
