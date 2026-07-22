<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\EmployeeSchedule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

final class StoreEmployeeScheduleRequest extends FormRequest
{
    /**
     * Hanya HRD yang dapat menetapkan jadwal harian.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('hrd') === true;
    }

    /**
     * Menormalisasi data sebelum proses validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_id' => $this->nullableValue(
                'employee_id'
            ),

            'work_schedule_id' => $this->nullableValue(
                'work_schedule_id'
            ),

            'schedule_date' => trim(
                (string) $this->input('schedule_date')
            ),

            'schedule_status' => Str::lower(
                trim(
                    (string) $this->input(
                        'schedule_status'
                    )
                )
            ),

            'notes' => $this->nullableString('notes'),
        ]);
    }

    /**
     * Aturan validasi jadwal harian baru.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'bail',
                'required',
                'integer',
                $this->activeEmployeeRule(),
            ],

            'schedule_date' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                $this->uniqueEmployeeDateRule(),
            ],

            'schedule_status' => [
                'bail',
                'required',
                'string',
                Rule::in([
                    'work',
                    'off',
                    'permit',
                    'sick',
                ]),
            ],

            'work_schedule_id' => [
                'bail',
                'nullable',
                'integer',
                'required_if:schedule_status,work',
                'prohibited_unless:schedule_status,work',
                $this->activeWorkScheduleRule(),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:65535',
            ],
        ];
    }

    /**
     * Pesan validasi berbahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan wajib dipilih.',

            'employee_id.integer' => 'Data karyawan tidak valid.',

            'employee_id.exists' => 'Karyawan tidak ditemukan atau sudah tidak aktif.',

            'schedule_date.required' => 'Tanggal jadwal wajib diisi.',

            'schedule_date.date_format' => 'Format tanggal jadwal tidak valid.',

            'schedule_status.required' => 'Status jadwal wajib dipilih.',

            'schedule_status.in' => 'Status jadwal harus kerja, libur, izin, atau sakit.',

            'work_schedule_id.required_if' => 'Pola jadwal kerja wajib dipilih untuk status kerja.',

            'work_schedule_id.integer' => 'Data pola jadwal kerja tidak valid.',

            'work_schedule_id.exists' => 'Pola jadwal kerja tidak ditemukan atau sudah tidak aktif.',

            'work_schedule_id.prohibited_unless' => 'Pola jadwal kerja hanya boleh dipilih untuk status kerja.',

            'notes.string' => 'Keterangan harus berupa teks.',

            'notes.max' => 'Keterangan terlalu panjang.',
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
            'employee_id' => 'karyawan',
            'work_schedule_id' => 'pola jadwal kerja',
            'schedule_date' => 'tanggal jadwal',
            'schedule_status' => 'status jadwal',
            'notes' => 'keterangan',
        ];
    }

    /**
     * Karyawan yang dipilih harus aktif.
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
     * Pola jadwal yang dipilih harus aktif.
     */
    private function activeWorkScheduleRule(): Exists
    {
        return Rule::exists(
            'work_schedules',
            'id'
        )->where(
            fn ($query) => $query->where(
                'status',
                'active'
            )
        );
    }

    /**
     * Memeriksa kombinasi karyawan dan tanggal.
     *
     * whereDate digunakan karena schedule_date dapat tersimpan
     * sebagai tanggal dengan waktu 00:00:00.
     */
    private function uniqueEmployeeDateRule(): Closure
    {
        return function (
            string $attribute,
            mixed $value,
            Closure $fail
        ): void {
            $employeeId = $this->input('employee_id');

            if (
                ! is_numeric($employeeId)
                || (int) $employeeId <= 0
                || ! is_string($value)
                || $value === ''
            ) {
                return;
            }

            $alreadyExists = EmployeeSchedule::query()
                ->where(
                    'employee_id',
                    (int) $employeeId
                )
                ->whereDate(
                    'schedule_date',
                    $value
                )
                ->exists();

            if ($alreadyExists) {
                $fail(
                    'Karyawan sudah mempunyai jadwal atau status pada tanggal tersebut.'
                );
            }
        };
    }

    /**
     * Mengubah input kosong menjadi null.
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
