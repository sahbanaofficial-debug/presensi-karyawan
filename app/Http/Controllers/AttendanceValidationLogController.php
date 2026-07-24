<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

final class AttendanceValidationLogController extends Controller
{
    private const ITEMS_PER_PAGE = 20;

    private const LOCATION_VALIDATION_TYPES = [
        'location_accuracy_too_low',
        'outside_geofence',
        'branch_mismatch',
    ];

    private const QR_VALIDATION_TYPES = [
        'session_not_found',
        'session_inactive',
        'session_not_started',
        'session_expired',
        'session_date_mismatch',
        'totp_invalid',
    ];

    private const SCHEDULE_VALIDATION_TYPES = [
        'employee_schedule_not_found',
        'schedule_not_working',
        'check_in_too_early',
        'check_in_too_late',
        'check_out_too_early',
        'check_out_too_late',
        'duplicate_attendance',
    ];

    /**
     * Menampilkan log validasi transaksi presensi
     * untuk HRD.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user !== null
            && $user->role === 'hrd',
            403
        );

        $availableValidationTypes =
            $this->availableValidationTypes();

        $validationDate = $this->validDateOrNull(
            (string) $request->query(
                'validation_date',
                ''
            )
        );

        $branchId = $this->positiveIntegerOrNull(
            $request->query('branch_id')
        );

        $employeeId = $this->positiveIntegerOrNull(
            $request->query('employee_id')
        );

        $status = $this->validEnumOrEmpty(
            value: (string) $request->query(
                'status',
                ''
            ),
            allowedValues: [
                'accepted',
                'rejected',
            ]
        );

        $validationType =
            $this->validEnumOrEmpty(
                value: (string) $request->query(
                    'validation_type',
                    ''
                ),
                allowedValues: $availableValidationTypes->all()
            );

        $baseQuery = $this->baseQuery()
            ->when(
                $validationDate !== null,
                static fn (QueryBuilder $query) => $query->whereDate(
                    'validation_logs.created_at',
                    $validationDate
                )
            )
            ->when(
                $branchId !== null,
                static fn (QueryBuilder $query) => $query->whereRaw(
                    'COALESCE(
                            session_branches.id,
                            employee_branches.id
                        ) = ?',
                    [
                        $branchId,
                    ]
                )
            )
            ->when(
                $employeeId !== null,
                static fn (QueryBuilder $query) => $query->where(
                    'employees.id',
                    $employeeId
                )
            )
            ->when(
                $status !== '',
                static fn (QueryBuilder $query) => $query->where(
                    'validation_logs.status',
                    $status
                )
            )
            ->when(
                $validationType !== '',
                static fn (QueryBuilder $query) => $query->where(
                    'validation_logs.validation_type',
                    $validationType
                )
            );

        $summary = [
            'total' => (clone $baseQuery)
                ->count('validation_logs.id'),

            'accepted' => (clone $baseQuery)
                ->where(
                    'validation_logs.status',
                    'accepted'
                )
                ->count('validation_logs.id'),

            'rejected' => (clone $baseQuery)
                ->where(
                    'validation_logs.status',
                    'rejected'
                )
                ->count('validation_logs.id'),

            'location' => (clone $baseQuery)
                ->whereIn(
                    'validation_logs.validation_type',
                    self::LOCATION_VALIDATION_TYPES
                )
                ->count('validation_logs.id'),

            'qr' => (clone $baseQuery)
                ->whereIn(
                    'validation_logs.validation_type',
                    self::QR_VALIDATION_TYPES
                )
                ->count('validation_logs.id'),

            'schedule' => (clone $baseQuery)
                ->whereIn(
                    'validation_logs.validation_type',
                    self::SCHEDULE_VALIDATION_TYPES
                )
                ->count('validation_logs.id'),
        ];

        $logs = $baseQuery
            ->select([
                'validation_logs.id',
                'validation_logs.user_id',
                'validation_logs.attendance_session_id',
                'validation_logs.validation_type',
                'validation_logs.status',
                'validation_logs.reason',
                'validation_logs.payload_reference',
                'validation_logs.latitude',
                'validation_logs.longitude',
                'validation_logs.accuracy',
                'validation_logs.distance',
                'validation_logs.created_at',

                'users.name as user_name',
                'users.email as user_email',

                'employees.id as employee_id',
                'employees.employee_number',
                'employees.full_name as employee_name',
                'employees.position',

                'attendance_sessions.public_id
                    as session_public_id',

                'attendance_sessions.attendance_type',
                'attendance_sessions.session_date',
            ])
            ->selectRaw(
                'COALESCE(
                    session_branches.id,
                    employee_branches.id
                ) as branch_id'
            )
            ->selectRaw(
                'COALESCE(
                    session_branches.code,
                    employee_branches.code
                ) as branch_code'
            )
            ->selectRaw(
                'COALESCE(
                    session_branches.name,
                    employee_branches.name
                ) as branch_name'
            )
            ->orderByDesc(
                'validation_logs.created_at'
            )
            ->orderByDesc(
                'validation_logs.id'
            )
            ->paginate(self::ITEMS_PER_PAGE)
            ->withQueryString();

        $branches = Branch::query()
            ->orderBy('name')
            ->get();

        $employees = Employee::query()
            ->with('branch')
            ->when(
                $branchId !== null,
                static fn ($query) => $query->where(
                    'branch_id',
                    $branchId
                )
            )
            ->orderBy('full_name')
            ->get();

        return view(
            'attendance-validation-logs.index',
            [
                'logs' => $logs,

                'branches' => $branches,

                'employees' => $employees,

                'availableValidationTypes' => $availableValidationTypes,

                'summary' => $summary,

                'selectedValidationDate' => $validationDate,

                'selectedBranchId' => $branchId,

                'selectedEmployeeId' => $employeeId,

                'selectedStatus' => $status,

                'selectedValidationType' => $validationType,
            ]
        );
    }

    /**
     * Membentuk query utama dan relasi data log.
     */
    private function baseQuery(): QueryBuilder
    {
        return DB::table('validation_logs')
            ->leftJoin(
                'users',
                'users.id',
                '=',
                'validation_logs.user_id'
            )
            ->leftJoin(
                'employees',
                'employees.user_id',
                '=',
                'users.id'
            )
            ->leftJoin(
                'attendance_sessions',
                'attendance_sessions.id',
                '=',
                'validation_logs.attendance_session_id'
            )
            ->leftJoin(
                'branches as session_branches',
                'session_branches.id',
                '=',
                'attendance_sessions.branch_id'
            )
            ->leftJoin(
                'branches as employee_branches',
                'employee_branches.id',
                '=',
                'employees.branch_id'
            );
    }

