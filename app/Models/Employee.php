<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'branch_id',
        'employee_number',
        'full_name',
        'position',
        'phone_number',
        'employment_status',
    ];

    /**
     * Akun pengguna yang terhubung dengan profil karyawan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cabang tempat karyawan ditugaskan.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Seluruh jadwal harian milik karyawan.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    /**
     * Item roster mingguan milik karyawan.
     */
    public function weeklyScheduleItems(): HasMany
    {
        return $this->hasMany(
            WeeklyScheduleItem::class
        );
    }

    /**
     * Seluruh transaksi presensi milik karyawan.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Permohonan pertukaran jadwal yang diajukan karyawan.
     */
    public function requestedScheduleSwaps(): HasMany
    {
        return $this->hasMany(
            ScheduleSwapRequest::class,
            'requester_employee_id'
        );
    }

    /**
     * Permohonan pertukaran jadwal ketika karyawan menjadi pasangan.
     */
    public function partneredScheduleSwaps(): HasMany
    {
        return $this->hasMany(
            ScheduleSwapRequest::class,
            'partner_employee_id'
        );
    }

    /**
     * Memeriksa apakah profil karyawan masih aktif.
     */
    public function isActive(): bool
    {
        return $this->employment_status === 'active';
    }

    /**
     * Memeriksa apakah karyawan ditempatkan pada cabang tertentu.
     */
    public function isAssignedToBranch(int $branchId): bool
    {
        return (int) $this->branch_id === $branchId;
    }
}
