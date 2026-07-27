<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
    ];

    /**
     * Atribut yang tidak boleh muncul saat model diubah menjadi array atau JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Profil karyawan yang terhubung dengan akun.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Sesi presensi yang dibuat oleh pengguna.
     */
    public function createdAttendanceSessions(): HasMany
    {
        return $this->hasMany(
            AttendanceSession::class,
            'created_by'
        );
    }

    /**
     * Roster mingguan yang dibuat oleh pengguna.
     */
    public function createdWeeklySchedules(): HasMany
    {
        return $this->hasMany(
            WeeklySchedule::class,
            'created_by'
        );
    }

    /**
     * Roster mingguan yang diterbitkan oleh pengguna.
     */
    public function publishedWeeklySchedules(): HasMany
    {
        return $this->hasMany(
            WeeklySchedule::class,
            'published_by'
        );
    }

    /**
     * Terminal cabang yang didaftarkan oleh pengguna.
     */
    public function createdBranchTerminals(): HasMany
    {
        return $this->hasMany(
            BranchTerminal::class,
            'created_by'
        );
    }

    /**
     * Terminal cabang yang dicabut oleh pengguna.
     */
    public function revokedBranchTerminals(): HasMany
    {
        return $this->hasMany(
            BranchTerminal::class,
            'revoked_by'
        );
    }

    /**
     * Jadwal karyawan yang disetujui oleh pengguna.
     */
    public function approvedEmployeeSchedules(): HasMany
    {
        return $this->hasMany(
            EmployeeSchedule::class,
            'approved_by'
        );
    }

    /**
     * Permohonan pertukaran jadwal yang diputuskan oleh pengguna.
     */
    public function approvedScheduleSwapRequests(): HasMany
    {
        return $this->hasMany(
            ScheduleSwapRequest::class,
            'approved_by'
        );
    }

    /**
     * Log validasi yang berkaitan dengan pengguna.
     */
    public function validationLogs(): HasMany
    {
        return $this->hasMany(
            ValidationLog::class,
            'user_id'
        );
    }

    /**
     * Log koreksi yang dilakukan oleh pengguna.
     */
    public function changedCorrectionLogs(): HasMany
    {
        return $this->hasMany(
            CorrectionLog::class,
            'changed_by'
        );
    }

    /**
     * Log koreksi yang disetujui oleh pengguna.
     */
    public function approvedCorrectionLogs(): HasMany
    {
        return $this->hasMany(
            CorrectionLog::class,
            'approved_by'
        );
    }

    /**
     * Memeriksa apakah pengguna memiliki role tertentu.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Memeriksa apakah akun masih aktif.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Memeriksa kelayakan profil karyawan.
     *
     * HRD dan admin tidak diwajibkan memiliki profil karyawan.
     * Akun employee wajib terhubung dengan profil yang masih aktif.
     */
    public function hasActiveEmployeeProfile(): bool
    {
        if (! $this->hasRole('employee')) {
            return true;
        }

        return $this->employee()
            ->where('employment_status', 'active')
            ->exists();
    }
}