    /**
     * Mengambil seluruh jenis validasi yang
     * sudah tercatat di database.
     *
     * @return Collection<int, string>
     */
    private function availableValidationTypes(): Collection
    {
        return DB::table('validation_logs')
            ->whereNotNull('validation_type')
            ->where(
                'validation_type',
                '<>',
                ''
            )
            ->distinct()
            ->orderBy('validation_type')
            ->pluck('validation_type')
            ->map(
                static fn (mixed $value): string => (string) $value
            )
            ->values();
    }

    /**
     * Mengubah nilai menjadi ID positif.
     */
    private function positiveIntegerOrNull(
        mixed $value
    ): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $validatedValue = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($validatedValue === false) {
            return null;
        }

        return (int) $validatedValue;
    }

    /**
     * Memeriksa nilai filter berbentuk enum.
     *
     * @param  array<int, string>  $allowedValues
     */
    private function validEnumOrEmpty(
        string $value,
        array $allowedValues
    ): string {
        $value = strtolower(
            trim($value)
        );

        if (
            $value === ''
            || ! in_array(
                $value,
                $allowedValues,
                true
            )
        ) {
            return '';
        }

        return $value;
    }

    /**
     * Memeriksa tanggal dengan format Y-m-d.
     */
    private function validDateOrNull(
        string $value
    ): ?string {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat(
                '!Y-m-d',
                $value,
                (string) config(
                    'app.timezone',
                    'Asia/Jakarta'
                )
            );
        } catch (Throwable) {
            return null;
        }

        if (
            $date === false
            || $date->format('Y-m-d') !== $value
        ) {
            return null;
        }

        return $value;
    }
}
