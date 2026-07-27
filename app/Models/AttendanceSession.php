<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    public const TYPE_AUTO = 'auto';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_AUTOMATIC = 'automatic';

    use HasFactory, HasUuids;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * public_id tidak dimasukkan karena dibuat otomatis oleh Laravel.
     *
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'weekly_schedule_id',
        'attendance_type',
        'session_source',
        'automation_key',
        'session_date',
        'start_time',
        'end_time',
        'encrypted_secret',
        'status',
        'created_by',
        'closed_at',
    ];

    /**
     * Atribut yang tidak boleh muncul dalam array atau JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'encrypted_secret',
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'closed_at' => 'datetime',
            'encrypted_secret' => 'encrypted',
        ];
    }

    /**
     * Kolom yang menerima UUID otomatis.
     *
     * Primary key tetap menggunakan id BIGINT.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /**
     * Cabang tempat sesi presensi berlaku.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Roster mingguan yang menghasilkan sesi otomatis.
     */
    public function weeklySchedule(): BelongsTo
    {
        return $this->belongsTo(
            WeeklySchedule::class
        );
    }

    /**
     * HRD atau admin yang membuat sesi.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * Presensi valid yang menggunakan sesi ini.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Log validasi yang berkaitan dengan sesi.
     */
    public function validationLogs(): HasMany
    {
        return $this->hasMany(ValidationLog::class);
    }

    /**
     * Memeriksa apakah sesi dibuat oleh sistem otomatis.
     */
    public function isAutomatic(): bool
    {
        return $this->session_source === self::SOURCE_AUTOMATIC;
    }

    /**
     * Memeriksa apakah sesi dibuat secara manual.
     */
    public function isManual(): bool
    {
        return $this->session_source === self::SOURCE_MANUAL;
    }

    /**
     * Memeriksa apakah sesi menggunakan tipe resolver otomatis.
     */
    public function isAutoType(): bool
    {
        return $this->attendance_type === self::TYPE_AUTO;
    }

    /**
     * Memeriksa apakah sesi berstatus aktif.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Memeriksa apakah sesi sudah ditutup manual.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Memeriksa apakah sesi sudah ditandai kedaluwarsa.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Memeriksa apakah sesi merupakan presensi masuk.
     */
    public function isCheckIn(): bool
    {
        return $this->attendance_type === 'check_in';
    }

    /**
     * Memeriksa apakah sesi merupakan presensi pulang.
     */
    public function isCheckOut(): bool
    {
        return $this->attendance_type === 'check_out';
    }

    /**
     * Memeriksa apakah waktu tertentu berada dalam rentang sesi.
     */
    public function isWithinTimeWindow(
        DateTimeInterface|string|null $moment = null
    ): bool {
        $currentTime = $this->resolveMoment($moment);

        return $currentTime->greaterThanOrEqualTo($this->start_time)
            && $currentTime->lessThanOrEqualTo($this->end_time);
    }

    /**
     * Memeriksa apakah sesi dapat digunakan pada waktu tertentu.
     */
    public function isUsableAt(
        DateTimeInterface|string|null $moment = null
    ): bool {
        return $this->isActive()
            && $this->isWithinTimeWindow($moment);
    }

    /**
     * Mengubah waktu masukan menjadi CarbonImmutable.
     */
    private function resolveMoment(
        DateTimeInterface|string|null $moment
    ): CarbonImmutable {
        if ($moment instanceof DateTimeInterface) {
            return CarbonImmutable::instance($moment);
        }

        if (is_string($moment)) {
            return CarbonImmutable::parse(
                $moment,
                config('app.timezone')
            );
        }

        return CarbonImmutable::now(
            config('app.timezone')
        );
    }
}
