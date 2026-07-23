<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DecideScheduleSwapRequest;
use App\Http\Requests\StoreScheduleSwapRequest;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\ScheduleSwapRequest;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ScheduleSwapRequestController extends Controller
{
    private const ITEMS_PER_PAGE = 15;

    /**
     * Menampilkan daftar permohonan pertukaran jadwal.
     */
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->query('search', '')
        );

        $status = strtolower(
            trim(
                (string) $request->query('status', '')
            )
        );

        $dateFrom = $this->validDateOrNull(
            (string) $request->query('date_from', '')
        );

        $dateTo = $this->validDateOrNull(
            (string) $request->query('date_to', '')
        );

        if (
            ! in_array(
                $status,
                [
                    'pending',
                    'approved',
                    'rejected',
                ],
                true
            )
        ) {
            $status = '';
        }

        $query = ScheduleSwapRequest::query()
            ->with([
                'requesterEmployee:id,branch_id,employee_number,full_name,position,employment_status',
                'requesterEmployee.branch:id,code,name,status',
                'partnerEmployee:id,branch_id,employee_number,full_name,position,employment_status',
                'partnerEmployee.branch:id,code,name,status',
                'approver:id,name,email,role,status',
            ])
            ->when(
                $search !== '',
                function (Builder $query) use (
                    $search
                ): void {
                    $query->where(
                        function (
                            Builder $searchQuery
                        ) use ($search): void {
                            $searchQuery
                                ->whereHas(
                                    'requesterEmployee',
                                    function (
                                        Builder $employeeQuery
                                    ) use ($search): void {
                                        $employeeQuery
                                            ->where(
                                                'employee_number',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'full_name',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                )
                                ->orWhereHas(
                                    'partnerEmployee',
                                    function (
                                        Builder $employeeQuery
                                    ) use ($search): void {
                                        $employeeQuery
                                            ->where(
                                                'employee_number',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'full_name',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                )
                                ->orWhere(
                                    'reason',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $status !== '',
                fn (Builder $query) => $query->where(
                    'status',
                    $status
                )
            );

        if (
            $dateFrom !== null
            || $dateTo !== null
        ) {
            $query->where(
                function (Builder $dateQuery) use (
                    $dateFrom,
                    $dateTo
                ): void {
                    $dateQuery
                        ->where(
                            function (
                                Builder $requesterQuery
                            ) use (
                                $dateFrom,
                                $dateTo
                            ): void {
                                if ($dateFrom !== null) {
                                    $requesterQuery->whereDate(
                                        'requester_date',
                                        '>=',
                                        $dateFrom
                                    );
                                }

                                if ($dateTo !== null) {
                                    $requesterQuery->whereDate(
                                        'requester_date',
                                        '<=',
                                        $dateTo
                                    );
                                }
                            }
                        )
                        ->orWhere(
                            function (
                                Builder $partnerQuery
                            ) use (
                                $dateFrom,
                                $dateTo
                            ): void {
                                if ($dateFrom !== null) {
                                    $partnerQuery->whereDate(
                                        'partner_date',
                                        '>=',
                                        $dateFrom
                                    );
                                }

                                if ($dateTo !== null) {
                                    $partnerQuery->whereDate(
                                        'partner_date',
                                        '<=',
                                        $dateTo
                                    );
                                }
                            }
                        );
                }
            );
        }

        $scheduleSwapRequests = $query
            ->orderByRaw(
                "CASE
                    WHEN status = 'pending' THEN 0
                    ELSE 1
                END"
            )
            ->orderByDesc('created_at')
            ->paginate(self::ITEMS_PER_PAGE)
            ->withQueryString();

        return view(
            'schedule-swap-requests.index',
            [
                'scheduleSwapRequests' => $scheduleSwapRequests,

                'search' => $search,

                'selectedStatus' => $status,

                'dateFrom' => $dateFrom,

                'dateTo' => $dateTo,
            ]
        );
    }

    /**
     * Menampilkan formulir pencatatan permohonan.
     */
    public function create(): View
    {
        $employees = Employee::query()
            ->with(
                'branch:id,code,name,status'
            )
            ->where(
                'employment_status',
                'active'
            )
            ->orderBy('full_name')
            ->get([
                'id',
                'branch_id',
                'employee_number',
                'full_name',
                'position',
                'employment_status',
            ]);

        return view(
            'schedule-swap-requests.create',
            [
                'employees' => $employees,
            ]
        );
    }

    /**
     * Menyimpan permohonan dengan status pending.
     */
    public function store(
        StoreScheduleSwapRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        if (
            $this->pendingDuplicateExists(
                $validated
            )
        ) {
            throw ValidationException::withMessages([
                'partner_date' => 'Permohonan pertukaran yang sama masih menunggu keputusan.',
            ]);
        }

        $scheduleSwapRequest =
            ScheduleSwapRequest::query()->create([
                'requester_employee_id' => $validated[
                        'requester_employee_id'
                    ],

                'partner_employee_id' => $validated[
                        'partner_employee_id'
                    ],

                'requester_date' => $validated['requester_date'],

                'partner_date' => $validated['partner_date'],

                'reason' => $validated['reason'],

                'status' => 'pending',

                'approved_by' => null,

                'approved_at' => null,
            ]);

        return redirect()
            ->route(
                'schedule-swap-requests.show',
                $scheduleSwapRequest
            )
            ->with(
                'success',
                'Permohonan pertukaran jadwal berhasil dicatat.'
            );
    }

    /**
     * Menampilkan detail permohonan.
     */
    public function show(
        ScheduleSwapRequest $scheduleSwapRequest
    ): View {
        $scheduleSwapRequest->load([
            'requesterEmployee:id,branch_id,employee_number,full_name,position,employment_status',
            'requesterEmployee.branch:id,code,name,address,status',
            'partnerEmployee:id,branch_id,employee_number,full_name,position,employment_status',
            'partnerEmployee.branch:id,code,name,address,status',
            'approver:id,name,email,role,status',
        ]);

        $requesterSchedule =
            $this->findEmployeeSchedule(
                (int) $scheduleSwapRequest
                    ->requester_employee_id,

                $this->dateString(
                    $scheduleSwapRequest
                        ->requester_date
                )
            );

        $partnerSchedule =
            $this->findEmployeeSchedule(
                (int) $scheduleSwapRequest
                    ->partner_employee_id,

                $this->dateString(
                    $scheduleSwapRequest
                        ->partner_date
                )
            );

        if ($requesterSchedule !== null) {
            $requesterSchedule
                ->load('workSchedule')
                ->loadCount('attendances');
        }

        if ($partnerSchedule !== null) {
            $partnerSchedule
                ->load('workSchedule')
                ->loadCount('attendances');
        }

        $canBeDecided =
            $scheduleSwapRequest->status === 'pending'
            && $requesterSchedule !== null
            && $partnerSchedule !== null
            && (int) (
                $requesterSchedule
                    ->attendances_count
                ?? 0
            ) === 0
            && (int) (
                $partnerSchedule
                    ->attendances_count
                ?? 0
            ) === 0;

        return view(
            'schedule-swap-requests.show',
            [
                'scheduleSwapRequest' => $scheduleSwapRequest,

                'requesterSchedule' => $requesterSchedule,

                'partnerSchedule' => $partnerSchedule,

                'canBeDecided' => $canBeDecided,
            ]
        );
    }

    /**
     * Menyetujui atau menolak permohonan.
     */
    public function decide(
        DecideScheduleSwapRequest $request,
        ScheduleSwapRequest $scheduleSwapRequest
    ): RedirectResponse {
        $validated = $request->validated();

        $decision = (string) $validated[
            'decision'
        ];

        $approverId = (int) $request
            ->user()
            ->getKey();

        DB::transaction(
            function () use (
                $scheduleSwapRequest,
                $decision,
                $approverId
            ): void {
                $lockedRequest =
                    ScheduleSwapRequest::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $scheduleSwapRequest
                                ->getKey()
                        );

                if (
                    $lockedRequest->status
                    !== 'pending'
                ) {
                    throw ValidationException::withMessages([
                        'decision' => 'Permohonan pertukaran jadwal sudah pernah diputuskan.',
                    ]);
                }

                if ($decision === 'rejected') {
                    $lockedRequest->update([
                        'status' => 'rejected',

                        'approved_by' => $approverId,

                        'approved_at' => now(),
                    ]);

                    return;
                }

                $requesterSchedule =
                    $this->findEmployeeSchedule(
                        (int) $lockedRequest
                            ->requester_employee_id,

                        $this->dateString(
                            $lockedRequest
                                ->requester_date
                        ),

                        true
                    );

                $partnerSchedule =
                    $this->findEmployeeSchedule(
                        (int) $lockedRequest
                            ->partner_employee_id,

                        $this->dateString(
                            $lockedRequest
                                ->partner_date
                        ),

                        true
                    );

                if (
                    $requesterSchedule === null
                    || $partnerSchedule === null
                ) {
                    throw ValidationException::withMessages([
                        'decision' => 'Jadwal salah satu karyawan sudah tidak tersedia. Permohonan tidak dapat disetujui.',
                    ]);
                }

                if (
                    $requesterSchedule
                        ->attendances()
                        ->exists()
                    || $partnerSchedule
                        ->attendances()
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'decision' => 'Pertukaran tidak dapat disetujui karena salah satu jadwal sudah memiliki data presensi.',
                    ]);
                }

                $requesterOriginal = [
                    'work_schedule_id' => $requesterSchedule
                        ->work_schedule_id,

                    'schedule_status' => $requesterSchedule
                        ->schedule_status,
                ];

                $partnerOriginal = [
                    'work_schedule_id' => $partnerSchedule
                        ->work_schedule_id,

                    'schedule_status' => $partnerSchedule
                        ->schedule_status,
                ];

                $requesterSchedule->update([
                    'work_schedule_id' => $partnerOriginal[
                            'work_schedule_id'
                        ],

                    'schedule_status' => $partnerOriginal[
                            'schedule_status'
                        ],

                    'approved_by' => $approverId,
                ]);

                $partnerSchedule->update([
                    'work_schedule_id' => $requesterOriginal[
                            'work_schedule_id'
                        ],

                    'schedule_status' => $requesterOriginal[
                            'schedule_status'
                        ],

                    'approved_by' => $approverId,
                ]);

                $lockedRequest->update([
                    'status' => 'approved',

                    'approved_by' => $approverId,

                    'approved_at' => now(),
                ]);
            },
            3
        );

        $message = $decision === 'approved'
            ? 'Permohonan pertukaran jadwal berhasil disetujui dan jadwal kedua karyawan telah diperbarui.'
            : 'Permohonan pertukaran jadwal berhasil ditolak.';

        return redirect()
            ->route(
                'schedule-swap-requests.show',
                $scheduleSwapRequest
            )
            ->with(
                'success',
                $message
            );
    }

    /**
     * Memeriksa permohonan pending yang sama
     * atau dengan posisi pengaju dan pasangan terbalik.
     *
     * @param  array<string, mixed>  $validated
     */
    private function pendingDuplicateExists(
        array $validated
    ): bool {
        return ScheduleSwapRequest::query()
            ->where('status', 'pending')
            ->where(
                function (Builder $query) use (
                    $validated
                ): void {
                    $query
                        ->where(
                            function (
                                Builder $sameQuery
                            ) use (
                                $validated
                            ): void {
                                $sameQuery
                                    ->where(
                                        'requester_employee_id',
                                        $validated[
                                            'requester_employee_id'
                                        ]
                                    )
                                    ->where(
                                        'partner_employee_id',
                                        $validated[
                                            'partner_employee_id'
                                        ]
                                    )
                                    ->whereDate(
                                        'requester_date',
                                        $validated[
                                            'requester_date'
                                        ]
                                    )
                                    ->whereDate(
                                        'partner_date',
                                        $validated[
                                            'partner_date'
                                        ]
                                    );
                            }
                        )
                        ->orWhere(
                            function (
                                Builder $reverseQuery
                            ) use (
                                $validated
                            ): void {
                                $reverseQuery
                                    ->where(
                                        'requester_employee_id',
                                        $validated[
                                            'partner_employee_id'
                                        ]
                                    )
                                    ->where(
                                        'partner_employee_id',
                                        $validated[
                                            'requester_employee_id'
                                        ]
                                    )
                                    ->whereDate(
                                        'requester_date',
                                        $validated[
                                            'partner_date'
                                        ]
                                    )
                                    ->whereDate(
                                        'partner_date',
                                        $validated[
                                            'requester_date'
                                        ]
                                    );
                            }
                        );
                }
            )
            ->exists();
    }

    /**
     * Mencari jadwal karyawan berdasarkan tanggal.
     */
    private function findEmployeeSchedule(
        int $employeeId,
        string $scheduleDate,
        bool $lockForUpdate = false
    ): ?EmployeeSchedule {
        $query = EmployeeSchedule::query()
            ->where(
                'employee_id',
                $employeeId
            )
            ->whereDate(
                'schedule_date',
                $scheduleDate
            );

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * Mengubah nilai tanggal menjadi Y-m-d.
     */
    private function dateString(
        mixed $value
    ): string {
        if (
            $value instanceof \DateTimeInterface
        ) {
            return $value->format('Y-m-d');
        }

        return substr(
            (string) $value,
            0,
            10
        );
    }

    /**
     * Memastikan filter tanggal menggunakan Y-m-d.
     */
    private function validDateOrNull(
        string $value
    ): ?string {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        if (
            $date === false
            || $date->format('Y-m-d')
                !== $value
        ) {
            return null;
        }

        return $value;
    }
}
