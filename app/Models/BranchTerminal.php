<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchTerminal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    use HasFactory, HasUuids;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * public_id dibuat otomatis oleh Laravel.
     *
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'name',
        'device_token_hash',
        'activation_code_hash',
        'activation_expires_at',
        'activated_at',
        'last_seen_at',
        'status',
        'created_by',
        'revoked_by',
        'revoked_at',
    ];

    /**
     * Hash autentikasi terminal tidak boleh terekspos.
     *
     * @var list<string>
     */
    protected $hidden = [
        'device_token_hash',
        'activation_code_hash',
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activation_expires_at' => 'datetime',
            'activated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
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
     * Cabang tempat terminal terdaftar.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * HRD yang mendaftarkan terminal.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * HRD yang mencabut terminal.
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revoked_by'
        );
    }

    /**
     * Transaksi presensi yang berasal dari terminal.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Log validasi yang berasal dari terminal.
     */
    public function validationLogs(): HasMany
    {
        return $this->hasMany(ValidationLog::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    /**
     * Memeriksa apakah kode aktivasi telah kedaluwarsa.
     */
    public function isActivationExpired(): bool
    {
        return $this->activation_expires_at !== null
            && $this->activation_expires_at->isPast();
    }
}