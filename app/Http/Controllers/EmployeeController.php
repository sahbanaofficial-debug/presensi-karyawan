<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

final class EmployeeController extends Controller
{
    private const ITEMS_PER_PAGE = 10;

    /**
     * Menampilkan daftar karyawan.
     */
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->query('search', '')
        );

        $branchIdInput = $request->query('branch_id');

        $branchId = is_numeric($branchIdInput)
            && (int) $branchIdInput > 0
                ? (int) $branchIdInput
                : null;

        $status = strtolower(
            trim((string) $request->query('status', ''))
        );

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = '';
        }

        $employees = Employee::query()
            ->with([
                'user:id,name,email,status',
                'branch:id,code,name,status',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $query->where(
                        function ($searchQuery) use ($search): void {
                            $searchQuery
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
                fn ($query) => $query->where(
                    'branch_id',
                    $branchId
                )
            )
            ->when(
                $status !== '',
                fn ($query) => $query->where(
                    'employment_status',
                    $status
                )
            )
            ->orderBy('full_name')
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

        return view('employees.index', [
            'employees' => $employees,
            'branches' => $branches,
            'search' => $search,
            'selectedBranchId' => $branchId,
            'selectedStatus' => $status,
        ]);
    }

    /**
     * Menampilkan formulir penambahan karyawan.
     */
    public function create(): View
    {
        $branches = Branch::query()
            ->where('status', 'active')
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
            ]);

        return view('employees.create', [
            'branches' => $branches,
        ]);
    }

    /**
     * Menyimpan akun dan profil karyawan.
     */
    public function store(
        StoreEmployeeRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        $employee = DB::transaction(
            function () use ($validated): Employee {
                $user = User::query()->create([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make(
                        $validated['password']
                    ),
                    'role' => 'employee',
                    'status' => $validated['account_status'],
                    'last_login_at' => null,
                ]);

                return Employee::query()->create([
                    'user_id' => $user->id,
                    'branch_id' => $validated['branch_id'],
                    'employee_number' => $validated['employee_number'],
                    'full_name' => $validated['full_name'],
                    'position' => $validated['position'],
                    'phone_number' => $validated['phone_number'],
                    'employment_status' => $validated[
                        'employment_status'
                    ],
                ]);
            }
        );

        return redirect()
            ->route('employees.show', $employee)
            ->with(
                'success',
                'Data karyawan dan akun berhasil ditambahkan.'
            );
    }

    /**
     * Menampilkan detail karyawan.
     */
    public function show(Employee $employee): View
    {
        $employee
            ->load([
                'user:id,name,email,role,status,last_login_at',
                'branch:id,code,name,address,status',
            ])
            ->loadCount([
                'schedules',
                'attendances',
                'requestedScheduleSwaps',
                'partneredScheduleSwaps',
            ]);

        return view('employees.show', [
            'employee' => $employee,
        ]);
    }

    /**
     * Menampilkan formulir perubahan karyawan.
     */
    public function edit(Employee $employee): View
    {
        $employee->load('user');

        $branches = Branch::query()
            ->where(
                function ($query) use ($employee): void {
                    $query
                        ->where('status', 'active')
                        ->orWhere(
                            'id',
                            $employee->branch_id
                        );
                }
            )
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
                'status',
            ]);

        return view('employees.edit', [
            'employee' => $employee,
            'branches' => $branches,
        ]);
    }

    /**
     * Memperbarui akun dan profil karyawan.
     */
    public function update(
        UpdateEmployeeRequest $request,
        Employee $employee
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(
            function () use ($validated, $employee): void {
                $employee->loadMissing('user');

                $userData = [
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'status' => $validated['account_status'],
                ];

                if (
                    isset($validated['password'])
                    && trim((string) $validated['password']) !== ''
                ) {
                    $userData['password'] = Hash::make(
                        $validated['password']
                    );
                }

                $employee->user->update($userData);

                $employee->update([
                    'branch_id' => $validated['branch_id'],
                    'employee_number' => $validated['employee_number'],
                    'full_name' => $validated['full_name'],
                    'position' => $validated['position'],
                    'phone_number' => $validated['phone_number'],
                    'employment_status' => $validated[
                        'employment_status'
                    ],
                ]);
            }
        );

        return redirect()
            ->route('employees.show', $employee)
            ->with(
                'success',
                'Data karyawan dan akun berhasil diperbarui.'
            );
    }
}
