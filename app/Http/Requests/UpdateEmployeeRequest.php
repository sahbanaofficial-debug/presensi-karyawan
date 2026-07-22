<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

final class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Hanya HRD yang dapat memperbarui data karyawan.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('hrd') === true;
    }

    /**
     * Menormalisasi input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(
                trim((string) $this->input('email'))
            ),
            'employee_number' => Str::upper(
                trim((string) $this->input('employee_number'))
            ),
            'full_name' => trim(
                (string) $this->input('full_name')
            ),
            'position' => trim(
                (string) $this->input('position')
            ),
            'phone_number' => $this->nullableString(
                'phone_number'
            ),
            'account_status' => Str::lower(
                trim((string) $this->input('account_status'))
            ),
            'employment_status' => Str::lower(
                trim((string) $this->input('employment_status'))
            ),
        ]);
    }

    /**
     * Aturan validasi perubahan akun dan profil karyawan.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'bail',
                'required',
                'string',
                'email',
                'max:150',
                $this->uniqueEmailRule(),
            ],
            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'employee_number' => [
                'bail',
                'required',
                'string',
                'max:50',
                $this->uniqueEmployeeNumberRule(),
            ],
            'full_name' => [
                'bail',
                'required',
                'string',
                'max:150',
            ],
            'position' => [
                'bail',
                'required',
                'string',
                'max:100',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s().]+$/',
            ],
            'branch_id' => [
                'bail',
                'required',
                'integer',
                $this->validBranchRule(),
            ],
            'account_status' => [
                'bail',
                'required',
                'string',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
            'employment_status' => [
                'bail',
                'required',
                'string',
                Rule::in([
                    'active',
                    'inactive',
                ]),
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
            'email.required' => 'Email akun wajib diisi.',
            'email.email' => 'Format email akun tidak valid.',
            'email.max' => 'Email akun maksimal 150 karakter.',
            'email.unique' => 'Email akun sudah digunakan.',

            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'password.min' => 'Kata sandi minimal delapan karakter.',
            'password.mixed' => 'Kata sandi harus menggunakan huruf besar dan huruf kecil.',
            'password.numbers' => 'Kata sandi harus mengandung angka.',
            'password.symbols' => 'Kata sandi harus mengandung simbol.',

            'employee_number.required' => 'Nomor karyawan wajib diisi.',
            'employee_number.max' => 'Nomor karyawan maksimal 50 karakter.',
            'employee_number.unique' => 'Nomor karyawan sudah digunakan.',

            'full_name.required' => 'Nama lengkap wajib diisi.',
            'full_name.max' => 'Nama lengkap maksimal 150 karakter.',

            'position.required' => 'Jabatan wajib diisi.',
            'position.max' => 'Jabatan maksimal 100 karakter.',

            'phone_number.max' => 'Nomor telepon maksimal 20 karakter.',
            'phone_number.regex' => 'Format nomor telepon tidak valid.',

            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.integer' => 'Data cabang tidak valid.',
            'branch_id.exists' => 'Cabang tidak ditemukan atau sudah tidak aktif.',

            'account_status.required' => 'Status akun wajib dipilih.',
            'account_status.in' => 'Status akun harus active atau inactive.',

            'employment_status.required' => 'Status karyawan wajib dipilih.',
            'employment_status.in' => 'Status karyawan harus active atau inactive.',
        ];
    }

    /**
     * Mengabaikan email akun milik karyawan yang sedang diedit.
     */
    private function uniqueEmailRule(): Unique
    {
        $rule = Rule::unique('users', 'email');
        $employee = $this->routeEmployee();

        if ($employee !== null) {
            $rule->ignore($employee->user_id);
        }

        return $rule;
    }

    /**
     * Mengabaikan nomor milik karyawan yang sedang diedit.
     */
    private function uniqueEmployeeNumberRule(): Unique
    {
        $rule = Rule::unique(
            'employees',
            'employee_number'
        );

        $employee = $this->routeEmployee();

        if ($employee !== null) {
            $rule->ignore($employee->getKey());
        }

        return $rule;
    }

    /**
     * Cabang tujuan harus aktif.
     *
     * Cabang lama tetap dapat dipertahankan walaupun telah dinonaktifkan,
     * sehingga HRD masih dapat memperbarui data lain pada profil karyawan.
     */
    private function validBranchRule(): Exists
    {
        $employee = $this->routeEmployee();
        $currentBranchId = $employee?->branch_id;

        return Rule::exists('branches', 'id')
            ->where(
                function ($query) use ($currentBranchId): void {
                    $query->where(
                        function ($branchQuery) use (
                            $currentBranchId
                        ): void {
                            $branchQuery->where(
                                'status',
                                'active'
                            );

                            if ($currentBranchId !== null) {
                                $branchQuery->orWhere(
                                    'id',
                                    $currentBranchId
                                );
                            }
                        }
                    );
                }
            );
    }

    /**
     * Mengambil model karyawan dari parameter route {employee}.
     */
    private function routeEmployee(): ?Employee
    {
        $employee = $this->route('employee');

        if ($employee instanceof Employee) {
            return $employee;
        }

        if (
            is_numeric($employee)
            && (int) $employee > 0
        ) {
            return Employee::query()->find(
                (int) $employee
            );
        }

        return null;
    }

    /**
     * Mengubah teks kosong menjadi null.
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
