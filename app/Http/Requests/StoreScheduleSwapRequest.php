<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

final class StoreScheduleSwapRequest extends FormRequest
{
    /**
     * Permohonan awal dapat dicatat oleh HRD atau admin.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($user->hasRole('hrd')) {
            return true;
        }

        if (
            ! $user->hasRole('admin')
            || $user->branch_id === null
        ) {
            return false;
        }

        $branchId = (int) $user->branch_id;

        $branchIsActive = Branch::query()
            ->whereKey($branchId)
            ->where('status', 'active')
            ->exists();

        if (! $branchIsActive) {
            return false;
        }

        foreach (
            [
                'requester_employee_id',
                'partner_employee_id',
            ] as $employeeField
        ) {
            $employeeId = $this->input(
                $employeeField
            );

            if (
                ! is_numeric($employeeId)
                || (int) $employeeId <= 0
            ) {
                continue;
            }

            $employeeIsOwned =
                Employee::query()
                    ->whereKey(
                        (int) $employeeId
                    )
                    ->where(
                        'branch_id',
                        $branchId
                    )
                    ->where(
                        'employment_status',
                        'active'
                    )
                    ->exists();

            if (! $employeeIsOwned) {
                return false;
            }
        }

        return true;
    }

    /**
     * Menormalisasi data sebelum proses validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'requester_employee_id' => $this->nullableValue(
                'requester_employee_id'
            ),

            'partner_employee_id' => $this->nullableValue(
                'partner_employee_id'
            ),

            'requester_date' => trim(
                (string) $this->input('requester_date')
            ),

            'partner_date' => trim(
                (string) $this->input('partner_date')
            ),

            'reason' => $this->nullableString('reason'),
        ]);
    }

    /**
     * Aturan validasi pengajuan pertukaran jadwal.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'requester_employee_id' => [
                'bail',
                'required',
                'integer',
                $this->activeEmployeeRule(),
            ],

            'partner_employee_id' => [
                'bail',
                'required',
                'integer',
                'different:requester_employee_id',
                $this->activeEmployeeRule(),
            ],

            'requester_date' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                $this->employeeScheduleExistsRule(
                    'requester_employee_id',
                    'Jadwal karyawan pengaju pada tanggal tersebut tidak ditemukan.'
                ),
            ],

            'partner_date' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                $this->employeeScheduleExistsRule(
                    'partner_employee_id',
                    'Jadwal karyawan pasangan pada tanggal tersebut tidak ditemukan.'
                ),
            ],

            'reason' => [
                'bail',
                'required',
                'string',
                'max:65535',
            ],
        ];
    }

    /**
     * Pesan validasi dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'requester_employee_id.required' => 'Karyawan pengaju wajib dipilih.',

            'requester_employee_id.integer' => 'Data karyawan pengaju tidak valid.',

            'requester_employee_id.exists' => 'Karyawan pengaju tidak ditemukan atau sudah tidak aktif.',

            'partner_employee_id.required' => 'Karyawan pasangan wajib dipilih.',

            'partner_employee_id.integer' => 'Data karyawan pasangan tidak valid.',

            'partner_employee_id.different' => 'Karyawan pasangan harus berbeda dari karyawan pengaju.',

            'partner_employee_id.exists' => 'Karyawan pasangan tidak ditemukan atau sudah tidak aktif.',

            'requester_date.required' => 'Tanggal jadwal karyawan pengaju wajib diisi.',

            'requester_date.date_format' => 'Format tanggal jadwal karyawan pengaju tidak valid.',

            'partner_date.required' => 'Tanggal jadwal karyawan pasangan wajib diisi.',

            'partner_date.date_format' => 'Format tanggal jadwal karyawan pasangan tidak valid.',

            'reason.required' => 'Alasan pertukaran jadwal wajib diisi.',

            'reason.string' => 'Alasan pertukaran jadwal harus berupa teks.',

            'reason.max' => 'Alasan pertukaran jadwal terlalu panjang.',
        ];
    }

    /**
     * Nama atribut untuk pesan validasi bawaan.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'requester_employee_id' => 'karyawan pengaju',

            'partner_employee_id' => 'karyawan pasangan',

            'requester_date' => 'tanggal jadwal pengaju',

            'partner_date' => 'tanggal jadwal pasangan',

            'reason' => 'alasan pertukaran',
        ];
    }

    /**
     * Karyawan yang dipilih harus masih aktif.
     */
    private function activeEmployeeRule(): Exists
    {
        return Rule::exists(
            'employees',
            'id'
        )->where(
            fn ($query) => $query->where(
                'employment_status',
                'active'
            )
        );
    }

    /**
     * Memastikan karyawan memiliki jadwal pada tanggal pilihan.
     */
    private function employeeScheduleExistsRule(
        string $employeeField,
        string $message
    ): Closure {
        return function (
            string $attribute,
            mixed $value,
            Closure $fail
        ) use (
            $employeeField,
            $message
        ): void {
            $employeeId = $this->input(
                $employeeField
            );

            if (
                ! is_numeric($employeeId)
                || (int) $employeeId <= 0
                || ! is_string($value)
                || $value === ''
            ) {
                return;
            }

            $scheduleExists = EmployeeSchedule::query()
                ->where(
                    'employee_id',
                    (int) $employeeId
                )
                ->whereDate(
                    'schedule_date',
                    $value
                )
                ->exists();

            if (! $scheduleExists) {
                $fail($message);
            }
        };
    }

    /**
     * Mengubah nilai kosong menjadi null.
     */
    private function nullableValue(
        string $field
    ): mixed {
        $value = $this->input($field);

        if ($value === null) {
            return null;
        }

        if (
            is_string($value)
            && trim($value) === ''
        ) {
            return null;
        }

        return $value;
    }

    /**
     * Menghapus spasi di awal dan akhir teks.
     */
    private function nullableString(
        string $field
    ): ?string {
        $value = $this->input($field);

        if ($value === null) {
            return null;
        }

        $normalizedValue = trim(
            (string) $value
        );

        return $normalizedValue === ''
            ? null
            : $normalizedValue;
    }
}
