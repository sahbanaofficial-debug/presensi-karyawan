<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreAttendanceCorrectionRequest extends FormRequest
{
    /**
     * Hanya HRD aktif yang boleh melakukan
     * koreksi manual presensi.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->role === 'hrd'
            && $user->status === 'active';
    }

    /**
     * Aturan validasi koreksi manual.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',

                Rule::exists(
                    'employees',
                    'id'
                )->where(
                    static fn ($query) =>
                        $query->where(
                            'employment_status',
                            'active'
                        )
                ),
            ],

            'attendance_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            'attendance_type' => [
                'required',
                'string',

                Rule::in([
                    'check_in',
                    'check_out',
                ]),
            ],

            'attendance_time' => [
                'required',
                'date_format:H:i',
            ],

            'reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Field yang Dikelola Server
            |--------------------------------------------------------------------------
            |
            | Pengguna tidak boleh mengirim atau memanipulasi
            | nilai berikut melalui formulir.
            |
            */
            'id' => [
                'prohibited',
            ],

            'attendance_id' => [
                'prohibited',
            ],

            'attendance_session_id' => [
                'prohibited',
            ],

            'employee_schedule_id' => [
                'prohibited',
            ],

            'branch_id' => [
                'prohibited',
            ],

            'latitude' => [
                'prohibited',
            ],

            'longitude' => [
                'prohibited',
            ],

            'accuracy' => [
                'prohibited',
            ],

            'distance' => [
                'prohibited',
            ],

            'geofence_radius' => [
                'prohibited',
            ],

            'attendance_status' => [
                'prohibited',
            ],

            'punctuality_status' => [
                'prohibited',
            ],

            'validation_status' => [
                'prohibited',
            ],

            'record_source' => [
                'prohibited',
            ],

            'last_corrected_by' => [
                'prohibited',
            ],

            'last_correction_reason' => [
                'prohibited',
            ],

            'last_corrected_at' => [
                'prohibited',
            ],

            'corrected_by' => [
                'prohibited',
            ],

            'action' => [
                'prohibited',
            ],

            'before_data' => [
                'prohibited',
            ],

            'after_data' => [
                'prohibited',
            ],

            'created_at' => [
                'prohibited',
            ],

            'updated_at' => [
                'prohibited',
            ],
        ];
    }

    /**
     * Melakukan validasi tambahan setelah
     * aturan dasar berhasil diperiksa.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->any()) {
                    return;
                }

                $employee = Employee::query()
                    ->with('branch')
                    ->find(
                        $this->employeeId()
                    );

                if ($employee === null) {
                    $validator->errors()->add(
                        'employee_id',
                        'Data karyawan tidak ditemukan.'
                    );

                    return;
                }

                if ($employee->branch === null) {
                    $validator->errors()->add(
                        'employee_id',
                        'Karyawan belum memiliki cabang penempatan.'
                    );

                    return;
                }

                if ($employee->branch->status !== 'active') {
                    $validator->errors()->add(
                        'employee_id',
                        'Cabang penempatan karyawan tidak aktif.'
                    );

                    return;
                }

                $employeeSchedule =
                    EmployeeSchedule::query()
                        ->where(
                            'employee_id',
                            $employee->getKey()
                        )
                        ->whereDate(
                            'schedule_date',
                            $this->attendanceDate()
                        )
                        ->first();

                if ($employeeSchedule === null) {
                    $validator->errors()->add(
                        'attendance_date',
                        'Jadwal karyawan pada tanggal tersebut tidak ditemukan.'
                    );

                    return;
                }

                if (
                    $employeeSchedule->schedule_status
                    !== 'work'
                ) {
                    $validator->errors()->add(
                        'attendance_date',
                        'Koreksi presensi hanya dapat dilakukan pada jadwal kerja.'
                    );
                }
            },
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
            'employee_id.required' =>
                'Karyawan wajib dipilih.',

            'employee_id.integer' =>
                'Data karyawan tidak valid.',

            'employee_id.exists' =>
                'Karyawan aktif tidak ditemukan.',

            'attendance_date.required' =>
                'Tanggal presensi wajib diisi.',

            'attendance_date.date_format' =>
                'Format tanggal presensi harus YYYY-MM-DD.',

            'attendance_date.before_or_equal' =>
                'Tanggal presensi tidak boleh melebihi tanggal hari ini.',

            'attendance_type.required' =>
                'Jenis presensi wajib dipilih.',

            'attendance_type.in' =>
                'Jenis presensi harus berupa presensi masuk atau presensi pulang.',

            'attendance_time.required' =>
                'Waktu presensi wajib diisi.',

            'attendance_time.date_format' =>
                'Format waktu presensi harus HH:MM.',

            'reason.required' =>
                'Alasan koreksi wajib diisi.',

            'reason.min' =>
                'Alasan koreksi minimal 10 karakter.',

            'reason.max' =>
                'Alasan koreksi maksimal 1000 karakter.',
        ];
    }

    /**
     * Nama atribut yang tampil pada pesan
     * validasi.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'karyawan',
            'attendance_date' => 'tanggal presensi',
            'attendance_type' => 'jenis presensi',
            'attendance_time' => 'waktu presensi',
            'reason' => 'alasan koreksi',
        ];
    }

    /**
     * Menormalkan input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $reason = trim(
            (string) $this->input(
                'reason',
                ''
            )
        );

        $normalizedReason = preg_replace(
            '/\s+/',
            ' ',
            $reason
        );

        $this->merge([
            'employee_id' => $this->filled(
                'employee_id'
            )
                ? trim(
                    (string) $this->input(
                        'employee_id'
                    )
                )
                : null,

            'attendance_date' => trim(
                (string) $this->input(
                    'attendance_date',
                    ''
                )
            ),

            'attendance_type' => strtolower(
                trim(
                    (string) $this->input(
                        'attendance_type',
                        ''
                    )
                )
            ),

            'attendance_time' => trim(
                (string) $this->input(
                    'attendance_time',
                    ''
                )
            ),

            'reason' => $normalizedReason
                ?? $reason,
        ]);
    }

    /**
     * ID karyawan yang telah divalidasi.
     */
    public function employeeId(): int
    {
        return (int) $this->input(
            'employee_id'
        );
    }

    /**
     * Tanggal presensi dengan format Y-m-d.
     */
    public function attendanceDate(): string
    {
        return (string) $this->input(
            'attendance_date'
        );
    }

    /**
     * Jenis presensi yang telah dinormalisasi.
     */
    public function attendanceType(): string
    {
        return (string) $this->input(
            'attendance_type'
        );
    }

    /**
     * Waktu presensi dengan format H:i.
     */
    public function attendanceTime(): string
    {
        return (string) $this->input(
            'attendance_time'
        );
    }

    /**
     * Alasan koreksi yang telah dinormalisasi.
     */
    public function correctionReason(): string
    {
        return (string) $this->input(
            'reason'
        );
    }

    /**
     * Tanggal dan waktu presensi dalam zona
     * waktu aplikasi.
     */
    public function attendanceMoment(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            sprintf(
                '%s %s:00',
                $this->attendanceDate(),
                $this->attendanceTime()
            ),
            (string) config(
                'app.timezone',
                'Asia/Jakarta'
            )
        );
    }
}