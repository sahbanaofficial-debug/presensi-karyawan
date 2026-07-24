<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AttendanceCorrection extends Model
{
    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    /**
     * Tabel audit hanya memiliki created_at.
     */
    public const UPDATED_AT = null;

    /**
     * Nama tabel model.
     *
     * @var string
     */
    protected $table = 'attendance_corrections';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_id',
        'corrected_by',
        'action',
        'reason',
        'before_data',
        'after_data',
    ];

    /**
     * Presensi yang dikoreksi.
     *
     * @return BelongsTo<Attendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(
            Attendance::class,
            'attendance_id'
        );
    }

    /**
     * HRD yang melakukan koreksi.
     *
     * @return BelongsTo<User, $this>
     */
    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'corrected_by'
        );
    }

    /**
     * Casting atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_id' => 'integer',
            'corrected_by' => 'integer',
            'before_data' => 'array',
            'after_data' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
