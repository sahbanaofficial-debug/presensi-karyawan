<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSwapRequest extends Model
{
    use HasFactory;

    /**
     * Atribut yang boleh diisi melalui mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'requester_employee_id',
        'partner_employee_id',
        'requester_date',
        'partner_date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requester_date' => 'date',
            'partner_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Karyawan yang mengajukan pertukaran jadwal.
     */
    public function requesterEmployee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'requester_employee_id'
        );
    }

    /**
     * Karyawan pasangan pertukaran jadwal.
     */
    public function partnerEmployee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'partner_employee_id'
        );
    }

    /**
     * Pengguna yang memberikan keputusan.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    /**
     * Memeriksa apakah permohonan masih menunggu keputusan.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Memeriksa apakah permohonan telah disetujui.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Memeriksa apakah permohonan telah ditolak.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Memeriksa apakah keputusan sudah diberikan.
     */
    public function hasDecision(): bool
    {
        return $this->isApproved() || $this->isRejected();
    }

    /**
     * Memeriksa apakah dua pihak pertukaran berbeda.
     */
    public function hasDifferentEmployees(): bool
    {
        return (int) $this->requester_employee_id
            !== (int) $this->partner_employee_id;
    }
}
