<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectionLog extends Model
{
    use HasFactory;

    /**
     * Tabel correction_logs hanya memiliki created_at.
     * Nilai created_at diisi otomatis oleh database.
     */
    public $timestamps = false;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'attendance_id',
        'changed_by',
        'approved_by',
        'before_data',
        'after_data',
        'reason',
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Data presensi yang dikoreksi.
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Pengguna yang melakukan koreksi.
     */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'changed_by'
        );
    }

    /**
     * HRD yang menyetujui koreksi.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    /**
     * Memeriksa apakah koreksi telah memiliki persetujuan.
     */
    public function hasApproval(): bool
    {
        return $this->approved_by !== null;
    }

    /**
     * Memeriksa apakah data sebelum dan sesudah berbeda.
     */
    public function hasDataChanges(): bool
    {
        return $this->before_data !== $this->after_data;
    }

    /**
     * Memeriksa apakah alasan koreksi tersedia.
     */
    public function hasReason(): bool
    {
        return trim((string) $this->reason) !== '';
    }
}
