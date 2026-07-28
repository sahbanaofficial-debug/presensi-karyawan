<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklySchedule extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    use HasFactory;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'week_start_date',
        'week_end_date',
        'status',
        'created_by',
        'published_by',
        'published_at',
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
            'week_start_date' => 'date',
            'week_end_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Cabang pemilik roster mingguan.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * HRD yang membuat roster mingguan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * HRD yang menerbitkan roster mingguan.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'published_by'
        );
    }

    /**
     * Item jadwal harian dalam roster mingguan.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            WeeklyScheduleItem::class
        );
    }

    /**
     * Sesi presensi otomatis yang berasal dari roster.
     */
    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(
            AttendanceSession::class
        );
    }

    /**
     * Memeriksa apakah roster masih berupa draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Memeriksa apakah roster telah diterbitkan.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Roster hanya dapat diedit selama masih draft.
     */
    public function isEditable(): bool
    {
        return $this->isDraft();
    }
}
