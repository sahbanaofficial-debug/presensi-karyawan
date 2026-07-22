<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\EmployeeSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

final class UpdateEmployeeScheduleRequest extends FormRequest
{
    /**
     * Hanya HRD yang dapat mengubah jadwal harian karyawan.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('hrd') === true;
    }

    /**
     * Menormalisasi data sebelum validasi.
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
     * Aturan validasi perubahan jadwal harian.
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
                $this->validEmployeeRule(),
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
                $this->validWorkScheduleRule(),
            ],

            'notes' => [
                'nullable',
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
            'employee_id.required' => 'Karyawan wajib dipilih.',

            'employee_id.integer' => 'Data karyawan tidak valid.',

            'employee_id.exists' => 'Karyawan tidak ditemukan atau sudah tidak aktif.',

            'schedule_date.required' => 'Tanggal jadwal wajib diisi.',

            'schedule_date.date_format' => 'Format tanggal jadwal tidak valid.',

            'schedule_date.unique' => 'Karyawan sudah mempunyai jadwal atau status pada tanggal tersebut.',

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
     * Karyawan tujuan harus aktif.
     *
     * Karyawan lama tetap diperbolehkan apabila profilnya
     * dinonaktifkan setelah jadwal dibuat.
     */
    private function validEmployeeRule(): Exists
    {
        $currentEmployeeId = $this
            ->routeEmployeeSchedule()
            ?->employee_id;

        return Rule::exists(
            'employees',
            'id'
        )->where(
            function ($query) use (
                $currentEmployeeId
            ): void {
                $query->where(
                    function ($employeeQuery) use (
                        $currentEmployeeId
                    ): void {
                        $employeeQuery->where(
                            'employment_status',
                            'active'
                        );

                        if ($currentEmployeeId !== null) {
                            $employeeQuery->orWhere(
                                'id',
                                $currentEmployeeId
                            );
                        }
                    }
                );
            }
        );
    }

    /**
     * Pola jadwal tujuan harus aktif.
     *
     * Pola jadwal lama tetap dapat dipertahankan apabila
     * dinonaktifkan setelah penetapan jadwal dibuat.
     */
    private function validWorkScheduleRule(): Exists
    {
        $currentWorkScheduleId = $this
            ->routeEmployeeSchedule()
            ?->work_schedule_id;

        return Rule::exists(
            'work_schedules',
            'id'
        )->where(
            function ($query) use (
                $currentWorkScheduleId
            ): void {
                $query->where(
                    function ($scheduleQuery) use (
                        $currentWorkScheduleId
                    ): void {
                        $scheduleQuery->where(
                            'status',
                            'active'
                        );

                        if ($currentWorkScheduleId !== null) {
                            $scheduleQuery->orWhere(
                                'id',
                                $currentWorkScheduleId
                            );
                        }
                    }
                );
            }
        );
    }

    /**
     * Kombinasi karyawan dan tanggal harus unik,
     * tetapi mengabaikan jadwal yang sedang diedit.
     */
    private function uniqueEmployeeDateRule(): Unique
    {
        $rule = Rule::unique(
            'employee_schedules',
            'schedule_date'
        )->where(
            fn ($query) => $query->where(
                'employee_id',
                $this->input('employee_id')
            )
        );

        $employeeSchedule = $this
            ->routeEmployeeSchedule();

        if ($employeeSchedule !== null) {
            $rule->ignore(
                $employeeSchedule->getKey()
            );
        }

        return $rule;
    }

    /**
     * Mengambil model dari parameter route
     * {employee_schedule}.
     */
    private function routeEmployeeSchedule(): ?EmployeeSchedule
    {
        $employeeSchedule = $this->route(
            'employee_schedule'
        );

        if (
            $employeeSchedule instanceof EmployeeSchedule
        ) {
            return $employeeSchedule;
        }

        if (
            is_numeric($employeeSchedule)
            && (int) $employeeSchedule > 0
        ) {
            return EmployeeSchedule::query()->find(
                (int) $employeeSchedule
            );
        }

        return null;
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
