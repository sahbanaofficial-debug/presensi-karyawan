<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreWeeklyScheduleRequest extends FormRequest
{
    /**
     * HRD aktif dapat mengelola seluruh cabang.
     * Admin aktif hanya dapat mengelola cabang penugasannya.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $branchId = $this->input('branch_id');

        return $user instanceof User
            && is_int($branchId)
            && $branchId > 0
            && $user->canManageWeeklyRosterForBranch(
                $branchId
            );
    }

    /**
     * Menormalkan header roster dan seluruh item jadwal.
     */
    protected function prepareForValidation(): void
    {
        $rawItems = $this->input('items');
        $normalizedItems = $rawItems;

        if (is_array($rawItems)) {
            $normalizedItems = [];

            foreach ($rawItems as $index => $item) {
                if (! is_array($item)) {
                    $normalizedItems[$index] = $item;

                    continue;
                }

                $status = $this->normalizedString(
                    $item['schedule_status'] ?? null
                );

                $normalizedItems[$index] = array_replace(
                    $item,
                    [
                        'employee_id' => $this->normalizedIdentifier(
                            $item['employee_id'] ?? null
                        ),

                        'work_schedule_id' => $this->normalizedIdentifier(
                            $item['work_schedule_id'] ?? null
                        ),

                        'schedule_date' => $this->normalizedString(
                            $item['schedule_date'] ?? null
                        ),

                        'schedule_status' => is_string($status)
                            ? strtolower($status)
                            : $status,

                        'notes' => $this->normalizedNullableText(
                            $item['notes'] ?? null
                        ),
                    ]
                );
            }
        }

        $this->merge([
            'branch_id' => $this->normalizedIdentifier(
                $this->input('branch_id')
            ),

            'week_start_date' => $this->normalizedString(
                $this->input('week_start_date')
            ),

            'items' => $normalizedItems,
        ]);
    }

    /**
     * Aturan validasi dasar payload roster mingguan.
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
                'min:1',

                Rule::exists(
                    'branches',
                    'id'
                )->where(
                    static fn ($query) => $query->where(
                        'status',
                        'active'
                    )
                ),
            ],

            'week_start_date' => [
                'bail',
                'required',
                'date_format:Y-m-d',
            ],

            'items' => [
                'bail',
                'required',
                'array',
                'min:1',
            ],

            'items.*' => [
                'bail',
                'required',
                'array',
            ],

            'items.*.employee_id' => [
                'bail',
                'required',
                'integer',
                'min:1',
                Rule::exists('employees', 'id'),
            ],

            'items.*.work_schedule_id' => [
                'bail',
                'nullable',
                'integer',
                'min:1',
                Rule::exists('work_schedules', 'id'),
            ],

            'items.*.schedule_date' => [
                'bail',
                'required',
                'date_format:Y-m-d',
            ],

            'items.*.schedule_status' => [
                'bail',
                'required',
                'string',

                Rule::in([
                    'work',
                    'off',
                    'leave',
                    'permit',
                    'sick',
                ]),
            ],

            'items.*.notes' => [
                'bail',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Menjalankan aturan bisnis lintas field.
     */
    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator): void {
                $items = $this->input('items');

                if (! is_array($items)) {
                    return;
                }

                $weekStart = $this->strictDate(
                    $this->input('week_start_date')
                );

                if ($weekStart === null) {
                    return;
                }

                if ($weekStart->format('N') !== '1') {
                    $validator->errors()->add(
                        'week_start_date',
                        'Tanggal awal roster wajib berada pada hari Senin.'
                    );
                }

                $weekEnd = $weekStart->modify('+6 days');

                $branchId = $this->input('branch_id');

                $employeeIds = [];
                $workScheduleIds = [];

                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $employeeId =
                        $item['employee_id'] ?? null;

                    if (
                        is_int($employeeId)
                        && $employeeId > 0
                    ) {
                        $employeeIds[] = $employeeId;
                    }

                    $workScheduleId =
                        $item['work_schedule_id'] ?? null;

                    if (
                        is_int($workScheduleId)
                        && $workScheduleId > 0
                    ) {
                        $workScheduleIds[] =
                            $workScheduleId;
                    }
                }

                $employees = Employee::query()
                    ->whereIn(
                        'id',
                        array_values(
                            array_unique($employeeIds)
                        )
                    )
                    ->get([
                        'id',
                        'branch_id',
                        'employment_status',
                    ])
                    ->keyBy('id');

                $workSchedules = WorkSchedule::query()
                    ->whereIn(
                        'id',
                        array_values(
                            array_unique($workScheduleIds)
                        )
                    )
                    ->get([
                        'id',
                        'status',
                    ])
                    ->keyBy('id');

                $seenEmployeeDates = [];

                foreach ($items as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $employeeField =
                        "items.{$index}.employee_id";

                    $workScheduleField =
                        "items.{$index}.work_schedule_id";

                    $scheduleDateField =
                        "items.{$index}.schedule_date";

                    $employeeId =
                        $item['employee_id'] ?? null;

                    $workScheduleId =
                        $item['work_schedule_id'] ?? null;

                    $scheduleStatus =
                        $item['schedule_status'] ?? null;

                    $scheduleDate = $this->strictDate(
                        $item['schedule_date'] ?? null
                    );

                    if (
                        $scheduleDate !== null
                        && (
                            $scheduleDate < $weekStart
                            || $scheduleDate > $weekEnd
                        )
                    ) {
                        $validator->errors()->add(
                            $scheduleDateField,
                            'Tanggal item harus berada dalam rentang Senin sampai Minggu pada roster tersebut.'
                        );
                    }

                    if (
                        is_int($employeeId)
                        && $employeeId > 0
                        && $scheduleDate !== null
                    ) {
                        $employeeDateKey = sprintf(
                            '%d|%s',
                            $employeeId,
                            $scheduleDate->format('Y-m-d')
                        );

                        if (
                            isset(
                                $seenEmployeeDates[
                                    $employeeDateKey
                                ]
                            )
                        ) {
                            $validator->errors()->add(
                                $scheduleDateField,
                                'Karyawan tidak boleh memiliki dua item roster pada tanggal yang sama.'
                            );
                        } else {
                            $seenEmployeeDates[
                                $employeeDateKey
                            ] = true;
                        }
                    }

                    if (
                        is_int($employeeId)
                        && $employeeId > 0
                        && ! $validator
                            ->errors()
                            ->has($employeeField)
                    ) {
                        $employee = $employees->get(
                            $employeeId
                        );

                        if ($employee !== null) {
                            if (! $employee->isActive()) {
                                $validator->errors()->add(
                                    $employeeField,
                                    'Karyawan roster harus berstatus aktif.'
                                );
                            } elseif (
                                is_int($branchId)
                                && $branchId > 0
                                && ! $employee
                                    ->isAssignedToBranch(
                                        $branchId
                                    )
                            ) {
                                $validator->errors()->add(
                                    $employeeField,
                                    'Karyawan tidak ditempatkan pada cabang roster yang dipilih.'
                                );
                            }
                        }
                    }

                    if (
                        is_int($workScheduleId)
                        && $workScheduleId > 0
                        && ! $validator
                            ->errors()
                            ->has($workScheduleField)
                    ) {
                        $workSchedule =
                            $workSchedules->get(
                                $workScheduleId
                            );

                        if (
                            $workSchedule !== null
                            && ! $workSchedule->isActive()
                        ) {
                            $validator->errors()->add(
                                $workScheduleField,
                                'Pola jadwal kerja harus berstatus aktif.'
                            );
                        }
                    }

                    if (
                        ! is_string($scheduleStatus)
                        || ! in_array(
                            $scheduleStatus,
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
                        continue;
                    }

                    if (
                        $scheduleStatus === 'work'
                        && $workScheduleId === null
                    ) {
                        $validator->errors()->add(
                            $workScheduleField,
                            'Pola jadwal kerja wajib dipilih untuk status kerja.'
                        );
                    }

                    if (
                        $scheduleStatus !== 'work'
                        && $workScheduleId !== null
                    ) {
                        $validator->errors()->add(
                            $workScheduleField,
                            'Pola jadwal kerja tidak boleh diisi untuk status nonkerja.'
                        );
                    }
                }
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
            'branch_id.required' => 'Cabang roster wajib dipilih.',

            'branch_id.integer' => 'Data cabang roster tidak valid.',

            'branch_id.min' => 'Data cabang roster tidak valid.',

            'branch_id.exists' => 'Cabang tidak ditemukan atau sudah tidak aktif.',

            'week_start_date.required' => 'Tanggal awal roster wajib diisi.',

            'week_start_date.date_format' => 'Format tanggal awal roster tidak valid.',

            'items.required' => 'Item roster mingguan wajib diisi.',

            'items.array' => 'Format item roster mingguan tidak valid.',

            'items.min' => 'Roster mingguan minimal memiliki satu item.',

            'items.*.array' => 'Format salah satu item roster tidak valid.',

            'items.*.employee_id.required' => 'Karyawan wajib dipilih.',

            'items.*.employee_id.integer' => 'Data karyawan tidak valid.',

            'items.*.employee_id.exists' => 'Karyawan tidak ditemukan.',

            'items.*.work_schedule_id.integer' => 'Data pola jadwal kerja tidak valid.',

            'items.*.work_schedule_id.exists' => 'Pola jadwal kerja tidak ditemukan.',

            'items.*.schedule_date.required' => 'Tanggal item roster wajib diisi.',

            'items.*.schedule_date.date_format' => 'Format tanggal item roster tidak valid.',

            'items.*.schedule_status.required' => 'Status item roster wajib dipilih.',

            'items.*.schedule_status.in' => 'Status item roster tidak valid.',

            'items.*.notes.string' => 'Catatan roster harus berupa teks.',

            'items.*.notes.max' => 'Catatan roster maksimal 1000 karakter.',
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
            'branch_id' => 'cabang roster',
            'week_start_date' => 'tanggal awal roster',
            'items' => 'item roster',
            'items.*.employee_id' => 'karyawan',
            'items.*.work_schedule_id' => 'pola jadwal kerja',
            'items.*.schedule_date' => 'tanggal item roster',
            'items.*.schedule_status' => 'status item roster',
            'items.*.notes' => 'catatan roster',
        ];
    }

    /**
     * Mengubah ID numerik menjadi integer.
     */
    private function normalizedIdentifier(
        mixed $value
    ): mixed {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = trim($value);

            if ($normalized === '') {
                return null;
            }

            if (ctype_digit($normalized)) {
                return (int) $normalized;
            }

            return $normalized;
        }

        return $value;
    }

    /**
     * Membersihkan string wajib.
     */
    private function normalizedString(
        mixed $value
    ): mixed {
        if (! is_string($value)) {
            return $value;
        }

        return trim($value);
    }

    /**
     * Membersihkan teks opsional.
     */
    private function normalizedNullableText(
        mixed $value
    ): mixed {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return $value;
        }

        $normalized = trim($value);

        return $normalized === ''
            ? null
            : $normalized;
    }

    /**
     * Membaca tanggal Y-m-d secara ketat.
     */
    private function strictDate(
        mixed $value
    ): ?DateTimeImmutable {
        if (
            ! is_string($value)
            || $value === ''
        ) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value,
            new DateTimeZone(
                (string) config(
                    'app.timezone',
                    'Asia/Jakarta'
                )
            )
        );

        if ($date === false) {
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

        if ($date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date;
    }
}
