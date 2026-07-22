<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationLog extends Model
{
    use HasFactory;

    /**
     * Log tidak memiliki kolom updated_at.
     *
     * Nilai created_at diisi oleh default database.
     */
    public $timestamps = false;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'attendance_session_id',
        'validation_type',
        'status',
        'reason',
        'payload_reference',
        'latitude',
        'longitude',
        'accuracy',
        'distance',
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
            'accuracy' => 'float',
            'distance' => 'float',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Pengguna yang melakukan permintaan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sesi presensi yang digunakan dalam permintaan.
     */
    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    /**
     * Memeriksa apakah hasil validasi diterima.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Memeriksa apakah hasil validasi ditolak.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Memeriksa apakah koordinat perangkat tersedia.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null;
    }

    /**
     * Memeriksa apakah hasil perhitungan jarak tersedia.
     */
    public function hasDistanceResult(): bool
    {
        return $this->distance !== null;
    }
}
