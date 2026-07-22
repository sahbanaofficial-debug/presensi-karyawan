<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreWorkScheduleRequest extends FormRequest
{
    /**
     * Hanya HRD yang dapat menambahkan pola jadwal kerja.
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
            'name' => trim(
                (string) $this->input('name')
            ),
            'check_in_time' => $this->normalizeTime(
                'check_in_time'
            ),
            'check_out_time' => $this->normalizeTime(
                'check_out_time'
            ),
            'check_in_open_minutes' => trim(
                (string) $this->input(
                    'check_in_open_minutes'
                )
            ),
            'late_tolerance_minutes' => trim(
                (string) $this->input(
                    'late_tolerance_minutes'
                )
            ),
            'check_out_limit_minutes' => trim(
                (string) $this->input(
                    'check_out_limit_minutes'
                )
            ),
            'status' => Str::lower(
                trim((string) $this->input('status'))
            ),
        ]);
    }

    /**
     * Aturan validasi pola jadwal kerja.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'bail',
                'required',
                'string',
                'max:100',
                Rule::unique(
                    'work_schedules',
                    'name'
                ),
            ],
            'check_in_time' => [
                'bail',
                'required',
                'date_format:H:i',
            ],
            'check_out_time' => [
                'bail',
                'required',
                'date_format:H:i',
                'after:check_in_time',
            ],
            'check_in_open_minutes' => [
                'bail',
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],
            'late_tolerance_minutes' => [
                'bail',
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],
            'check_out_limit_minutes' => [
                'bail',
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],
            'status' => [
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
            'name.required' => 'Nama jadwal wajib diisi.',
            'name.max' => 'Nama jadwal maksimal 100 karakter.',
            'name.unique' => 'Nama jadwal sudah digunakan.',

            'check_in_time.required' => 'Jam masuk wajib diisi.',
            'check_in_time.date_format' => 'Format jam masuk tidak valid.',

            'check_out_time.required' => 'Jam pulang wajib diisi.',
            'check_out_time.date_format' => 'Format jam pulang tidak valid.',
            'check_out_time.after' => 'Jam pulang harus setelah jam masuk.',

            'check_in_open_minutes.required' => 'Waktu pembukaan presensi masuk wajib diisi.',
            'check_in_open_minutes.integer' => 'Waktu pembukaan presensi masuk harus berupa bilangan bulat.',
            'check_in_open_minutes.min' => 'Waktu pembukaan presensi masuk tidak boleh bernilai negatif.',
            'check_in_open_minutes.max' => 'Waktu pembukaan presensi masuk maksimal 1.440 menit.',

            'late_tolerance_minutes.required' => 'Toleransi keterlambatan wajib diisi.',
            'late_tolerance_minutes.integer' => 'Toleransi keterlambatan harus berupa bilangan bulat.',
            'late_tolerance_minutes.min' => 'Toleransi keterlambatan tidak boleh bernilai negatif.',
            'late_tolerance_minutes.max' => 'Toleransi keterlambatan maksimal 1.440 menit.',

            'check_out_limit_minutes.required' => 'Batas akhir presensi pulang wajib diisi.',
            'check_out_limit_minutes.integer' => 'Batas akhir presensi pulang harus berupa bilangan bulat.',
            'check_out_limit_minutes.min' => 'Batas akhir presensi pulang tidak boleh bernilai negatif.',
            'check_out_limit_minutes.max' => 'Batas akhir presensi pulang maksimal 1.440 menit.',

            'status.required' => 'Status jadwal wajib dipilih.',
            'status.in' => 'Status jadwal harus active atau inactive.',
        ];
    }

    /**
     * Nama atribut yang digunakan pada pesan validasi bawaan.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama jadwal',
            'check_in_time' => 'jam masuk',
            'check_out_time' => 'jam pulang',
            'check_in_open_minutes' => 'waktu pembukaan presensi masuk',
            'late_tolerance_minutes' => 'toleransi keterlambatan',
            'check_out_limit_minutes' => 'batas akhir presensi pulang',
            'status' => 'status jadwal',
        ];
    }

    /**
     * Menormalisasi input waktu HTML menjadi format HH:MM.
     */
    private function normalizeTime(
        string $field
    ): string {
        $value = trim(
            (string) $this->input($field)
        );

        if (
            preg_match(
                '/^\d{2}:\d{2}:\d{2}$/',
                $value
            ) === 1
        ) {
            return substr($value, 0, 5);
        }

        return $value;
    }
}
