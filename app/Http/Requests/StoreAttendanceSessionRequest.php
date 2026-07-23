<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AttendanceSession;
use App\Models\Branch;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreAttendanceSessionRequest extends FormRequest
{
    /**
     * Sesi presensi dapat dibuat oleh HRD dan admin operasional.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return in_array(
            $user->role,
            [
                'hrd',
                'admin',
            ],
            true
        );
    }

    /**
     * Menormalisasi nilai sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'branch_id' => $this->nullableValue(
                'branch_id'
            ),

            'attendance_type' => Str::lower(
                trim(
                    (string) $this->input(
                        'attendance_type'
                    )
                )
            ),

            'session_date' => trim(
                (string) $this->input(
                    'session_date'
                )
            ),

            'start_time' => trim(
                (string) $this->input(
                    'start_time'
                )
            ),

            'end_time' => trim(
                (string) $this->input(
                    'end_time'
                )
            ),
        ]);
    }

    /**
     * Aturan dasar pembukaan sesi presensi.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists(
                    'branches',
                    'id'
                )->where(
                    fn ($query) => $query->where(
                        'status',
                        'active'
                    )
                ),
            ],

            'attendance_type' => [
                'bail',
                'required',
                'string',
                Rule::in([
                    'check_in',
                    'check_out',
                ]),
            ],

            'session_date' => [
                'bail',
                'required',
                'date_format:Y-m-d',
            ],

            'start_time' => [
                'bail',
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'bail',
                'required',
                'date_format:H:i',
            ],
        ];
    }

    /**
     * Validasi bisnis setelah aturan dasar terpenuhi.
     */
    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator): void {
                $this->validateBranchConfiguration(
                    $validator
                );

                $this->validateSessionTimeRange(
                    $validator
                );

                $this->validateActiveSessionUniqueness(
                    $validator
                );
            }
        );
    }

    /**
     * Pesan validasi dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch_id.required' => 'Cabang wajib dipilih.',

            'branch_id.integer' => 'Data cabang tidak valid.',

            'branch_id.exists' => 'Cabang tidak ditemukan atau sudah tidak aktif.',

            'attendance_type.required' => 'Jenis sesi presensi wajib dipilih.',

            'attendance_type.string' => 'Jenis sesi presensi tidak valid.',

            'attendance_type.in' => 'Jenis sesi harus berupa presensi masuk atau presensi pulang.',

            'session_date.required' => 'Tanggal sesi presensi wajib diisi.',

            'session_date.date_format' => 'Format tanggal sesi presensi tidak valid.',

            'start_time.required' => 'Waktu mulai sesi wajib diisi.',

            'start_time.date_format' => 'Format waktu mulai sesi tidak valid.',

            'end_time.required' => 'Waktu berakhir sesi wajib diisi.',

            'end_time.date_format' => 'Format waktu berakhir sesi tidak valid.',
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
            'branch_id' => 'cabang',

            'attendance_type' => 'jenis sesi presensi',

            'session_date' => 'tanggal sesi presensi',

            'start_time' => 'waktu mulai sesi',

            'end_time' => 'waktu berakhir sesi',
        ];
    }

    /**
     * Cabang harus mempunyai koordinat dan batas geofence.
     */
    private function validateBranchConfiguration(
        Validator $validator
    ): void {
        if (
            $validator
                ->errors()
                ->has('branch_id')
        ) {
            return;
        }

        $branchId = $this->input(
            'branch_id'
        );

        if (
            ! is_numeric($branchId)
            || (int) $branchId <= 0
        ) {
            return;
        }

        $branch = Branch::query()->find(
            (int) $branchId
        );

        if (
            $branch !== null
            && ! $branch->hasGeofenceConfiguration()
        ) {
            $validator->errors()->add(
                'branch_id',
                'Cabang belum memiliki konfigurasi geofence yang lengkap.'
            );
        }
    }

    /**
     * Waktu berakhir harus lebih besar dari waktu mulai.
     */
    private function validateSessionTimeRange(
        Validator $validator
    ): void {
        foreach (
            [
                'session_date',
                'start_time',
                'end_time',
            ] as $field
        ) {
            if (
                $validator
                    ->errors()
                    ->has($field)
            ) {
                return;
            }
        }

        $sessionDate = (string) $this->input(
            'session_date'
        );

        $startTime = $this->parseSessionDateTime(
            $sessionDate,
            (string) $this->input('start_time')
        );

        $endTime = $this->parseSessionDateTime(
            $sessionDate,
            (string) $this->input('end_time')
        );

        if (
            $startTime === null
            || $endTime === null
        ) {
            return;
        }

        if ($endTime <= $startTime) {
            $validator->errors()->add(
                'end_time',
                'Waktu berakhir sesi harus setelah waktu mulai sesi.'
            );
        }
    }

    /**
     * Mencegah dua sesi aktif untuk cabang, tanggal,
     * dan jenis presensi yang sama.
     */
    private function validateActiveSessionUniqueness(
        Validator $validator
    ): void {
        foreach (
            [
                'branch_id',
                'attendance_type',
                'session_date',
            ] as $field
        ) {
            if (
                $validator
                    ->errors()
                    ->has($field)
            ) {
                return;
            }
        }

        $branchId = $this->input(
            'branch_id'
        );

        $attendanceType = (string) $this->input(
            'attendance_type'
        );

        $sessionDate = (string) $this->input(
            'session_date'
        );

        if (
            ! is_numeric($branchId)
            || (int) $branchId <= 0
        ) {
            return;
        }

        $activeSessionExists =
            AttendanceSession::query()
                ->where(
                    'branch_id',
                    (int) $branchId
                )
                ->where(
                    'attendance_type',
                    $attendanceType
                )
                ->whereDate(
                    'session_date',
                    $sessionDate
                )
                ->where(
                    'status',
                    'active'
                )
                ->exists();

        if (! $activeSessionExists) {
            return;
        }

        $attendanceTypeLabel =
            $attendanceType === 'check_in'
                ? 'masuk'
                : 'pulang';

        $validator->errors()->add(
            'attendance_type',
            sprintf(
                'Cabang tersebut sudah mempunyai sesi presensi %s yang aktif pada tanggal pilihan.',
                $attendanceTypeLabel
            )
        );
    }

    /**
     * Menggabungkan tanggal sesi dan input waktu.
     */
    private function parseSessionDateTime(
        string $sessionDate,
        string $time
    ): ?DateTimeImmutable {
        $dateTime = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            "{$sessionDate} {$time}",
            new DateTimeZone(
                (string) config(
                    'app.timezone',
                    'Asia/Jakarta'
                )
            )
        );

        if ($dateTime === false) {
            return null;
        }

        $errors = DateTimeImmutable::getLastErrors();

        if (
            $errors !== false
            && (
                $errors['warning_count'] > 0
                || $errors['error_count'] > 0
            )
        ) {
            return null;
        }

        return $dateTime;
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
}
