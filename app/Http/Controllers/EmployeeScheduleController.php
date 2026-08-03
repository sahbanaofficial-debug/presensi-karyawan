<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeScheduleRequest;
use App\Http\Requests\UpdateEmployeeScheduleRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EmployeeScheduleController extends Controller
{
    private const ITEMS_PER_PAGE = 15;

    /**
     * Menampilkan daftar jadwal harian karyawan.
     */
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->query('search', '')
        );

        $branchId = $this->positiveIntegerOrNull(
            $request->query('branch_id')
        );

        $status = strtolower(
            trim(
                (string) $request->query(
                    'schedule_status',
                    ''
                )
            )
        );

        if (
            ! in_array(
                $status,
                [
                    'work',
                    'off',
                    'leave',
                    'permit',
                    'sick',
                ],
                true
            )
        ) {
            $status = '';
        }

        $dateFrom = $this->validDateOrNull(
            (string) $request->query('date_from', '')
        );

        $dateTo = $this->validDateOrNull(
            (string) $request->query('date_to', '')
        );

        $employeeSchedules = EmployeeSchedule::query()
            ->with([
                'employee:id,branch_id,employee_number,full_name,position,employment_status',
                'employee.branch:id,code,name,status',
                'workSchedule:id,name,check_in_time,check_out_time,status',
            ])
            ->withCount('attendances')
            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $query->whereHas(
                        'employee',
                        function ($employeeQuery) use (
                            $search
                        ): void {
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
                    );
                }
            )
            ->when(
                $branchId !== null,
                function ($query) use ($branchId): void {
                    $query->whereHas(
                        'employee',
                        fn ($employeeQuery) => $employeeQuery->where(
                            'branch_id',
                            $branchId
                        )
                    );
                }
            )
            ->when(
                $status !== '',
                fn ($query) => $query->where(
                    'schedule_status',
                    $status
                )
            )
            ->when(
                $dateFrom !== null,
                fn ($query) => $query->whereDate(
                    'schedule_date',
                    '>=',
                    $dateFrom
                )
            )
            ->when(
                $dateTo !== null,
                fn ($query) => $query->whereDate(
                    'schedule_date',
                    '<=',
                    $dateTo
                )
            )
            ->orderBy('schedule_date')
            ->orderBy('employee_id')
            ->orderBy('id')
            ->paginate(self::ITEMS_PER_PAGE)
            ->withQueryString();

        $branches = Branch::query()
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
                'status',
            ]);

        return view('employee-schedules.index', [
            'employeeSchedules' => $employeeSchedules,
            'branches' => $branches,
            'search' => $search,
            'selectedBranchId' => $branchId,
            'selectedStatus' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Menampilkan formulir penambahan jadwal harian.
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
            ]);

        $workSchedules = WorkSchedule::query()
            ->where('status', 'active')
            ->orderBy('check_in_time')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'check_in_time',
                'check_out_time',
                'status',
            ]);

        return view('employee-schedules.create', [
            'employees' => $employees,
            'workSchedules' => $workSchedules,
        ]);
    }

    /**
     * Menyimpan jadwal harian karyawan.
     */
    public function store(
        StoreEmployeeScheduleRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        $validated['approved_by'] = $request
            ->user()
            ->getKey();

        $employeeSchedule = EmployeeSchedule::query()
            ->create($validated);

        return redirect()
            ->route(
                'employee-schedules.show',
                $employeeSchedule
            )
            ->with(
                'success',
                'Jadwal harian karyawan berhasil ditambahkan.'
            );
    }

    /**
     * Menampilkan detail jadwal harian.
     */
    public function show(
        EmployeeSchedule $employeeSchedule
    ): View {
        $employeeSchedule
            ->load([
                'employee:id,user_id,branch_id,employee_number,full_name,position,employment_status',
                'employee.user:id,name,email,status',
                'employee.branch:id,code,name,address,status',
                'workSchedule:id,name,check_in_time,check_out_time,check_in_open_minutes,late_tolerance_minutes,check_out_limit_minutes,status',
            ])
            ->loadCount('attendances');

        $approver = User::query()->find(
            $employeeSchedule->approved_by
        );

        return view('employee-schedules.show', [
            'employeeSchedule' => $employeeSchedule,
            'approver' => $approver,
        ]);
    }

    /**
     * Menampilkan formulir perubahan jadwal harian.
     */
    public function edit(
        EmployeeSchedule $employeeSchedule
    ): View {
        $employeeSchedule->load([
            'employee.branch',
            'workSchedule',
        ]);

        $employees = Employee::query()
            ->with(
                'branch:id,code,name,status'
            )
            ->where(
                function ($query) use (
                    $employeeSchedule
                ): void {
                    $query
                        ->where(
                            'employment_status',
                            'active'
                        )
                        ->orWhere(
                            'id',
                            $employeeSchedule->employee_id
                        );
                }
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

        $workSchedules = WorkSchedule::query()
            ->where(
                function ($query) use (
                    $employeeSchedule
                ): void {
                    $query->where('status', 'active');

                    if (
                        $employeeSchedule
                            ->work_schedule_id !== null
                    ) {
                        $query->orWhere(
                            'id',
                            $employeeSchedule
                                ->work_schedule_id
                        );
                    }
                }
            )
            ->orderBy('check_in_time')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'check_in_time',
                'check_out_time',
                'status',
            ]);

        return view('employee-schedules.edit', [
            'employeeSchedule' => $employeeSchedule,
            'employees' => $employees,
            'workSchedules' => $workSchedules,
        ]);
    }

    /**
     * Memperbarui jadwal harian karyawan.
     */
    public function update(
        UpdateEmployeeScheduleRequest $request,
        EmployeeSchedule $employeeSchedule
    ): RedirectResponse {
        $validated = $request->validated();

        $validated['approved_by'] = $request
            ->user()
            ->getKey();

        $employeeSchedule->update($validated);

        return redirect()
            ->route(
                'employee-schedules.show',
                $employeeSchedule
            )
            ->with(
                'success',
                'Jadwal harian karyawan berhasil diperbarui.'
            );
    }

    /**
     * Menghapus jadwal yang belum memiliki presensi.
     */
    public function destroy(
        EmployeeSchedule $employeeSchedule
    ): RedirectResponse {
        if (
            $employeeSchedule
                ->attendances()
                ->exists()
        ) {
            return redirect()
                ->route(
                    'employee-schedules.show',
                    $employeeSchedule
                )
                ->with(
                    'error',
                    'Jadwal harian tidak dapat dihapus karena sudah memiliki data presensi.'
                );
        }

        $employeeSchedule->delete();

        return redirect()
            ->route('employee-schedules.index')
            ->with(
                'success',
                'Jadwal harian karyawan berhasil dihapus.'
            );
    }

    /**
     * Mengubah input menjadi bilangan bulat positif.
     */
    private function positiveIntegerOrNull(
        mixed $value
    ): ?int {
        if (
            ! is_numeric($value)
            || (int) $value <= 0
        ) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Memastikan filter tanggal menggunakan format Y-m-d.
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
            || $date->format('Y-m-d') !== $value
        ) {
            return null;
        }

        return $value;
    }
}
